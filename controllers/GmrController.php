<?php

namespace app\controllers;

use Yii;
use Throwable;
use yii\web\Controller;
use yii\web\Response;
use app\models\GmrBuyTrade;
use app\models\GmrExchange;
use app\models\GmrInvoice;
use app\models\GmrInvoiceDetail;
use app\models\GmrMonthlySummary;
use app\models\GmrBankBalance;

/**
 * Receives the iTrust 2026 workbook sync from the Google Apps Script
 * (example/gmr-sync.script).
 *
 * Endpoints:
 *   POST /gmr/sync         single row (on-edit trigger)
 *   POST /gmr/sync-batch   many rows, optionally replacing a whole sheet
 *   POST /gmr/sync-invoice rendered invoices (+ lines), example/invoices.json
 *   GET  /gmr              health / entity list
 *
 * Authentication: "Authorization: Bearer <gmrSyncToken>" (config/params.php).
 */
class GmrController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return parent::beforeAction($action);
    }

    /**
     * Entity key => ActiveRecord class + mass-assignable columns.
     *
     * @return array
     */
    protected function entities()
    {
        return [
            'buy_trade' => [
                'model' => GmrBuyTrade::class,
                'fields' => ['month', 'trade_day', 'trade_date', 'ref_id', 'nominal', 'price', 'total'],
            ],
            'exchange' => [
                'model' => GmrExchange::class,
                'fields' => ['month', 'trade_day', 'trade_date', 'ref_id', 'nominal', 'rate_buy', 'buy_amount', 'rate_sell', 'sell_amount', 'fee_pct', 'fee_amount', 'omset', 'profit'],
            ],
            'invoice' => [
                'model' => GmrInvoice::class,
                'fields' => ['month', 'direction', 'invoice_date', 'ref_id', 'invoice_number', 'billed_to', 'payment_method', 'issue_date', 'due_date', 'status', 'qty', 'rate', 'amount'],
            ],
            'invoice_detail' => [
                'model' => GmrInvoiceDetail::class,
                'fields' => ['month', 'detail_date', 'ref_id', 'invoice_number', 'description', 'qty', 'unit_price', 'amount'],
            ],
            'monthly_summary' => [
                'model' => GmrMonthlySummary::class,
                'fields' => ['month', 'nominal', 'rate_buy_avg', 'buy_amount', 'rate_sell_avg', 'sell_amount', 'fee', 'profit'],
            ],
            'bank_balance' => [
                'model' => GmrBankBalance::class,
                'fields' => ['entry_type', 'balance_day', 'balance_date', 'global_amount', 'sisa_usdt', 'tambah_modal', 'out_amount', 'total', 'profit', 'remark', 'bca', 'bca_bisnis', 'blu_bca', 'mandiri', 'bri', 'bri_vega', 'bni', 'sinarmas', 'total_bank', 'usdt', 'rate', 'total_usdt'],
            ],
        ];
    }

    /**
     * GET /gmr - health check.
     */
    public function actionIndex()
    {
        return [
            'success' => true,
            'service' => 'GMR sync',
            'entities' => array_keys($this->entities()),
        ];
    }

    /**
     * POST /gmr/sync - upsert a single row.
     */
    public function actionSync()
    {
        if (!$this->authorize()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $payload = $this->jsonBody();

        if (!isset($payload['rows']) && isset($payload['record'])) {
            $payload['rows'] = [$payload['record']];
        }

        $result = $this->process($payload);

        if (!$result['success']) {
            Yii::$app->response->statusCode = 422;
        }

        return $result;
    }

    /**
     * POST /gmr/sync-batch - upsert many rows, optionally replacing a sheet.
     */
    public function actionSyncBatch()
    {
        if (!$this->authorize()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $result = $this->process($this->jsonBody());

        if (!$result['success']) {
            Yii::$app->response->statusCode = 422;
        }

        return $result;
    }

    /**
     * POST /gmr/sync-invoice - upsert rendered invoices and their line items.
     *
     * Accepts a single invoice object (example/invoices.json) or a list under
     * "invoices". Each invoice is keyed by its "id" (= invoice_number) so
     * repeated calls update the same header + lines instead of duplicating.
     *
     * Response: { success, count, invoices: [{ invoice_id, invoice_number, status }] }
     *
     * @return array
     */
    public function actionSyncInvoice()
    {
        if (!$this->authorize()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $payload = $this->jsonBody();

        if (isset($payload['invoices']) && is_array($payload['invoices'])) {
            $invoices = $payload['invoices'];
        } elseif (isset($payload['id']) || isset($payload['invoice_number'])) {
            $invoices = [$payload];
        } else {
            $invoices = array_values(array_filter($payload, 'is_array'));
        }

        if ($invoices === []) {
            Yii::$app->response->statusCode = 422;

            return ['success' => false, 'message' => 'No invoices supplied'];
        }

        $results = [];
        $transaction = Yii::$app->db->beginTransaction();

        try {
            foreach ($invoices as $invoice) {
                $results[] = $this->syncInvoice($invoice);
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            Yii::error('GMR invoice sync failed: ' . $e->getMessage(), 'gmr');
            Yii::$app->response->statusCode = 422;

            return ['success' => false, 'message' => $e->getMessage()];
        }

        return [
            'success' => true,
            'count' => count($results),
            'invoices' => $results,
        ];
    }

    /**
     * Upsert one rendered invoice and replace its API-owned line items.
     *
     * @param array $data
     * @return array
     */
    protected function syncInvoice($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invoice is not an object');
        }

        $invoiceNumber = trim((string) ($data['id'] ?? $data['invoice_number'] ?? ''));
        if ($invoiceNumber === '') {
            throw new \InvalidArgumentException('Invoice is missing "id"');
        }

        $lines = $data['lines'] ?? [];
        if (!is_array($lines)) {
            $lines = [];
        }

        $direction = strtoupper((string) ($data['direction'] ?? $this->detectDirection($lines)));
        if (!in_array($direction, [GmrInvoice::DIRECTION_BUY, GmrInvoice::DIRECTION_SELL], true)) {
            $direction = GmrInvoice::DIRECTION_BUY;
        }

        $date = $this->normalizeDate($data['date'] ?? $data['invoice_date'] ?? null);
        $dueDate = $this->normalizeDate($data['dueDate'] ?? $data['due_date'] ?? null);
        $issueDate = $this->normalizeDate($data['issueDate'] ?? $data['issue_date'] ?? null) ?? $date;

        $qty = 0.0;
        $amount = 0.0;
        foreach ($lines as $line) {
            $lineQty = (float) ($line['qty'] ?? 0);
            $linePrice = (float) ($line['price'] ?? $line['unit_price'] ?? 0);
            $qty += $lineQty;
            $amount += $lineQty * $linePrice;
        }

        // Invoices are keyed by their number alone so a re-sync always updates
        // the existing row instead of inserting a second one when the detected
        // direction (or the sheet it came from) differs between runs.
        $syncKey = 'API:' . $invoiceNumber;

        $model = GmrInvoice::findOne(['sync_key' => $syncKey]);
        if ($model === null) {
            $model = GmrInvoice::findOne(['invoice_number' => $invoiceNumber]);
        }
        if ($model === null) {
            $model = new GmrInvoice();
            $model->sync_key = $syncKey;
            $model->source_sheet = 'API';
        }

        $model->month = $data['month'] ?? $this->monthFromDate($date ?? $dueDate);
        $model->direction = $direction;
        $model->invoice_date = $date;
        $model->issue_date = $issueDate;
        $model->due_date = $dueDate;
        $model->ref_id = $data['ref_id'] ?? $data['refId'] ?? null;
        $model->invoice_number = $invoiceNumber;
        $model->billed_to = $data['customer'] ?? $data['billed_to'] ?? null;
        $model->payment_method = $data['paymentMethod'] ?? $data['payment_method'] ?? null;
        $model->status = $data['status'] ?? null;
        $model->qty = $qty;
        $model->rate = $qty > 0 ? $amount / $qty : 0;
        $model->amount = $amount;

        if (!$model->save()) {
            throw new \RuntimeException(
                'Invoice ' . $invoiceNumber . ' failed: ' . json_encode($model->getErrors())
            );
        }

        GmrInvoiceDetail::deleteAll(['source_sheet' => 'API', 'invoice_number' => $invoiceNumber]);

        foreach ($lines as $index => $line) {
            $detail = new GmrInvoiceDetail();
            $detail->source_sheet = 'API';
            $detail->sync_key = 'API:' . $invoiceNumber . ':' . $index;
            $detail->month = $model->month;
            $detail->detail_date = $date;
            $detail->ref_id = $model->ref_id;
            $detail->invoice_number = $invoiceNumber;
            $detail->description = $line['description'] ?? null;
            $detail->qty = (float) ($line['qty'] ?? 0);
            $detail->unit_price = (float) ($line['price'] ?? $line['unit_price'] ?? 0);
            $detail->amount = $detail->qty * $detail->unit_price;

            if (!$detail->save()) {
                throw new \RuntimeException(
                    'Invoice line ' . $invoiceNumber . '#' . $index . ' failed: ' . json_encode($detail->getErrors())
                );
            }
        }

        return [
            'invoice_id' => (int) $model->id,
            'invoice_number' => $model->invoice_number,
            'status' => $model->status,
            'lines' => count($lines),
        ];
    }

    /**
     * Infer BUY/SELL from the first line description that names a side.
     *
     * @param array $lines
     * @return string
     */
    protected function detectDirection($lines)
    {
        foreach ($lines as $line) {
            $description = strtoupper((string) ($line['description'] ?? ''));
            if (strpos($description, 'SELL') !== false) {
                return GmrInvoice::DIRECTION_SELL;
            }
            if (strpos($description, 'BUY') !== false) {
                return GmrInvoice::DIRECTION_BUY;
            }
        }

        return GmrInvoice::DIRECTION_BUY;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    protected function normalizeDate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /**
     * Derive the sheet-style month label (JAN..DEC) from a date.
     *
     * @param string|null $date
     * @return string|null
     */
    protected function monthFromDate($date)
    {
        if ($date === null) {
            return null;
        }

        $timestamp = strtotime($date);

        return $timestamp === false ? null : strtoupper(date('M', $timestamp));
    }

    /**
     * Validate a payload and persist its rows.
     *
     * @param array $payload
     * @return array
     */
    protected function process($payload)
    {
        $entityKey = $payload['entity'] ?? '';
        $entities = $this->entities();

        if (!isset($entities[$entityKey])) {
            return ['success' => false, 'message' => "Unknown entity '{$entityKey}'"];
        }

        $rows = $payload['rows'] ?? [];
        if (!is_array($rows) || $rows === []) {
            return ['success' => false, 'message' => 'No rows supplied'];
        }

        $sourceSheet = (string) ($payload['source_sheet'] ?? '');
        $month = $payload['month'] ?? null;
        $mode = $payload['mode'] ?? 'upsert';

        $class = $entities[$entityKey]['model'];
        $fields = $entities[$entityKey]['fields'];

        $created = 0;
        $updated = 0;
        $errors = [];

        try {
            if ($mode === 'replace' && $sourceSheet !== '') {
                $class::deleteAll(['source_sheet' => $sourceSheet]);
            }

            foreach ($rows as $index => $row) {
                if (!is_array($row)) {
                    $errors[] = ['row' => $index, 'message' => 'Row is not an object'];
                    continue;
                }

                $sheetRow = $row['sheet_row'] ?? null;
                $key = $row['sync_key'] ?? ($sourceSheet !== '' && $sheetRow !== null ? $sourceSheet . ':' . $sheetRow : null);

                if ($key === null || $key === '') {
                    $errors[] = ['row' => $index, 'message' => 'Missing sync_key'];
                    continue;
                }

                /** @var \yii\db\ActiveRecord $model */
                $model = $class::findOne(['sync_key' => $key]);
                $isNew = $model === null;

                if ($isNew) {
                    $model = new $class();
                    $model->sync_key = $key;
                    if ($sourceSheet !== '') {
                        $model->source_sheet = $sourceSheet;
                    }
                    if ($sheetRow !== null) {
                        $model->sheet_row = $sheetRow;
                    }
                    if ($month !== null && $model->hasAttribute('month')) {
                        $model->month = $month;
                    }
                }

                foreach ($fields as $field) {
                    if (array_key_exists($field, $row) && $model->hasAttribute($field)) {
                        $model->$field = $row[$field];
                    }
                }

                if ($model->save()) {
                    $isNew ? $created++ : $updated++;
                } else {
                    $errors[] = ['row' => $index, 'sync_key' => $key, 'errors' => $model->getErrors()];
                }
            }
        } catch (Throwable $e) {
            Yii::error('GMR sync failed: ' . $e->getMessage(), 'gmr');

            return ['success' => false, 'message' => $e->getMessage()];
        }

        return [
            'success' => true,
            'entity' => $entityKey,
            'source_sheet' => $sourceSheet,
            'mode' => $mode,
            'received' => count($rows),
            'created' => $created,
            'updated' => $updated,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check the bearer token against config/params.php:gmrSyncToken.
     *
     * @return bool
     */
    protected function authorize()
    {
        $expected = Yii::$app->params['gmrSyncToken'] ?? null;

        if (empty($expected)) {
            return true;
        }

        $header = Yii::$app->request->headers->get('Authorization', '');
        $token = preg_replace('/^Bearer\s+/i', '', trim($header));

        if ($token !== '' && hash_equals($expected, $token)) {
            return true;
        }

        Yii::$app->response->statusCode = 401;

        return false;
    }

    /**
     * @return array
     */
    protected function jsonBody()
    {
        $raw = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            $data = Yii::$app->request->post();
        }

        return is_array($data) ? $data : [];
    }
}
