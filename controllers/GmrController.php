<?php

namespace app\controllers;

use Yii;
use Throwable;
use yii\data\Pagination;
use yii\web\Controller;
use yii\web\Response;
use app\models\GmrBuyTrade;
use app\models\GmrExchange;
use app\models\GmrInvoice;
use app\models\GmrInvoiceDetail;
use app\models\GmrMonthlySummary;
use app\models\GmrBankBalance;
use app\helpers\Toon;

/**
 * Receives the iTrust 2026 workbook sync from the Google Apps Script
 * (example/gmr-sync.script).
 *
 * Endpoints:
 *   POST /gmr/sync         single row (on-edit trigger)
 *   POST /gmr/sync-batch   many rows, optionally replacing a whole sheet
 *   POST /gmr/sync-invoice rendered invoices (+ lines), example/invoices.json
 *   POST /gmr/sync-toon    same payloads as sync / sync-invoice, TOON-encoded
 *   GET  /gmr              health / entity list
 *   GET  /gmr/invoices     list invoices, each with its line items
 *   GET  /gmr/invoice      one invoice (id or invoice_number) with its lines
 *   GET  /gmr/invoice-toon one invoice, encoded as TOON
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
                'fields' => ['month', 'direction', 'invoice_date', 'ref_id', 'invoice_number', 'billed_to', 'payment_method', 'account_number', 'account_name', 'issue_date', 'due_date', 'status', 'qty', 'rate', 'amount', 'total', 'tax_pct', 'tax_amount', 'fee_pct', 'fee_amount'],
            ],
            'invoice_detail' => [
                'model' => GmrInvoiceDetail::class,
                'fields' => ['month', 'detail_date', 'ref_id', 'invoice_number', 'description', 'qty', 'unit_price', 'amount', 'fee_pct', 'fee_amount'],
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

        $result = $this->processInvoices($this->jsonBody());

        if (!$result['success']) {
            Yii::$app->response->statusCode = 422;
        }

        return $result;
    }

    /**
     * POST /gmr/sync-toon - upsert rows from a TOON-encoded payload.
     *
     * The request body is TOON text (helpers/Toon.php) carrying the same shapes
     * as /gmr/sync (entity + rows) or /gmr/sync-invoice (rendered invoices with
     * lines). The response is JSON, like the other sync endpoints.
     *
     * @return array
     */
    public function actionSyncToon()
    {
        if (!$this->authorize()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $payload = $this->toonBody();

        $result = isset($payload['entity'])
            ? $this->process($payload)
            : $this->processInvoices($payload);

        if (!$result['success']) {
            Yii::$app->response->statusCode = 422;
        }

        return $result;
    }

    /**
     * Upsert rendered invoices (single object, a list, or under "invoices"),
     * replacing each invoice's line items in one transaction.
     *
     * @param array $payload
     * @return array
     */
    protected function processInvoices($payload)
    {
        if (isset($payload['invoices']) && is_array($payload['invoices'])) {
            $invoices = $payload['invoices'];
        } elseif (isset($payload['id']) || isset($payload['invoice_number'])) {
            $invoices = [$payload];
        } else {
            $invoices = array_values(array_filter($payload, 'is_array'));
        }

        if ($invoices === []) {
            return ['success' => false, 'message' => 'No invoices supplied'];
        }

        $sourceSheet = trim((string) ($payload['source_sheet'] ?? ''));

        $results = [];
        $transaction = Yii::$app->db->beginTransaction();

        try {
            foreach ($invoices as $invoice) {
                $results[] = $this->syncInvoice($invoice, $sourceSheet);
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            Yii::error('GMR invoice sync failed: ' . $e->getMessage(), 'gmr');

            return ['success' => false, 'message' => $e->getMessage()];
        }

        return [
            'success' => true,
            'count' => count($results),
            'invoices' => $results,
        ];
    }

    /**
     * GET /gmr/invoices - invoice headers, each with its line items.
     *
     * Query params (all optional):
     *   id              - exact invoice id
     *   invoice_number  - exact invoice number
     *   direction       - BUY | SELL (comma-separated list allowed)
     *   month           - JAN..DEC (comma-separated list allowed)
     *   status          - PENDING | PAID | CANCEL (comma-separated list allowed)
     *   ref_id          - exact sheet ref id
     *   source_sheet    - exact source sheet (e.g. API, INV_BUY_SEP)
     *   billed_to       - substring of the billed-to name
     *   search          - substring of invoice_number or billed_to
     *   date_from       - invoice_date >= YYYY-MM-DD
     *   date_to         - invoice_date <= YYYY-MM-DD
     *   created_from    - created_at >= YYYY-MM-DD
     *   created_to      - created_at <= YYYY-MM-DD
     *   updated_from    - updated_at >= YYYY-MM-DD
     *   updated_to      - updated_at <= YYYY-MM-DD
     *   with_details    - 0 to omit the lines (default 1)
     *   sort_by         - id | invoice_number | direction | month | invoice_date | issue_date
     *                     | due_date | billed_to | payment_method | status | qty | rate
     *                     | amount | created_at | updated_at (default id)
     *   sort_order      - asc | desc (default desc)
     *   page            - 1-based page number (default 1)
     *   per_page        - rows per page, default 50, max 200 (alias: limit)
     *   offset          - row offset, used when page is not given (alias behaviour)
     *
     * Response: { success, count, total, pagination, invoices: [...] }
     * Also sets X-Pagination-Total-Count / -Page-Count / -Current-Page / -Per-Page.
     *
     * @return array
     */
    public function actionInvoices()
    {
        if (!$this->authorize()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $request = Yii::$app->request;
        $query = GmrInvoice::find();

        $this->applyInvoiceFilters($query);

        $total = (int) (clone $query)->count();

        $pagination = new Pagination([
            'totalCount' => $total,
            'pageSize' => $this->resolvePageSize($request),
        ]);

        // Accept either ?page= (preferred) or ?offset= for the position.
        $page = $request->get('page');
        if ($page === null || $page === '') {
            $offset = max(0, (int) $request->get('offset', 0));
            $page = (int) floor($offset / $pagination->pageSize) + 1;
        }
        $pagination->setPage((int) $page - 1, true);

        $withDetails = (string) $request->get('with_details', '1') !== '0';
        if ($withDetails) {
            $query->with('details');
        }

        $this->applyInvoiceSort($query, $request);

        $invoices = $query
            ->limit($pagination->getLimit())
            ->offset($pagination->getOffset())
            ->all();

        $page = $pagination->getPage() + 1;
        $pageCount = $pagination->getPageCount();

        Yii::$app->response->headers->set('X-Pagination-Total-Count', (string) $pagination->totalCount);
        Yii::$app->response->headers->set('X-Pagination-Page-Count', (string) $pageCount);
        Yii::$app->response->headers->set('X-Pagination-Current-Page', (string) $page);
        Yii::$app->response->headers->set('X-Pagination-Per-Page', (string) $pagination->pageSize);

        return [
            'success' => true,
            'count' => count($invoices),
            'total' => $total,
            'pagination' => [
                'total' => $pagination->totalCount,
                'count' => count($invoices),
                'page' => $page,
                'per_page' => $pagination->pageSize,
                'page_count' => $pageCount,
                'has_next' => $page < $pageCount,
                'has_prev' => $page > 1,
            ],
            'invoices' => array_map(
                function ($invoice) use ($withDetails) {
                    return $this->serializeInvoice($invoice, $withDetails);
                },
                $invoices
            ),
        ];
    }

    /**
     * Page size from ?per_page (or ?limit), clamped to 1..200 with a default of 50.
     *
     * @param \yii\web\Request $request
     * @return int
     */
    protected function resolvePageSize($request)
    {
        $value = $request->get('per_page');
        if ($value === null || $value === '') {
            $value = $request->get('limit');
        }

        $size = (int) $value;
        if ($size < 1) {
            return 50;
        }

        return $size > 200 ? 200 : $size;
    }

    /**
     * GET /gmr/invoice - a single invoice header with its line items.
     *
     * Query params: id (preferred) or invoice_number.
     *
     * Response: { success, invoice: { ...header, details: [...] } }
     *
     * @return array
     */
    public function actionInvoice()
    {
        if (!$this->authorize()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $request = Yii::$app->request;
        $id = $request->get('id');
        $number = trim((string) $request->get('invoice_number', ''));

        if (($id === null || $id === '') && $number === '') {
            Yii::$app->response->statusCode = 422;

            return ['success' => false, 'message' => 'Provide id or invoice_number'];
        }

        $query = GmrInvoice::find()->with('details');

        if ($id !== null && $id !== '') {
            $query->andWhere(['id' => (int) $id]);
        }
        if ($number !== '') {
            $query->andWhere(['invoice_number' => $number]);
        }

        $model = $query->one();

        if ($model === null) {
            Yii::$app->response->statusCode = 404;

            return ['success' => false, 'message' => 'Invoice not found'];
        }

        return [
            'success' => true,
            'invoice' => $this->serializeInvoice($model, true),
        ];
    }

    /**
     * GET /gmr/invoice-toon - a single invoice (id or invoice_number) as TOON.
     *
     * Query params: id (preferred) or invoice_number.
     *
     * Response: TOON text (helpers/Toon.php). 422 when neither id nor
     * invoice_number is given, 404 when the invoice does not exist.
     *
     * @return array|\yii\web\Response
     */
    public function actionInvoiceToon()
    {
        if (!$this->authorize()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $request = Yii::$app->request;
        $id = $request->get('id');
        $number = trim((string) $request->get('invoice_number', ''));

        if (($id === null || $id === '') && $number === '') {
            Yii::$app->response->statusCode = 422;

            return ['success' => false, 'message' => 'Provide id or invoice_number'];
        }

        $query = GmrInvoice::find()->with('details');

        if ($id !== null && $id !== '') {
            $query->andWhere(['id' => (int) $id]);
        }
        if ($number !== '') {
            $query->andWhere(['invoice_number' => $number]);
        }

        $model = $query->one();

        if ($model === null) {
            Yii::$app->response->statusCode = 404;

            return ['success' => false, 'message' => 'Invoice not found'];
        }

        return $this->toonResponse([
            'success' => true,
            'invoice' => $this->serializeInvoice($model, true),
        ]);
    }

    /**
     * Apply the shared invoice filters from the current request to a query.
     *
     * @param \yii\db\ActiveQuery $query
     * @return void
     */
    protected function applyInvoiceFilters($query)
    {
        $request = Yii::$app->request;

        $id = $request->get('id');
        if ($id !== null && $id !== '') {
            $query->andWhere(['id' => (int) $id]);
        }

        $number = trim((string) $request->get('invoice_number', ''));
        if ($number !== '') {
            $query->andWhere(['invoice_number' => $number]);
        }

        // direction / month / status accept a comma-separated list (or an array).
        $directions = array_map('strtoupper', $this->csvParam($request, 'direction'));
        if ($directions !== []) {
            $query->andWhere(['in', 'direction', $directions]);
        }

        $months = array_map('strtoupper', $this->csvParam($request, 'month'));
        if ($months !== []) {
            $query->andWhere(['in', 'month', $months]);
        }

        $statuses = array_map('strtoupper', $this->csvParam($request, 'status'));
        if ($statuses !== []) {
            $query->andWhere(['in', 'status', $statuses]);
        }

        $refId = trim((string) $request->get('ref_id', ''));
        if ($refId !== '') {
            $query->andWhere(['ref_id' => $refId]);
        }

        $sourceSheet = trim((string) $request->get('source_sheet', ''));
        if ($sourceSheet !== '') {
            $query->andWhere(['source_sheet' => $sourceSheet]);
        }

        $billedTo = trim((string) $request->get('billed_to', ''));
        if ($billedTo !== '') {
            $query->andWhere(['like', 'billed_to', $billedTo]);
        }

        $from = $this->normalizeDate($request->get('date_from'));
        if ($from !== null) {
            $query->andWhere(['>=', 'invoice_date', $from]);
        }

        $to = $this->normalizeDate($request->get('date_to'));
        if ($to !== null) {
            $query->andWhere(['<=', 'invoice_date', $to]);
        }

        $this->applyDateRange($query, 'created_at', $request->get('created_from'), $request->get('created_to'));
        $this->applyDateRange($query, 'updated_at', $request->get('updated_from'), $request->get('updated_to'));

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'invoice_number', $search],
                ['like', 'billed_to', $search],
            ]);
        }
    }

    /**
     * Apply ?sort_by / ?sort_order (whitelisted) to the invoice query.
     *
     * @param \yii\db\ActiveQuery $query
     * @param \yii\web\Request $request
     * @return void
     */
    protected function applyInvoiceSort($query, $request)
    {
        $allowed = [
            'id', 'invoice_number', 'direction', 'month', 'invoice_date', 'issue_date',
            'due_date', 'billed_to', 'payment_method', 'status', 'qty', 'rate', 'amount',
            'created_at', 'updated_at',
        ];

        $sortBy = trim((string) $request->get('sort_by', ''));
        if (!in_array($sortBy, $allowed, true)) {
            $sortBy = 'id';
        }

        $order = strtoupper(trim((string) $request->get('sort_order', '')));
        $order = $order === 'ASC' ? SORT_ASC : SORT_DESC;

        $query->orderBy([$sortBy => $order]);
    }

    /**
     * Apply an inclusive date range to a DATETIME column.
     *
     * @param \yii\db\ActiveQuery $query
     * @param string $column
     * @param mixed $from
     * @param mixed $to
     * @return void
     */
    protected function applyDateRange($query, $column, $from, $to)
    {
        $start = $this->normalizeDate($from);
        if ($start !== null) {
            $query->andWhere(['>=', $column, $start . ' 00:00:00']);
        }

        $end = $this->normalizeDate($to);
        if ($end !== null) {
            $query->andWhere(['<=', $column, $end . ' 23:59:59']);
        }
    }

    /**
     * Read a request param that may be a comma-separated string or an array.
     *
     * @param \yii\web\Request $request
     * @param string $name
     * @return string[]
     */
    protected function csvParam($request, $name)
    {
        $value = $request->get($name);
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value) ? $value : explode(',', (string) $value);
        $out = [];
        foreach ($values as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $out[] = $item;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Shape an invoice header for a JSON response.
     *
     * @param GmrInvoice $model
     * @param bool $withDetails
     * @return array
     */
    protected function serializeInvoice($model, $withDetails = true)
    {
        $data = [
            'id' => (int) $model->id,
            'invoice_number' => $model->invoice_number,
            'direction' => $model->direction,
            'month' => $model->month,
            'source_sheet' => $model->source_sheet,
            'invoice_date' => $model->invoice_date,
            'issue_date' => $model->issue_date,
            'due_date' => $model->due_date,
            'ref_id' => $model->ref_id,
            'billed_to' => $model->billed_to,
            'payment_method' => $model->payment_method,
            'account_number' => $model->account_number,
            'account_name' => $model->account_name,
            'status' => $model->status,
            'qty' => $this->toFloat($model->qty),
            'rate' => $this->toFloat($model->rate),
            'amount' => $this->toFloat($model->amount),
            'tax_pct' => $this->toFloat($model->tax_pct),
            'tax_amount' => $this->toFloat($model->tax_amount),
            'fee_pct' => $this->toFloat($model->fee_pct),
            'fee_amount' => $this->toFloat($model->fee_amount),
            'total' => $this->toFloat($model->total),
        ];

        if ($withDetails) {
            $data['details'] = array_map([$this, 'serializeInvoiceDetail'], $model->details);
        }

        return $data;
    }

    /**
     * Shape an invoice line for a JSON response.
     *
     * @param GmrInvoiceDetail $detail
     * @return array
     */
    protected function serializeInvoiceDetail($detail)
    {
        return [
            'id' => (int) $detail->id,
            'detail_date' => $detail->detail_date,
            'ref_id' => $detail->ref_id,
            'description' => $detail->description,
            'qty' => $this->toFloat($detail->qty),
            'unit_price' => $this->toFloat($detail->unit_price),
            'amount' => $this->toFloat($detail->amount),
            'fee_pct' => $this->toFloat($detail->fee_pct),
            'fee_amount' => $this->toFloat($detail->fee_amount),
        ];
    }

    /**
     * Cast a DECIMAL column (PDO hands it back as a string) to a float.
     *
     * @param mixed $value
     * @return float|null
     */
    protected function toFloat($value)
    {
        return $value === null ? null : (float) $value;
    }

    /**
     * Upsert one rendered invoice and replace its API-owned line items.
     *
     * @param array $data
     * @param string $sourceSheet payload-level source sheet, used when the
     *                            invoice itself does not carry one
     * @return array
     */
    protected function syncInvoice($data, $sourceSheet = '')
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

        // The row-level source_sheet wins over the payload-level one; both fall
        // back to the API pseudo-sheet so the required column is never empty.
        $sheet = trim((string) ($data['source_sheet'] ?? ''));
        if ($sheet === '') {
            $sheet = trim((string) $sourceSheet);
        }

        $model = GmrInvoice::findOne(['sync_key' => $syncKey]);
        if ($model === null) {
            $model = GmrInvoice::findOne(['invoice_number' => $invoiceNumber]);
        }
        if ($model === null) {
            $model = new GmrInvoice();
            $model->sync_key = $syncKey;
            $model->source_sheet = $sheet !== '' ? $sheet : 'API';
        } elseif ($sheet !== '') {
            $model->source_sheet = $sheet;
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
        $model->account_number = $data['account_number'] ?? $data['accountNumber'] ?? null;
        $model->account_name = $data['account_name'] ?? $data['accountName'] ?? null;
        $model->status = $data['status'] ?? null;
        $model->qty = $qty;
        $model->rate = $qty > 0 ? $amount / $qty : 0;
        $model->amount = $amount;
        $model->tax_pct = (float) ($data['tax_pct'] ?? $data['taxPct'] ?? 0);
        $model->tax_amount = (float) ($data['tax_amount'] ?? $data['taxValue'] ?? 0);
        $model->fee_pct = (float) ($data['fee_pct'] ?? $data['feePct'] ?? 0);
        $model->fee_amount = (float) ($data['fee_amount'] ?? $data['feeValue'] ?? 0);
        // Stored exactly as the sheet sends it (column N on both sheet
        // families); the sheet formula is the source of truth, so it is never
        // recomputed here.
        $model->total = (float) ($data['total'] ?? 0);

        if (!$model->save()) {
            throw new \RuntimeException(
                'Invoice ' . $invoiceNumber . ' failed: ' . json_encode($model->getErrors())
            );
        }

        // Replace the invoice's whole line set, from any source, so a re-sync
        // can never leave the previous lines behind and duplicate them.
        GmrInvoiceDetail::deleteAll(['invoice_number' => $invoiceNumber]);

        foreach ($lines as $index => $line) {
            $detail = new GmrInvoiceDetail();
            $detail->source_sheet = $model->source_sheet;
            $detail->sync_key = 'API:' . $invoiceNumber . ':' . $index;
            $detail->month = $model->month;
            $detail->detail_date = $date;
            $detail->ref_id = $model->ref_id;
            $detail->invoice_number = $invoiceNumber;
            $detail->description = $line['description'] ?? null;
            $detail->qty = (float) ($line['qty'] ?? 0);
            $detail->unit_price = (float) ($line['price'] ?? $line['unit_price'] ?? 0);
            $detail->amount = $detail->qty * $detail->unit_price;
            $detail->fee_pct = (float) ($line['fee_pct'] ?? $line['feePct'] ?? 0);
            $detail->fee_amount = (float) ($line['fee_amount'] ?? $line['feeValue'] ?? $line['fee'] ?? 0);

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
        $skipped = 0;
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

                // A zero or negative amount means the row carries no value; skip it.
                if (array_key_exists('amount', $row) && (float) $row['amount'] <= 0) {
                    $skipped++;
                    continue;
                }

                $sheetRow = $row['sheet_row'] ?? null;
                $rowSheet = trim((string) ($row['source_sheet'] ?? $sourceSheet));
                $key = $row['sync_key'] ?? ($rowSheet !== '' && $sheetRow !== null ? $rowSheet . ':' . $sheetRow : null);

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
                    if ($sheetRow !== null) {
                        $model->sheet_row = $sheetRow;
                    }
                    if ($month !== null && $model->hasAttribute('month')) {
                        $model->month = $month;
                    }
                }

                // Persist the originating sheet name (a row value wins over the payload).
                if ($rowSheet !== '' && $model->hasAttribute('source_sheet')) {
                    $model->source_sheet = $rowSheet;
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
            'skipped' => $skipped,
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

    /**
     * Decode the request body as TOON into a PHP array.
     *
     * @return array
     */
    protected function toonBody()
    {
        $decoded = Toon::decode(Yii::$app->request->getRawBody());

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Encode a payload as a TOON response body.
     *
     * @param array $data
     * @return \yii\web\Response
     */
    protected function toonResponse($data)
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/toon; charset=UTF-8');
        $response->data = Toon::encode($data);

        return $response;
    }
}
