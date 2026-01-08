<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * This is the model class for table "scraped_data_logs".
 *
 * @property int $id
 * @property string $endpoint
 * @property string $method
 * @property string $pair
 * @property string $timeframe
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $request_data JSON data
 * @property int|null $response_status
 * @property float|null $response_time
 * @property string $created_at
 */
class ScrapedDataLog extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'scraped_data_logs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['endpoint', 'method', 'pair', 'timeframe'], 'required'],
            [['user_agent', 'request_data'], 'string'],
            [['response_status'], 'integer'],
            [['response_time'], 'number'],
            [['created_at'], 'safe'],
            [['endpoint'], 'string', 'max' => 100],
            [['method'], 'string', 'max' => 10],
            [['pair', 'timeframe'], 'string', 'max' => 10],
            [['ip_address'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'endpoint' => 'Endpoint',
            'method' => 'Method',
            'pair' => 'Currency Pair',
            'timeframe' => 'Timeframe',
            'ip_address' => 'IP Address',
            'user_agent' => 'User Agent',
            'request_data' => 'Request Data',
            'response_status' => 'Response Status',
            'response_time' => 'Response Time (ms)',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Extract pair and timeframe from URL
     *
     * @param string $url
     * @return array ['pair' => string, 'timeframe' => string]
     */
    public static function extractPairAndTimeframe($url)
    {
        $pair = 'UNKNOWN';
        $timeframe = 'UNKNOWN';

        // Extract from myfxbook URL pattern: /forex-market/currencies/EURJPY-H4
        if (preg_match('/currencies\/([A-Z]{6})-?([A-Za-z0-9]*)/i', $url, $matches)) {
            $pair = $matches[1] ?? 'UNKNOWN';
            $timeframe = $matches[2] ?? 'UNKNOWN';
        }

        // If timeframe is empty, try to get from other patterns
        if (empty($timeframe) || $timeframe === 'UNKNOWN') {
            // Check for timeframe in URL parameters or path
            $timeframes = ['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1', 'W1', 'MN'];
            foreach ($timeframes as $tf) {
                if (stripos($url, $tf) !== false) {
                    $timeframe = $tf;
                    break;
                }
            }
        }

        return [
            'pair' => strtoupper($pair),
            'timeframe' => strtoupper($timeframe)
        ];
    }

    /**
     * Log scraped data request
     *
     * @param string $endpoint
     * @param string $method
     * @param array|string $requestData
     * @param string|null $pair Override auto-detected pair
     * @param string|null $timeframe Override auto-detected timeframe
     * @param int|null $responseStatus
     * @param float|null $responseTime
     * @return bool
     */
    public static function logScrapedData($endpoint, $method, $requestData, $pair = null, $timeframe = null, $responseStatus = null, $responseTime = null)
    {
        $log = new self();
        $log->endpoint = $endpoint;
        $log->method = $method;
        $log->ip_address = Yii::$app->request->getUserIP();
        $log->user_agent = Yii::$app->request->getUserAgent();

        // Convert request data to JSON if it's not already a string
        if (!is_string($requestData)) {
            $requestData = json_encode($requestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $log->request_data = $requestData;

        // Extract pair and timeframe from data if not provided
        if ($pair === null || $timeframe === null) {
            $dataArray = json_decode($requestData, true);
            $url = $dataArray['metadata']['url'] ?? '';

            if (!empty($url)) {
                $extracted = self::extractPairAndTimeframe($url);

                if ($pair === null) {
                    $pair = $extracted['pair'];
                }
                if ($timeframe === null) {
                    $timeframe = $extracted['timeframe'];
                }
            }
        }

        $log->pair = $pair ?? 'UNKNOWN';
        $log->timeframe = $timeframe ?? 'UNKNOWN';
        $log->response_status = $responseStatus;
        $log->response_time = $responseTime;

        return $log->save();
    }

    /**
     * Get request data as array
     *
     * @return array|null
     */
    public function getRequestDataArray()
    {
        if (empty($this->request_data)) {
            return null;
        }

        return json_decode($this->request_data, true);
    }

    /**
     * Get metadata from request data
     *
     * @return array|null
     */
    public function getMetadata()
    {
        $data = $this->getRequestDataArray();
        return $data['metadata'] ?? null;
    }

    /**
     * Get technical analysis data
     *
     * @return array|null
     */
    public function getTechnicalAnalysis()
    {
        $data = $this->getRequestDataArray();
        return $data['data']['technicalAnalysis'] ?? null;
    }

    /**
     * Get economic calendar data
     *
     * @return array|null
     */
    public function getEconomicCalendar()
    {
        $data = $this->getRequestDataArray();
        return $data['data']['economicCalendar'] ?? null;
    }

    /**
     * Get interest rates data
     *
     * @return array|null
     */
    public function getInterestRates()
    {
        $data = $this->getRequestDataArray();
        return $data['data']['interestRates'] ?? null;
    }

    /**
     * Get scraped URL
     *
     * @return string|null
     */
    public function getScrapedUrl()
    {
        $metadata = $this->getMetadata();
        return $metadata['url'] ?? null;
    }

    /**
     * Get scrape timestamp
     *
     * @return string|null
     */
    public function getScrapeTimestamp()
    {
        $metadata = $this->getMetadata();
        return $metadata['scrapeTimestamp'] ?? null;
    }

    /**
     * Find logs by pair and timeframe
     *
     * @param string $pair
     * @param string $timeframe
     * @param int $limit
     * @return array
     */
    public static function findByPairAndTimeframe($pair, $timeframe, $limit = 10)
    {
        return self::find()
            ->where(['pair' => strtoupper($pair), 'timeframe' => strtoupper($timeframe)])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Get statistics by pair
     *
     * @param string $pair
     * @return array
     */
    public static function getPairStatistics($pair)
    {
        $query = self::find()->where(['pair' => strtoupper($pair)]);
        return [
            'total_logs' => $query->count(),
            'average_response_time' => $query->average('response_time'),
            'success_rate' => $query->andWhere(['response_status' => 200])->count() / max(1, $query->count()) * 100,
            'latest_log' => $query->orderBy(['created_at' => SORT_DESC])->one(),
        ];
    }

    public static function getLatestData($pair = "EURJPJ", $timeframe = "H4")
    {
        return [
            'latest_data' =>
            self::find()
                ->where(['pair' => strtoupper($pair), 'timeframe' => strtoupper($timeframe)])
                ->orderBy(['created_at' => SORT_DESC])
                ->one()
        ];
    }
}
