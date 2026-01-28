<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\Cors;
use app\models\forms\LoginForm;
use app\helpers\TelegramHelper;
use app\helpers\JwtHelper;
use app\models\MyfxbookScrapedDataNew;
use app\models\InvestingScrapedData;
use app\models\MyfxbookScrapedData;
use app\models\MyfxbookEconomicEvent;
use app\models\MyfxbookTechnicalPattern;
use app\models\MyfxbookInterestRate;
use app\models\MyfxbookStatistics;
use app\models\MyfxbookApiLog;
use app\models\TelemetryData;
use app\models\UserDevices;
use app\models\ScrapedDataLog;
use app\models\DualSourceScrapedData;
use yii\web\BadRequestHttpException;

class MobileController extends Controller
{
    public $enableCsrfValidation = false; // Disable CSRF validation for this controller

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Remove CSRF validation for API requests
        unset($behaviors['authenticator']);

        // Add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => ['*'], // Or restrict to specific domains: ['http://yourdomain.com']
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ],
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // Set JSON response format for API endpoints
        if (in_array($action->id, [
            'telemetry',
            'averaged-telemetry',
            'sync-status',
            'save-scrape-data',
            'save-myfxbook-data',
            'save-investing-data',
            'get-scrape-data',
            'get-latest-events',
            'get-high-impact-events',
            'get-technical-analysis',
            'get-interest-rates',
            'get-statistics'
        ])) {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }

        return parent::beforeAction($action);
    }

    public function actionSaveScrapeData()
    {
        $startTime = microtime(true);
        $response = ['success' => false, 'message' => ''];

        try {
            // Get raw JSON input
            $rawData = Yii::$app->request->getRawBody();
            $jsonData = json_decode($rawData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON data: ' . json_last_error_msg());
            }

            // Validate required structure
            if (!isset($jsonData['metadata']) || !isset($jsonData['data'])) {
                throw new \Exception('Missing required fields: metadata and data');
            }

            // Validate metadata
            if (!isset($jsonData['metadata']['url'])) {
                throw new \Exception('Missing url in metadata');
            }

            // Validate data structure has at least one data type
            $hasData = isset($jsonData['data']['technicalAnalysis']) ||
                isset($jsonData['data']['economicCalendar']) ||
                isset($jsonData['data']['interestRates']);

            if (!$hasData) {
                throw new \Exception('No data found in data section');
            }

            // Extract pair and timeframe from URL
            $url = $jsonData['metadata']['url'];
            $extracted = ScrapedDataLog::extractPairAndTimeframe($url);
            $pair = $extracted['pair'];
            $timeframe = $extracted['timeframe'];

            // Process and save the data to application tables
            $processingResult = true; //$this->processAndSaveData($jsonData, $pair, $timeframe);

            // Log the API request
            $responseTime = microtime(true) - $startTime;
            ScrapedDataLog::logScrapedData(
                'scraped-data/save',
                'POST',
                $jsonData,
                $pair,
                $timeframe,
                200,
                $responseTime
            );

            // Send success notification to Telegram if configured
            $this->sendSuccessScrapedDataNotification($jsonData, $pair, $timeframe, $processingResult);

            $response = [
                'success' => true,
                'message' => 'Scraped data saved successfully',
                'data' => [
                    'pair' => $pair,
                    'timeframe' => $timeframe,
                    'url' => $url,
                    'scrape_timestamp' => $jsonData['metadata']['scrapeTimestamp'] ?? null,
                    'processing_result' => $processingResult,
                    'response_time' => round($responseTime, 3) . 's'
                ]
            ];
        } catch (\Exception $e) {
            // Log error
            $responseTime = microtime(true) - $startTime;

            // Try to extract pair and timeframe from URL even on error
            $pair = 'ERROR';
            $timeframe = 'ERROR';
            try {
                if (isset($jsonData['metadata']['url'])) {
                    $extracted = ScrapedDataLog::extractPairAndTimeframe($jsonData['metadata']['url']);
                    $pair = $extracted['pair'];
                    $timeframe = $extracted['timeframe'];
                }
            } catch (\Exception $ex) {
                // Ignore extraction error
            }

            ScrapedDataLog::logScrapedData(
                'scraped-data/save',
                'POST',
                $rawData,
                $pair,
                $timeframe,
                400,
                $responseTime
            );

            // Send error notification to Telegram
            TelegramHelper::sendSimpleError(
                "Scraped data save failed: " . $e->getMessage(),
                "Pair: {$pair}, Timeframe: {$timeframe}"
            );

            Yii::error('Scraped Data API Error: ' . $e->getMessage());

            $response = [
                'success' => false,
                'message' => 'Error processing scraped data: ' . $e->getMessage(),
                'error_code' => 400,
                'pair' => $pair,
                'timeframe' => $timeframe
            ];

            Yii::$app->response->statusCode = 400;
        }

        return $response;
    }

    private function sendSuccessScrapedDataNotification($data, $pair, $timeframe, $processingResult)
    {
        $metadata = $data['metadata'];
        $technicalAnalysis = $data['data']['technicalAnalysis'] ?? null;

        if ($technicalAnalysis) {
            $summary = $technicalAnalysis['technicalSummary'] ?? 'No summary';
            $buyCount = $technicalAnalysis['counts']['buy'] ?? 0;
            $sellCount = $technicalAnalysis['counts']['sell'] ?? 0;
            $neutralCount = $technicalAnalysis['counts']['neutral'] ?? 0;

            $message = "✅ <b>Scraped Data Saved Successfully</b>\n";
            $message .= "📊 <b>Pair:</b> {$pair}-{$timeframe}\n";
            $message .= "📈 <b>Technical Summary:</b> {$summary}\n";
            $message .= "🔍 <b>Signals:</b> 🟢 {$buyCount} | 🔴 {$sellCount} | ⚪ {$neutralCount}\n";
            $message .= "🕐 <b>Scraped:</b> " . date('H:i:s', strtotime($metadata['scrapeTimestamp'])) . "\n";
            $message .= "🌐 <b>URL:</b> " . $metadata['url'];

            TelegramHelper::sendSimpleMessage($message);
        }
    }


    public function actionSaveScrapeData2()
    {
        $startTime = microtime(true);
        $requestData = Yii::$app->request->getRawBody();
        $jsonData = json_decode($requestData, true);

        // Log request
        MyfxbookApiLog::logRequest('save-scrape-data', 'POST', $jsonData);

        try {
            // Validate request
            if (empty($jsonData)) {
                throw new BadRequestHttpException('Invalid JSON data');
            }

            if (!isset($jsonData['data'])) {
                throw new BadRequestHttpException('Missing data section');
            }

            // Save scraped data
            $success = MyfxbookScrapedData::saveScrapedData($jsonData);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($success) {
                // Get the last inserted ID
                $scrapeId = Yii::$app->db->getLastInsertID();

                // Send Telegram notification for high impact events
                $this->sendHighImpactNotification($jsonData);

                $response = [
                    'success' => true,
                    'message' => 'Scraped data saved successfully',
                    'scrapeId' => $scrapeId,
                    'responseTime' => $responseTime
                ];

                // Update log with success
                MyfxbookApiLog::logRequest('save-scrape-data', 'POST', $jsonData, 200, $responseTime);

                return $response;
            } else {
                throw new \Exception('Failed to save scraped data');
            }
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Update log with error
            MyfxbookApiLog::logRequest('save-scrape-data', 'POST', $jsonData, 500, $responseTime);

            Yii::error('Error saving scrape data: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to save scraped data: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'responseTime' => $responseTime
            ];
        }
    }

    public function actionGetAllScrapedDataV2()
    {
        try {
            // Get query parameters
            $request = Yii::$app->request;
            $limit = $request->get('limit', 50);
            $offset = $request->get('offset', 0);
            $pair = $request->get('pair');
            $timeframe = $request->get('timeframe');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $sort = $request->get('sort', 'desc'); // asc or desc

            // Validate limit
            $limit = min(max(1, $limit), 1000); // Cap at 1000 records

            // Build query
            $query = DualSourceScrapedData::find();

            // Apply filters
            if ($pair) {
                $query->andWhere(['pair' => $pair]);
            }

            if ($timeframe) {
                $query->andWhere(['timeframe' => $timeframe]);
            }

            if ($dateFrom) {
                $query->andWhere(['>=', 'created_at', $dateFrom]);
            }

            if ($dateTo) {
                $query->andWhere(['<=', 'created_at', $dateTo]);
            }

            // Apply sorting
            $orderBy = ($sort === 'asc') ? 'created_at ASC' : 'created_at DESC';
            $query->orderBy($orderBy);

            // Get total count for pagination
            $totalCount = $query->count();

            // Apply pagination
            $query->limit($limit)->offset($offset);

            // Execute query
            $data = $query->all();

            // Format response
            $formattedData = [];
            foreach ($data as $record) {
                $formattedData[] = $this->formatScrapedDataRecord($record);
            }

            $response = [
                'success' => true,
                'data' => $formattedData,
                'pagination' => [
                    'total' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount
                ],
                'filters' => [
                    'pair' => $pair,
                    'timeframe' => $timeframe,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'sort' => $sort
                ]
            ];

            return $response;
        } catch (\Exception $e) {
            Yii::error('Get All Scraped Data V2 Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error retrieving scraped data: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    public function actionGetScrapedDataV2($id)
    {
        try {
            // Find record
            $record = DualSourceScrapedData::findOne($id);

            if (!$record) {
                return [
                    'success' => false,
                    'message' => 'Scraped data not found',
                    'error_code' => 404
                ];
            }

            // Format response with full data
            $response = [
                'success' => true,
                'data' => $this->formatScrapedDataRecord($record, true) // true = include full data
            ];

            return $response;
        } catch (\Exception $e) {
            Yii::error('Get Scraped Data V2 Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error retrieving scraped data: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * Get latest scraped data V2 - Updated to match web page requirements
     * 
     * Endpoint: GET /mobile/get-latest-scrape-data-v2
     * Parameters: pair, timeframe (optional)
     */
    public function actionGetLatestScrapeDataV2()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $startTime = microtime(true);

        try {
            // Get query parameters
            $request = Yii::$app->request;
            $pair = $request->post('pair', $request->get('pair', 'EURJPY'));
            $timeframe = $request->post('timeframe', $request->get('timeframe', 'H4'));
            // $hours = $request->get('hours', 24);

            // Convert pair format if needed (e.g., EURJPY to EUR/JPY)
            $pair = str_replace('/', '', $pair); // Remove existing slashes
            // if (strlen($pair) == 6) {
            //     $pair = substr($pair, 0, 3) . '/' . substr($pair, 3, 3);
            // }

            // Convert timeframe format if needed
            $timeframe = strtoupper($timeframe);
            if (is_numeric($timeframe)) {
                // Convert numeric to H format (e.g., 240 to H4)
                if ($timeframe == 240) $timeframe = 'H4';
                elseif ($timeframe == 60) $timeframe = 'H1';
                elseif ($timeframe == 30) $timeframe = 'M30';
                elseif ($timeframe == 15) $timeframe = 'M15';
                elseif ($timeframe == 'D') $timeframe = 'D1';
                elseif ($timeframe == 'W') $timeframe = 'W1';
            }

            // Calculate date threshold
            // $dateThreshold = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

            // Find latest record
            $record = DualSourceScrapedData::find()
                ->where(['pair' => $pair, 'timeframe' => $timeframe])
                // ->andWhere(['>=', 'created_at', $dateThreshold])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if (!$record) {
                return [
                    'success' => false,
                    'message' => 'No recent scraped data found for specified parameters',
                    'error_code' => 404,
                    'details' => [
                        'pair' => $pair,
                        'timeframe' => $timeframe,
                        'hours' => $hours,
                        'threshold' => $dateThreshold
                    ],
                    'suggestions' => [
                        'Try different pair/timeframe',
                        'Check if data exists in database',
                        'Verify scrapers are running'
                    ]
                ];
            }

            // Format response for web page consumption
            $responseData = $this->formatScrapedDataForWeb($record);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log successful request
            Yii::info("V2 Scrape Data fetched - Pair: {$pair}, Timeframe: {$timeframe}, Response time: {$responseTime}ms");

            return [
                'success' => true,
                'message' => 'Latest scraped data retrieved successfully',
                'data' => $responseData,
                'metadata' => [
                    'pair' => $pair,
                    'timeframe' => $timeframe,
                    'record_id' => $record->id,
                    'retrieved_at' => date('Y-m-d H:i:s'),
                    'response_time_ms' => $responseTime,
                    'data_age_seconds' => time() - strtotime($record->created_at)
                ]
            ];
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Yii::error('Get Latest Scrape Data V2 Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error retrieving latest scraped data: ' . $e->getMessage(),
                'error_code' => 500,
                'response_time_ms' => $responseTime,
                'trace' => YII_DEBUG ? $e->getTraceAsString() : null
            ];
        }
    }

    /**
     * Format scraped data for web page consumption
     */
    private function formatScrapedDataForWeb($record)
    {
        $data = [
            'id' => $record->id,
            'pair' => $record->pair,
            'timeframe' => $record->timeframe,
            'created_at' => $record->created_at,
            'scrape_timestamp' => $record->scrape_timestamp,
            'combined_at' => $record->combined_at,
            'has_investing_data' => !empty($record->investing_data),
            'has_myfxbook_data' => !empty($record->myfxbook_data),
        ];

        // Parse investing.com data if available
        if (!empty($record->investing_data)) {
            $investingData = json_decode($record->investing_data, true);
            if ($investingData) {
                $data['investing'] = $this->extractInvestingAnalysis($investingData);
            }
        }

        // Parse myfxbook data if available
        if (!empty($record->myfxbook_data)) {
            $myfxbookData = json_decode($record->myfxbook_data, true);
            if ($myfxbookData) {
                $data['myfxbook'] = $this->extractMyfxbookAnalysis($myfxbookData);
            }
        }

        // Parse combined data
        if (!empty($record->combined_data)) {
            $combinedData = json_decode($record->combined_data, true);
            if ($combinedData) {
                $data['combined'] = $this->extractCombinedAnalysis($combinedData);
            }
        }

        return $data;
    }

    /**
     * Extract technical analysis from investing.com data
     */
    private function extractInvestingAnalysis($investingData)
    {
        $analysis = [
            'technicalSummary' => $investingData['overall_signal'] ?? 'Neutral',
            'patterns' => [],
            'counts' => ['buy' => 0, 'sell' => 0, 'neutral' => 0],
            'totalPatterns' => 0
        ];

        // Extract from sections if available
        if (isset($investingData['sections']) && is_array($investingData['sections'])) {
            foreach ($investingData['sections'] as $sectionName => $sectionData) {
                if (isset($sectionData['rows']) && is_array($sectionData['rows'])) {
                    foreach ($sectionData['rows'] as $row) {
                        if (isset($row['name']) && isset($row['action'])) {
                            $pattern = [
                                'name' => $row['name'],
                                'signal' => strtolower($row['action'])
                            ];

                            $analysis['patterns'][] = $pattern;
                            $analysis['totalPatterns']++;

                            if (strtolower($row['action']) == 'buy') {
                                $analysis['counts']['buy']++;
                            } elseif (strtolower($row['action']) == 'sell') {
                                $analysis['counts']['sell']++;
                            } else {
                                $analysis['counts']['neutral']++;
                            }
                        }
                    }
                }
            }
        }

        return $analysis;
    }

    /**
     * Extract technical analysis from myfxbook data
     */
    private function extractMyfxbookAnalysis($myfxbookData)
    {
        $analysis = [
            'technicalSummary' => $myfxbookData['technical_summary'] ?? 'Neutral',
            'patterns' => [],
            'counts' => ['buy' => 0, 'sell' => 0, 'neutral' => 0],
            'totalPatterns' => 0
        ];

        // Extract from sections if available
        if (isset($myfxbookData['sections']) && is_array($myfxbookData['sections'])) {
            foreach ($myfxbookData['sections'] as $sectionName => $sectionData) {
                if (isset($sectionData['rows']) && is_array($sectionData['rows'])) {
                    foreach ($sectionData['rows'] as $row) {
                        if (isset($row['name']) && isset($row['action'])) {
                            $pattern = [
                                'name' => $row['name'],
                                'signal' => strtolower($row['action'])
                            ];

                            $analysis['patterns'][] = $pattern;
                            $analysis['totalPatterns']++;

                            if (strtolower($row['action']) == 'buy') {
                                $analysis['counts']['buy']++;
                            } elseif (strtolower($row['action']) == 'sell') {
                                $analysis['counts']['sell']++;
                            } else {
                                $analysis['counts']['neutral']++;
                            }
                        }
                    }
                }
            }
        }

        return $analysis;
    }

    /**
     * Extract combined analysis
     */
    private function extractCombinedAnalysis($combinedData)
    {
        return [
            'technicalAnalysis' => $this->mergeTechnicalAnalysis(
                $this->extractInvestingAnalysis($combinedData['investing_com'] ?? []),
                $this->extractMyfxbookAnalysis($combinedData['myfxbook'] ?? [])
            ),
            'interestRates' => $this->extractInterestRates($combinedData),
            'economicCalendar' => $this->extractEconomicCalendar($combinedData)
        ];
    }

    /**
     * Merge technical analysis from both sources
     */
    private function mergeTechnicalAnalysis($investing, $myfxbook)
    {
        // Prioritize myfxbook if available, otherwise use investing
        if (!empty($myfxbook['patterns'])) {
            return $myfxbook;
        }

        return $investing;
    }

    /**
     * Extract interest rates data
     */
    private function extractInterestRates($combinedData)
    {
        $interestRates = [];

        // Check if interest rates exist in either source
        if (isset($combinedData['myfxbook']['sections']['interest_rates'])) {
            $section = $combinedData['myfxbook']['sections']['interest_rates'];
            if (isset($section['rows']) && is_array($section['rows'])) {
                foreach ($section['rows'] as $row) {
                    if (isset($row['country']) && isset($row['current_rate'])) {
                        $interestRates[] = [
                            'country' => $row['country'],
                            'centralBank' => $row['central_bank'] ?? '',
                            'currentRate' => $row['current_rate'],
                            'previousRate' => $row['previous_rate'] ?? '',
                            'nextMeeting' => $row['next_meeting'] ?? '',
                            'change' => $row['change'] ?? '',
                            'outlook' => $row['outlook'] ?? ''
                        ];
                    }
                }
            }
        }

        return $interestRates;
    }

    /**
     * Extract economic calendar data
     */
    private function extractEconomicCalendar($combinedData)
    {
        $events = [];

        // Check if economic calendar exists in either source
        if (isset($combinedData['myfxbook']['sections']['economic_calendar'])) {
            $section = $combinedData['myfxbook']['sections']['economic_calendar'];
            if (isset($section['rows']) && is_array($section['rows'])) {
                foreach ($section['rows'] as $row) {
                    if (isset($row['time']) && isset($row['event'])) {
                        $events[] = [
                            'time' => $row['time'],
                            'currency' => $row['currency'] ?? '',
                            'event' => $row['event'],
                            'previous' => $row['previous'] ?? '',
                            'forecast' => $row['forecast'] ?? '',
                            'actual' => $row['actual'] ?? '',
                            'impact' => $row['impact'] ?? 'medium',
                            'country' => $row['country'] ?? ''
                        ];
                    }
                }
            }
        }

        return [
            'events' => $events,
            'totalEvents' => count($events),
            'highImpactEvents' => array_filter($events, function ($event) {
                return ($event['impact'] ?? '') == 'high';
            })
        ];
    }

    public function actionGetScrapedDataStatsV2()
    {
        try {
            // Get query parameters
            $request = Yii::$app->request;
            $pair = $request->get('pair');
            $timeframe = $request->get('timeframe');
            $days = $request->get('days', 7); // Last X days

            $dateThreshold = date('Y-m-d 00:00:00', strtotime("-{$days} days"));

            // Build base query
            $query = DualSourceScrapedData::find()
                ->where(['>=', 'created_at', $dateThreshold]);

            if ($pair) {
                $query->andWhere(['pair' => $pair]);
            }

            if ($timeframe) {
                $query->andWhere(['timeframe' => $timeframe]);
            }

            // Get total count
            $totalCount = $query->count();

            // Get count by pair
            $byPair = DualSourceScrapedData::find()
                ->select(['pair', 'COUNT(*) as count'])
                ->where(['>=', 'created_at', $dateThreshold])
                ->groupBy(['pair'])
                ->orderBy(['count' => SORT_DESC])
                ->asArray()
                ->all();

            // Get count by timeframe
            $byTimeframe = DualSourceScrapedData::find()
                ->select(['timeframe', 'COUNT(*) as count'])
                ->where(['>=', 'created_at', $dateThreshold])
                ->groupBy(['timeframe'])
                ->orderBy(['count' => SORT_DESC])
                ->asArray()
                ->all();

            // Get daily counts
            $dailyCounts = DualSourceScrapedData::find()
                ->select([
                    'DATE(created_at) as date',
                    'COUNT(*) as count',
                    'SUM(CASE WHEN investing_data IS NOT NULL THEN 1 ELSE 0 END) as investing_count',
                    'SUM(CASE WHEN myfxbook_data IS NOT NULL THEN 1 ELSE 0 END) as myfxbook_count'
                ])
                ->where(['>=', 'created_at', $dateThreshold])
                ->groupBy(['DATE(created_at)'])
                ->orderBy(['date' => SORT_DESC])
                ->asArray()
                ->all();

            // Get latest record timestamp
            $latestRecord = DualSourceScrapedData::find()
                ->select(['created_at', 'pair', 'timeframe'])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            $response = [
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_records' => $totalCount,
                        'date_range' => [
                            'from' => $dateThreshold,
                            'to' => date('Y-m-d H:i:s'),
                            'days' => $days
                        ],
                        'latest_scrape' => $latestRecord ? [
                            'timestamp' => $latestRecord->created_at,
                            'pair' => $latestRecord->pair,
                            'timeframe' => $latestRecord->timeframe
                        ] : null
                    ],
                    'by_pair' => $byPair,
                    'by_timeframe' => $byTimeframe,
                    'daily_counts' => $dailyCounts
                ]
            ];

            return $response;
        } catch (\Exception $e) {
            Yii::error('Get Scraped Data Stats V2 Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error retrieving scraped data statistics: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    public function actionGetLatestScrapeData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $pair = Yii::$app->request->get('pair', 'EURJPY');
            $timeframe = Yii::$app->request->get('timeframe', 'H4');
            return [
                'success' => true,
                'data' => ScrapedDataLog::getLatestData($pair, $timeframe)
            ];
        } catch (\Exception $e) {

            Yii::error('Error getting scrape data: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get scraped data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get scraped data by ID or latest
     * 
     * Endpoint: GET /mobile/get-scrape-data?id=123
     * Endpoint: GET /mobile/get-scrape-data?limit=10
     * Endpoint: GET /mobile/get-scrape-data?date=2024-01-07
     * Endpoint: GET /mobile/get-scrape-data?startDate=2024-01-01&endDate=2024-01-07
     * 
     * Response:
     * {
     *   "success": true,
     *   "data": [...],
     *   "total": 10
     * }
     */
    public function actionGetScrapeData()
    {
        $startTime = microtime(true);

        try {
            $id = Yii::$app->request->get('id');
            $limit = Yii::$app->request->get('limit', 10);
            $date = Yii::$app->request->get('date');
            $startDate = Yii::$app->request->get('startDate');
            $endDate = Yii::$app->request->get('endDate');

            if ($id) {
                // Get specific scrape by ID
                $data = MyfxbookScrapedData::find()
                    ->with(['economicEvents', 'technicalPatterns', 'technicalSummary', 'interestRates'])
                    ->where(['id' => $id])
                    ->one();

                $result = $data ? [$data] : [];
                $total = $data ? 1 : 0;
            } elseif ($date) {
                // Get scrapes for specific date
                $result = MyfxbookScrapedData::getScrapesByDateRange($date . ' 00:00:00', $date . ' 23:59:59');
                $total = count($result);
            } elseif ($startDate && $endDate) {
                // Get scrapes for date range
                $result = MyfxbookScrapedData::getScrapesByDateRange($startDate . ' 00:00:00', $endDate . ' 23:59:59');
                $total = count($result);
            } else {
                // Get latest scrapes
                $result = MyfxbookScrapedData::getLatestScrapes($limit);
                $total = count($result);
            }

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Format response
            $formattedData = [];
            foreach ($result as $scrape) {
                $formattedData[] = $this->formatScrapeData($scrape);
            }

            // Log request
            MyfxbookApiLog::logRequest('get-scrape-data', 'GET', $_GET, 200, $responseTime);

            return [
                'success' => true,
                'data' => $formattedData,
                'total' => $total,
                'responseTime' => $responseTime
            ];
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log error
            MyfxbookApiLog::logRequest('get-scrape-data', 'GET', $_GET, 500, $responseTime);

            Yii::error('Error getting scrape data: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get scraped data: ' . $e->getMessage(),
                'responseTime' => $responseTime
            ];
        }
    }

    /**
     * Get latest economic events
     * 
     * Endpoint: GET /mobile/get-latest-events?currency=EUR&limit=20
     * 
     * Response:
     * {
     *   "success": true,
     *   "events": [...],
     *   "total": 15
     * }
     */
    public function actionGetLatestEvents()
    {
        $startTime = microtime(true);

        try {
            $currency = Yii::$app->request->get('currency');
            $limit = Yii::$app->request->get('limit', 20);

            $events = MyfxbookEconomicEvent::find()
                ->joinWith('scrapeData')
                ->orderBy(['myfxbook_economic_events.created_at' => SORT_DESC])
                ->limit($limit);

            if ($currency) {
                $events->andWhere(['currency' => $currency]);
            }

            $events = $events->all();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log request
            MyfxbookApiLog::logRequest('get-latest-events', 'GET', $_GET, 200, $responseTime);

            return [
                'success' => true,
                'events' => $events,
                'total' => count($events),
                'responseTime' => $responseTime
            ];
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log error
            MyfxbookApiLog::logRequest('get-latest-events', 'GET', $_GET, 500, $responseTime);

            Yii::error('Error getting latest events: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get events: ' . $e->getMessage(),
                'responseTime' => $responseTime
            ];
        }
    }

    /**
     * Get high impact events
     * 
     * Endpoint: GET /mobile/get-high-impact-events?currency=USD&limit=10
     * 
     * Response:
     * {
     *   "success": true,
     *   "events": [...],
     *   "total": 8
     * }
     */
    public function actionGetHighImpactEvents()
    {
        $startTime = microtime(true);

        try {
            $currency = Yii::$app->request->get('currency');
            $limit = Yii::$app->request->get('limit', 10);

            $events = MyfxbookScrapedData::getHighImpactEvents($currency, $limit);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log request
            MyfxbookApiLog::logRequest('get-high-impact-events', 'GET', $_GET, 200, $responseTime);

            return [
                'success' => true,
                'events' => $events,
                'total' => count($events),
                'responseTime' => $responseTime
            ];
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log error
            MyfxbookApiLog::logRequest('get-high-impact-events', 'GET', $_GET, 500, $responseTime);

            Yii::error('Error getting high impact events: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get high impact events: ' . $e->getMessage(),
                'responseTime' => $responseTime
            ];
        }
    }

    /**
     * Get technical analysis data
     * 
     * Endpoint: GET /mobile/get-technical-analysis?limit=5
     * 
     * Response:
     * {
     *   "success": true,
     *   "technicalAnalysis": [...],
     *   "total": 5
     * }
     */
    public function actionGetTechnicalAnalysis()
    {
        $startTime = microtime(true);

        try {
            $limit = Yii::$app->request->get('limit', 5);

            $scrapes = MyfxbookScrapedData::getLatestScrapes($limit);

            $technicalData = [];
            foreach ($scrapes as $scrape) {
                if ($scrape->technicalSummary) {
                    $technicalData[] = [
                        'scrapeId' => $scrape->id,
                        'scrapeTimestamp' => $scrape->scrape_timestamp,
                        'technicalSummary' => $scrape->technicalSummary->technical_summary,
                        'totalPatterns' => $scrape->technicalSummary->total_patterns,
                        'buyCount' => $scrape->technicalSummary->buy_count,
                        'sellCount' => $scrape->technicalSummary->sell_count,
                        'neutralCount' => $scrape->technicalSummary->neutral_count,
                        'buyPercentage' => $scrape->technicalSummary->getBuyPercentage(),
                        'sellPercentage' => $scrape->technicalSummary->getSellPercentage(),
                        'patterns' => $scrape->technicalPatterns
                    ];
                }
            }

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log request
            MyfxbookApiLog::logRequest('get-technical-analysis', 'GET', $_GET, 200, $responseTime);

            return [
                'success' => true,
                'technicalAnalysis' => $technicalData,
                'total' => count($technicalData),
                'responseTime' => $responseTime
            ];
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log error
            MyfxbookApiLog::logRequest('get-technical-analysis', 'GET', $_GET, 500, $responseTime);

            Yii::error('Error getting technical analysis: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get technical analysis: ' . $e->getMessage(),
                'responseTime' => $responseTime
            ];
        }
    }

    /**
     * Get interest rates
     * 
     * Endpoint: GET /mobile/get-interest-rates?country=United States&limit=10
     * 
     * Response:
     * {
     *   "success": true,
     *   "interestRates": [...],
     *   "total": 5
     * }
     */
    public function actionGetInterestRates()
    {
        $startTime = microtime(true);

        try {
            $country = Yii::$app->request->get('country');
            $limit = Yii::$app->request->get('limit', 10);

            $query = MyfxbookInterestRate::find()
                ->joinWith('scrapeData')
                ->orderBy(['myfxbook_interest_rates.created_at' => SORT_DESC])
                ->limit($limit);

            if ($country) {
                $query->andWhere(['country' => $country]);
            }

            $rates = $query->all();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log request
            MyfxbookApiLog::logRequest('get-interest-rates', 'GET', $_GET, 200, $responseTime);

            return [
                'success' => true,
                'interestRates' => $rates,
                'total' => count($rates),
                'responseTime' => $responseTime
            ];
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log error
            MyfxbookApiLog::logRequest('get-interest-rates', 'GET', $_GET, 500, $responseTime);

            Yii::error('Error getting interest rates: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get interest rates: ' . $e->getMessage(),
                'responseTime' => $responseTime
            ];
        }
    }

    /**
     * Get scraping statistics
     * 
     * Endpoint: GET /mobile/get-statistics?startDate=2024-01-01&endDate=2024-01-07
     * 
     * Response:
     * {
     *   "success": true,
     *   "statistics": [...],
     *   "total": 7
     * }
     */
    public function actionGetStatistics()
    {
        $startTime = microtime(true);

        try {
            $startDate = Yii::$app->request->get('startDate', date('Y-m-d', strtotime('-7 days')));
            $endDate = Yii::$app->request->get('endDate', date('Y-m-d'));

            $statistics = MyfxbookStatistics::getStatisticsByDateRange($startDate, $endDate);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log request
            MyfxbookApiLog::logRequest('get-statistics', 'GET', $_GET, 200, $responseTime);

            return [
                'success' => true,
                'statistics' => $statistics,
                'total' => count($statistics),
                'responseTime' => $responseTime
            ];
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log error
            MyfxbookApiLog::logRequest('get-statistics', 'GET', $_GET, 500, $responseTime);

            Yii::error('Error getting statistics: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage(),
                'responseTime' => $responseTime
            ];
        }
    }

    /**
     * Format scrape data for response
     *
     * @param MyfxbookScrapedData $scrape
     * @return array
     */
    private function formatScrapeData($scrape)
    {
        return [
            'id' => $scrape->id,
            'scrapeTimestamp' => $scrape->scrape_timestamp,
            'scrapeNumber' => $scrape->scrape_number,
            'url' => $scrape->url,
            'refreshInterval' => $scrape->refresh_interval,
            'createdAt' => $scrape->created_at,
            'economicEvents' => $scrape->economicEvents,
            'technicalAnalysis' => $scrape->technicalSummary ? [
                'technicalSummary' => $scrape->technicalSummary->technical_summary,
                'totalPatterns' => $scrape->technicalSummary->total_patterns,
                'buyCount' => $scrape->technicalSummary->buy_count,
                'sellCount' => $scrape->technicalSummary->sell_count,
                'neutralCount' => $scrape->technicalSummary->neutral_count,
                'buyPercentage' => $scrape->technicalSummary->getBuyPercentage(),
                'sellPercentage' => $scrape->technicalSummary->getSellPercentage(),
                'patterns' => $scrape->technicalPatterns
            ] : null,
            'interestRates' => $scrape->interestRates
        ];
    }

    /**
     * Send Telegram notification for high impact events
     *
     * @param array $scrapeData
     * @return void
     */
    private function sendHighImpactNotification($scrapeData)
    {
        try {
            $highImpactEvents = [];

            if (isset($scrapeData['data']['economicCalendar']['events'])) {
                foreach ($scrapeData['data']['economicCalendar']['events'] as $event) {
                    if (($event['impact'] ?? '') === 'high') {
                        $highImpactEvents[] = $event;
                    }
                }
            }

            if (!empty($highImpactEvents)) {
                $message = "🔔 *HIGH IMPACT EVENTS DETECTED*\n\n";

                foreach ($highImpactEvents as $event) {
                    $message .= "⏰ *Time:* {$event['time']}\n";
                    $message .= "💰 *Currency:* {$event['currency']}\n";
                    $message .= "📅 *Event:* {$event['event']}\n";
                    $message .= "📊 *Previous:* {$event['previous']}\n";
                    $message .= "🎯 *Forecast:* {$event['forecast']}\n";
                    $message .= "📍 *Country:* {$event['country']}\n\n";
                }

                // Send via TelegramHelper if available
                if (class_exists('app\helpers\TelegramHelper')) {
                    TelegramHelper::sendSimpleMessage($message);
                }
            }
        } catch (\Exception $e) {
            Yii::error('Error sending Telegram notification: ' . $e->getMessage());
        }
    }

    public function actionIndex()
    {
        Yii::debug('debug index mobile controller'); // Use Yii's logging
        return 'index';
    }

    public function actionLogin()
    {
        // Add CORS headers
        Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
        Yii::$app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        Yii::$app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        // Handle OPTIONS request (preflight)
        if (Yii::$app->request->isOptions) {
            Yii::$app->response->statusCode = 200;
            return;
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Get POST data
            $request = Yii::$app->request;
            $username = $request->post('username');
            $password = $request->post('password');

            // Validate input
            if (empty($username) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Username and password are required',
                    'token' => null
                ];
            }

            // Create login form model
            $model = new LoginForm();
            $model->user_name = $username;
            $model->user_pass = $password;

            // Attempt login
            if ($model->login()) {
                // Get the logged-in user
                $user = Yii::$app->user->identity;

                // Generate JWT token
                $token = $this->generateJwtToken($user);

                // Log the login activity
                $clientIp = \app\helpers\CustomHelper::get_client_ip() ?? 'localhost';

                TelegramHelper::sendSimpleMessage(
                    [
                        'text' => "Mobile User Login : " . $model->user_name . "\nFrom : " . $clientIp,
                        'parse_mode' => 'html'
                    ],
                    Yii::$app->params['group_id']
                );

                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => [
                        'id' => $user->user_id,
                        'name' => $user->user_nama,
                        'username' => $user->user_name,
                        'user_tipe' => $user->user_tipe,
                        'user_email' => $user->user_email,
                        'photo' => $user->user_foto,
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid username or password',
                    'token' => null
                ];
            }
        } catch (\Exception $e) {
            Yii::error('Mobile login error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.',
                'token' => null
            ];
        }
    }

    /**
     * Generate JWT token for user
     */
    private function generateJwtToken($user)
    {
        $secret = Yii::$app->params['jwtSecret'] ?? 'Ju5TS0m3!2@nd0M';
        $issuedAt = time();
        $expire = $issuedAt + (60 * 60 * 24 * 7); // Token valid for 7 days

        $payload = [
            'iss' => 'IskandarMudaGreen', // Issuer
            'aud' => 'Johor Bahru', // Audience
            'iat' => $issuedAt, // Issued at
            'exp' => $expire, // Expire time
            'data' => [
                'id' => $user->user_id,
                'name' => $user->user_nama,
                'username' => $user->user_name,
                'user_tipe' => $user->user_tipe,
                'user_email' => $user->user_email,
                'photo' => $user->user_foto
            ]
        ];

        $token = JwtHelper::encode($payload, $secret);
        return $token;
    }

    public function actionValidateToken()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $request = Yii::$app->request;
            $token = $request->post('token') ?? $request->get('token');

            if (empty($token)) {
                return [
                    'success' => false,
                    'message' => 'Token is required',
                    'valid' => false
                ];
            }

            $isValid = $this->validateJwtToken($token);

            return [
                'success' => true,
                'valid' => $isValid,
                'message' => $isValid ? 'Token is valid' : 'Token is invalid or expired'
            ];
        } catch (\Exception $e) {
            Yii::error('Token validation error: ' . $e->getMessage());
            return [
                'success' => false,
                'valid' => false,
                'message' => 'Token validation failed'
            ];
        }
    }

    /**
     * Validate JWT token
     */
    private function validateJwtToken($token)
    {
        $secret = Yii::$app->params['jwtSecret'] ?? 'Ju5TS0m3!2@nd0M';

        // Validate token
        try {
            $decodedPayload = JwtHelper::validate($token, $secret);
            return time() < $decodedPayload['exp'];
        } catch (\Exception $e) {
            Yii::error('JWT validation error: ' . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    // NEW ENDPOINTS FOR AVERAGED TELEMETRY DATA
    // ============================================================

    /**
     * Endpoint to receive averaged telemetry data
     * POST /mobile/averaged-telemetry
     */
    public function actionAveragedTelemetry()
    {
        $request = Yii::$app->request;

        try {
            // Validate request method
            if (!$request->isPost) {
                return [
                    'success' => false,
                    'message' => 'Method not allowed. Use POST.',
                    'code' => 405
                ];
            }

            // Check content type and parse accordingly
            $contentType = $request->getContentType();
            $postData = [];

            if (strpos($contentType, 'application/json') !== false) {
                // For JSON requests
                $rawBody = $request->getRawBody();
                if (!empty($rawBody)) {
                    $postData = json_decode($rawBody, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return [
                            'success' => false,
                            'message' => 'Invalid JSON format: ' . json_last_error_msg(),
                            'code' => 400
                        ];
                    }
                }
            } else {
                // For form-urlencoded or multipart form data
                $postData = $request->post();
            }

            // Get client info
            $clientIp = \app\helpers\CustomHelper::get_client_ip() ?? 'localhost';
            $userAgent = $request->getUserAgent() ?? 'Unknown';

            // Log incoming request
            Yii::info('Averaged telemetry data received from IP: ' . $clientIp);

            // Send Telegram notification about endpoint hit
            $this->sendEndpointHitNotification($postData, $clientIp, $userAgent);

            // Validate required fields
            $requiredFields = ['device_id', 'device_name', 'data_timestamp', 'sample_count'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!isset($postData[$field]) || (is_string($postData[$field]) && trim($postData[$field]) === '')) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                // Send Telegram notification for missing fields error
                $this->sendErrorNotification($postData, $clientIp, 'Missing required fields: ' . implode(', ', $missingFields));

                return [
                    'success' => false,
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields),
                    'code' => 400
                ];
            }

            // Extract data
            $telemetryData = $this->extractTelemetryData($postData);

            // Save to database
            $saveResult = $this->saveTelemetryData($postData, $telemetryData, $clientIp);

            if ($saveResult === true) {
                // Send success notification to Telegram
                $this->sendSuccessNotification($postData, $telemetryData, $clientIp);

                return [
                    'success' => true,
                    'message' => 'Telemetry data saved successfully',
                    'data_received' => [
                        'device_id' => $postData['device_id'],
                        'device_name' => $postData['device_name'],
                        'data_timestamp' => $postData['data_timestamp'],
                        'sample_count' => $postData['sample_count']
                    ],
                    'data_timestamp' => date('Y-m-d H:i:s'),
                    'code' => 200
                ];
            } else {
                // Database save failed - send error notification
                $this->sendErrorNotification($postData, $clientIp, $saveResult);

                return [
                    'success' => false,
                    'message' => $saveResult,
                    'code' => 500
                ];
            }
        } catch (\Exception $e) {
            // Send exception notification to Telegram
            $clientIp = \app\helpers\CustomHelper::get_client_ip() ?? 'localhost';
            $this->sendExceptionNotification($e, $clientIp);

            return [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }


    /**
     * Send notification when endpoint is hit
     */
    private function sendEndpointHitNotification($postData, $clientIp, $userAgent)
    {
        try {
            $deviceId = $postData['device_id'] ?? 'Unknown';
            $deviceName = $postData['device_name'] ?? 'Unknown Device';
            $sampleCount = $postData['sample_count'] ?? 0;
            $dataType = $postData['data_type'] ?? 'unknown';

            $message = "📱 <b>Telemetry Endpoint Hit</b>\n\n";
            $message .= "🔧 <b>Device:</b> " . htmlspecialchars($deviceName) . "\n";
            $message .= "🆔 <b>ID:</b> " . htmlspecialchars($deviceId) . "\n";
            $message .= "📊 <b>Samples:</b> " . $sampleCount . "\n";
            $message .= "📝 <b>Type:</b> " . htmlspecialchars($dataType) . "\n";
            $message .= "🌐 <b>From IP:</b> " . $clientIp . "\n";
            $message .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";

            // Shorten user agent if too long
            $shortAgent = strlen($userAgent) > 50 ? substr($userAgent, 0, 50) . '...' : $userAgent;
            $message .= "🤖 <b>Agent:</b> " . htmlspecialchars($shortAgent) . "\n";

            TelegramHelper::sendSimpleMessage(
                [
                    'text' => $message,
                    'parse_mode' => 'html'
                ],
                Yii::$app->params['group_id']
            );

            Yii::info('Telegram notification sent: Endpoint hit');
        } catch (\Exception $e) {
            Yii::error('Failed to send Telegram endpoint notification: ' . $e->getMessage());
        }
    }

    /**
     * Send success notification to Telegram
     */
    private function sendSuccessNotification($postData, $telemetryData, $clientIp)
    {
        try {
            $deviceId = $postData['device_id'];
            $deviceName = $postData['device_name'];
            $sampleCount = $postData['sample_count'];
            $dataTimestamp = $postData['data_timestamp'];

            // Format the timestamp nicely
            $formattedTime = date('Y-m-d H:i:s', strtotime($dataTimestamp));

            $message = "✅ <b>Telemetry Data Saved Successfully</b>\n\n";
            $message .= "🔧 <b>Device:</b> " . htmlspecialchars($deviceName) . "\n";
            $message .= "🆔 <b>ID:</b> " . htmlspecialchars($deviceId) . "\n";
            $message .= "📊 <b>Samples:</b> " . $sampleCount . "\n";
            $message .= "🕐 <b>Data Time:</b> " . $formattedTime . "\n";
            $message .= "🌐 <b>From IP:</b> " . $clientIp . "\n";
            $message .= "⏰ <b>Saved At:</b> " . date('Y-m-d H:i:s') . "\n\n";

            // Add key measurements if available
            if (isset($telemetryData['ac_v'])) {
                $message .= "⚡ <b>AC Voltage:</b> " . $telemetryData['ac_v'] . "V\n";
            }

            if (isset($telemetryData['ac_p'])) {
                $message .= "💡 <b>AC Power:</b> " . $telemetryData['ac_p'] . "W\n";
            }

            if (isset($telemetryData['dc_v'])) {
                $message .= "🔋 <b>DC Voltage:</b> " . $telemetryData['dc_v'] . "V\n";
            }

            // Add GPS info if available
            if (isset($telemetryData['gps_fixed']) && $telemetryData['gps_fixed']) {
                $message .= "📍 <b>GPS:</b> Fixed\n";
                if (isset($telemetryData['lat']) && isset($telemetryData['lng'])) {
                    $message .= "🌍 <b>Location:</b> " . $telemetryData['lat'] . ", " . $telemetryData['lng'] . "\n";
                }
            }

            // Add relay status if available
            if (isset($telemetryData['r'])) {
                $relayStatus = $telemetryData['r'] == 1 ? 'ON ✅' : 'OFF ⛔';
                $message .= "🔌 <b>Relay:</b> " . $relayStatus . "\n";
            }

            // Add low voltage warning if present
            if (isset($telemetryData['low_v']) && $telemetryData['low_v']) {
                $message .= "⚠️ <b>Warning:</b> Low Voltage Detected\n";
            }

            TelegramHelper::sendSimpleMessage(
                [
                    'text' => $message,
                    'parse_mode' => 'html'
                ],
                Yii::$app->params['group_id']
            );

            Yii::info('Telegram notification sent: Data saved successfully');
        } catch (\Exception $e) {
            Yii::error('Failed to send Telegram success notification: ' . $e->getMessage());
        }
    }

    /**
     * Send error notification to Telegram
     */
    private function sendErrorNotification($postData, $clientIp, $errorMessage)
    {
        try {
            $deviceId = $postData['device_id'] ?? 'Unknown';
            $deviceName = $postData['device_name'] ?? 'Unknown Device';

            $message = "❌ <b>Telemetry Data Error</b>\n\n";
            $message .= "🔧 <b>Device:</b> " . htmlspecialchars($deviceName) . "\n";
            $message .= "🆔 <b>ID:</b> " . htmlspecialchars($deviceId) . "\n";
            $message .= "🌐 <b>From IP:</b> " . $clientIp . "\n";
            $message .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
            $message .= "📝 <b>Error:</b> " . htmlspecialchars($errorMessage) . "\n";

            TelegramHelper::sendSimpleMessage(
                [
                    'text' => $message,
                    'parse_mode' => 'html'
                ],
                Yii::$app->params['group_id']
            );

            Yii::info('Telegram notification sent: Error occurred');
        } catch (\Exception $e) {
            Yii::error('Failed to send Telegram error notification: ' . $e->getMessage());
        }
    }

    /**
     * Send exception notification to Telegram
     */
    private function sendExceptionNotification($exception, $clientIp)
    {
        try {
            $message = "🚨 <b>Server Exception in Telemetry Endpoint</b>\n\n";
            $message .= "🌐 <b>From IP:</b> " . $clientIp . "\n";
            $message .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
            $message .= "📝 <b>Error:</b> " . htmlspecialchars($exception->getMessage()) . "\n";
            $message .= "📁 <b>File:</b> " . htmlspecialchars($exception->getFile()) . "\n";
            $message .= "🔢 <b>Line:</b> " . $exception->getLine() . "\n";

            // Truncate message if too long
            if (strlen($message) > 4000) {
                $message = substr($message, 0, 4000) . "\n...[truncated]";
            }

            TelegramHelper::sendSimpleMessage(
                [
                    'text' => $message,
                    'parse_mode' => 'html'
                ],
                Yii::$app->params['group_id']
            );

            Yii::info('Telegram notification sent: Server exception');
        } catch (\Exception $e) {
            Yii::error('Failed to send Telegram exception notification: ' . $e->getMessage());
        }
    }

    /**
     * Also update saveTelemetryData to send critical alerts
     */
    private function saveTelemetryData($postData, $telemetryData, $clientIp)
    {
        try {
            $db = Yii::$app->db;

            // Parse data_timestamp
            $dataTimestamp = $postData['data_timestamp'];
            $parsedTimestamp = strtotime($dataTimestamp);

            if ($parsedTimestamp === false) {
                return "Invalid data_timestamp format: " . $dataTimestamp;
            }

            $insertData = [
                'device_id' => $postData['device_id'],
                'device_name' => $postData['device_name'],
                'data_timestamp' => date('Y-m-d H:i:s', $parsedTimestamp),
                'ac_v' => $telemetryData['ac_v'] !== null ? floatval($telemetryData['ac_v']) : null,
                'ac_i' => $telemetryData['ac_i'] !== null ? floatval($telemetryData['ac_i']) : null,
                'ac_p' => $telemetryData['ac_p'] !== null ? floatval($telemetryData['ac_p']) : null,
                'energy' => $telemetryData['energy'] !== null ? floatval($telemetryData['energy']) : null,
                'freq' => $telemetryData['freq'] !== null ? floatval($telemetryData['freq']) : null,
                'pf' => $telemetryData['pf'] !== null ? floatval($telemetryData['pf']) : null,
                'dc_v' => $telemetryData['dc_v'] !== null ? floatval($telemetryData['dc_v']) : null,
                'dc_i' => $telemetryData['dc_i'] !== null ? floatval($telemetryData['dc_i']) : null,
                'low_v' => $telemetryData['low_v'] ? 1 : 0,
                'gps_fixed' => $telemetryData['gps_fixed'] ? 1 : 0,
                'lat' => $telemetryData['lat'] !== null ? floatval($telemetryData['lat']) : null,
                'lng' => $telemetryData['lng'] !== null ? floatval($telemetryData['lng']) : null,
                'relay_state' => intval($telemetryData['r']),
                'sample_count' => intval($postData['sample_count']),
                'sync_type' => $telemetryData['sync_type'],
                'data_type' => $telemetryData['data_type'],
                'buffer_start_time' => $telemetryData['buffer_start_time'],
                'buffer_end_time' => $telemetryData['buffer_end_time'],
                'original_sample_count' => intval($telemetryData['original_sample_count']),
                'client_ip' => $clientIp,
                'user_agent' => Yii::$app->request->getUserAgent(),
            ];

            // Check if table exists
            $tableExists = $db->createCommand("SHOW TABLES LIKE 'telemetry_data'")->queryScalar();
            if (!$tableExists) {
                return "Table 'telemetry_data' does not exist. Please create the table first.";
            }

            // Try to insert
            $command = $db->createCommand()->insert('telemetry_data', $insertData);
            $result = $command->execute();

            if ($result > 0) {
                $lastId = $db->getLastInsertID();
                Yii::info("Telemetry data saved. ID: {$lastId}, Device: {$postData['device_id']}");

                // Check for critical conditions and send alert
                $this->checkCriticalConditions($postData, $telemetryData, $clientIp, $lastId);

                return true;
            } else {
                // Get database error
                $error = $db->createCommand("SHOW ERRORS")->queryOne();
                if ($error) {
                    return "Database error: " . $error['Message'];
                }
                return "Insert failed. No rows affected.";
            }
        } catch (\Exception $e) {
            // Return the error message directly
            return "Database error: " . $e->getMessage();
        }
    }


    /**
     * Check for critical conditions and send alerts
     */
    private function checkCriticalConditions($postData, $telemetryData, $clientIp, $recordId)
    {
        try {
            $criticalEvents = [];

            // Check low voltage
            if ($telemetryData['low_v']) {
                $criticalEvents[] = "⚠️ <b>Low Voltage Warning</b>: " . ($telemetryData['ac_v'] ?? 'N/A') . "V";
            }

            // Check high voltage
            if (isset($telemetryData['ac_v']) && floatval($telemetryData['ac_v']) > 250) {
                $criticalEvents[] = "⚡ <b>High Voltage</b>: " . $telemetryData['ac_v'] . "V";
            }

            // Check high power
            if (isset($telemetryData['ac_p']) && floatval($telemetryData['ac_p']) > 5000) {
                $criticalEvents[] = "💡 <b>High Power</b>: " . $telemetryData['ac_p'] . "W";
            }

            // Check GPS lost
            if (isset($telemetryData['gps_fixed']) && !$telemetryData['gps_fixed']) {
                $criticalEvents[] = "📍 <b>GPS Signal Lost</b>";
            }

            // Send alert if there are critical events
            if (!empty($criticalEvents)) {
                $message = "🚨 <b>Critical Alert - Device: " . htmlspecialchars($postData['device_name']) . "</b>\n\n";
                $message .= "🆔 <b>ID:</b> " . htmlspecialchars($postData['device_id']) . "\n";
                $message .= "📊 <b>Record ID:</b> " . $recordId . "\n";
                $message .= "🌐 <b>From IP:</b> " . $clientIp . "\n";
                $message .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n\n";
                $message .= "<b>Critical Events:</b>\n";
                $message .= implode("\n", $criticalEvents);

                TelegramHelper::sendSimpleMessage(
                    [
                        'text' => $message,
                        'parse_mode' => 'html'
                    ],
                    Yii::$app->params['group_id']
                );

                Yii::info('Critical alert sent to Telegram');
            }
        } catch (\Exception $e) {
            Yii::error('Failed to check critical conditions: ' . $e->getMessage());
        }
    }

    private function extractTelemetryData($postData)
    {
        return [
            'ac_v' => $postData['ac_v'] ?? null,
            'ac_i' => $postData['ac_i'] ?? null,
            'ac_p' => $postData['ac_p'] ?? null,
            'energy' => $postData['energy'] ?? null,
            'freq' => $postData['freq'] ?? null,
            'pf' => $postData['pf'] ?? null,
            'dc_v' => $postData['dc_v'] ?? null,
            'dc_i' => $postData['dc_i'] ?? null,
            'low_v' => $postData['low_v'] ?? false,
            'gps_fixed' => $postData['gps_fixed'] ?? false,
            'lat' => $postData['lat'] ?? null,
            'lng' => $postData['lng'] ?? null,
            'r' => $postData['r'] ?? 0,
            'rt_h' => $postData['rt_h'] ?? 0,
            'rt_m' => $postData['rt_m'] ?? 0,
            'demo' => $postData['demo'] ?? false,
            'pzem_ok' => $postData['pzem_ok'] ?? true,
            'buffer_start_time' => $postData['buffer_start_time'] ?? null,
            'buffer_end_time' => $postData['buffer_end_time'] ?? null,
            'data_type' => $postData['data_type'] ?? 'averaged_telemetry',
            'sync_type' => $postData['sync_type'] ?? 'iteration',
            'original_sample_count' => $postData['original_sample_count'] ?? 1,
            'transport' => $postData['transport'] ?? 'http',
        ];
    }

    private function validateTelemetryData($telemetryData)
    {
        // Validate numeric values
        $numericFields = [
            'ac_v' => ['min' => 0, 'max' => 300],
            'ac_i' => ['min' => 0, 'max' => 100],
            'ac_p' => ['min' => 0, 'max' => 10000],
            'energy' => ['min' => 0, 'max' => 999999],
            'freq' => ['min' => 45, 'max' => 65],
            'pf' => ['min' => 0, 'max' => 1],
            'dc_v' => ['min' => 0, 'max' => 100],
            'dc_i' => ['min' => 0, 'max' => 100],
        ];

        foreach ($numericFields as $field => $limits) {
            if ($telemetryData[$field] !== null) {
                $value = floatval($telemetryData[$field]);
                if (!is_numeric($telemetryData[$field])) {
                    Yii::warning("Invalid numeric value for $field: " . $telemetryData[$field]);
                    return "Field $field must be numeric";
                }
                if ($value < $limits['min'] || $value > $limits['max']) {
                    Yii::warning("Value out of range for $field: " . $value);
                    return "Field $field must be between {$limits['min']} and {$limits['max']}";
                }
            }
        }

        // Validate GPS coordinates if present
        if ($telemetryData['lat'] !== null) {
            $lat = floatval($telemetryData['lat']);
            if ($lat < -90 || $lat > 90) {
                Yii::warning("Invalid latitude: " . $telemetryData['lat']);
                return "Latitude must be between -90 and 90";
            }
        }

        if ($telemetryData['lng'] !== null) {
            $lng = floatval($telemetryData['lng']);
            if ($lng < -180 || $lng > 180) {
                Yii::warning("Invalid longitude: " . $telemetryData['lng']);
                return "Longitude must be between -180 and 180";
            }
        }

        // Validate relay state
        $relayState = intval($telemetryData['r']);
        if (!in_array($relayState, [0, 1])) {
            Yii::warning("Invalid relay state: " . $telemetryData['r']);
            return "Relay state must be 0 or 1";
        }

        return true;
    }

    // Temporary debug action
    public function actionDebugTable()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $db = Yii::$app->db;

        try {
            // Check table
            $tableExists = $db->createCommand("SHOW TABLES LIKE 'telemetry_data'")->queryScalar();

            // Check table structure
            $tableInfo = $db->createCommand("DESCRIBE telemetry_data")->queryAll();

            // Count records
            $recordCount = $tableExists ? $db->createCommand("SELECT COUNT(*) FROM telemetry_data")->queryScalar() : 0;

            return [
                'success' => true,
                'data' => [
                    'table_exists' => $tableExists ? 'Yes' : 'No',
                    'record_count' => $recordCount,
                    'table_structure' => $tableInfo,
                    'current_database' => $db->createCommand("SELECT DATABASE()")->queryScalar(),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }


    /**
     * Send Telegram notification for important telemetry events
     */
    private function sendTelemetryNotification($postData, $telemetryData)
    {
        try {
            $clientIp = \app\helpers\CustomHelper::get_client_ip() ?? 'localhost';

            // Check for critical conditions
            $criticalEvents = [];

            if ($telemetryData['low_v']) {
                $criticalEvents[] = "⚠️ Low Voltage Warning";
            }

            if ($telemetryData['ac_v'] && floatval($telemetryData['ac_v']) > 250) {
                $criticalEvents[] = "⚠️ High AC Voltage: " . $telemetryData['ac_v'] . "V";
            }

            if ($telemetryData['ac_p'] && floatval($telemetryData['ac_p']) > 5000) {
                $criticalEvents[] = "⚠️ High Power Consumption: " . $telemetryData['ac_p'] . "W";
            }

            // Send notification if there are critical events
            if (!empty($criticalEvents)) {
                $message = "🚨 <b>Critical Telemetry Alert</b>\n";
                $message .= "Device: " . $postData['device_name'] . "\n";
                $message .= "ID: " . $postData['device_id'] . "\n";
                $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
                $message .= "Samples: " . $postData['sample_count'] . "\n";
                $message .= "From IP: " . $clientIp . "\n\n";
                $message .= "<b>Events:</b>\n" . implode("\n", $criticalEvents);

                TelegramHelper::sendSimpleMessage(
                    [
                        'text' => $message,
                        'parse_mode' => 'html'
                    ],
                    Yii::$app->params['group_id']
                );
            }

            // Optional: Send periodic summary (e.g., every 100 records)
            static $notificationCount = 0;
            $notificationCount++;

            if ($notificationCount % 100 === 0) {
                $summary = "📊 <b>Telemetry Summary</b>\n";
                $summary .= "Total records processed: $notificationCount\n";
                $summary .= "Latest device: " . $postData['device_name'] . "\n";
                $summary .= "Latest samples: " . $postData['sample_count'] . "\n";
                $summary .= "Time: " . date('Y-m-d H:i:s');

                TelegramHelper::sendSimpleMessage(
                    [
                        'text' => $summary,
                        'parse_mode' => 'html'
                    ],
                    Yii::$app->params['group_id']
                );
            }
        } catch (\Exception $e) {
            Yii::error('Telegram notification error: ' . $e->getMessage());
        }
    }

    /**
     * Endpoint to get sync configuration for mobile app
     * GET /mobile/sync-config
     */
    public function actionSyncConfig()
    {
        try {
            $request = Yii::$app->request;

            // Optional: Validate JWT token
            $token = $request->get('token');
            if ($token && !$this->validateJwtToken($token)) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired token',
                    'code' => 401
                ];
            }

            // Return sync configuration
            return [
                'success' => true,
                'config' => [
                    'server_url' => Yii::$app->params['mobileServerUrl'] ?? Yii::$app->request->hostInfo,
                    'sync_by_iteration' => Yii::$app->params['syncByIteration'] ?? false,
                    'sync_iteration_count' => Yii::$app->params['syncIterationCount'] ?? 10,
                    'sync_interval' => Yii::$app->params['syncInterval'] ?? 60,
                    'telemetry_endpoint' => '/mobile/averaged-telemetry',
                    'max_sample_count' => Yii::$app->params['maxSampleCount'] ?? 100,
                    'timestamp_format' => 'iso8601',
                ],
                'data_timestamp' => date('Y-m-d H:i:s'),
                'code' => 200
            ];
        } catch (\Exception $e) {
            Yii::error('Sync config error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get sync configuration',
                'code' => 500
            ];
        }
    }

    /**
     * Endpoint to get telemetry data statistics
     * GET /mobile/telemetry-stats
     */
    public function actionTelemetryStats()
    {
        try {
            $request = Yii::$app->request;

            // Optional: Validate JWT token
            $token = $request->get('token');
            if ($token && !$this->validateJwtToken($token)) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired token',
                    'code' => 401
                ];
            }

            $db = Yii::$app->db;

            // Get statistics
            $stats = [
                'total_records' => (int)$db->createCommand("SELECT COUNT(*) FROM telemetry_data")->queryScalar(),
                'today_records' => (int)$db->createCommand("SELECT COUNT(*) FROM telemetry_data WHERE DATE(created_at) = CURDATE()")->queryScalar(),
                'unique_devices' => (int)$db->createCommand("SELECT COUNT(DISTINCT device_id) FROM telemetry_data")->queryScalar(),
                'latest_record' => $db->createCommand("SELECT MAX(created_at) FROM telemetry_data")->queryScalar(),
            ];

            return [
                'success' => true,
                'stats' => $stats,
                'data_timestamp' => date('Y-m-d H:i:s'),
                'code' => 200
            ];
        } catch (\Exception $e) {
            Yii::error('Telemetry stats error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get telemetry statistics',
                'code' => 500
            ];
        }
    }

    /**
     * Health check endpoint
     * GET /mobile/health
     */
    public function actionHealth()
    {
        try {
            $db = Yii::$app->db;

            // Check database connection
            $dbStatus = $db->createCommand("SELECT 1")->queryScalar() === '1';

            return [
                'success' => true,
                'status' => 'healthy',
                'services' => [
                    'database' => $dbStatus ? 'connected' : 'disconnected',
                    'api' => 'running',
                    'data_timestamp' => date('Y-m-d H:i:s'),
                ],
                'code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'code' => 503
            ];
        }
    }

    /**
     * Get all devices for the authenticated user
     */
    public function actionUserDevices()
    {
        $user_id = Yii::$app->user->identity->user_id;

        // Get user's devices with optional filters
        $query = UserDevices::find()
            ->where(['user_id' => $user_id])
            ->andWhere(['is_active' => 1]);

        // Optional: Get latest telemetry data for each device
        $devices = $query->all();

        $result = [];
        foreach ($devices as $device) {
            // Get latest telemetry data for this device
            $latestTelemetry = TelemetryData::find()
                ->where(['device_id' => $device->device_id])
                ->orderBy(['data_timestamp' => SORT_DESC])
                ->one();

            $deviceData = [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_alias' => $device->device_alias,
                'device_description' => $device->device_description,
                'created_at' => $device->created_at,
                'updated_at' => $device->updated_at,
            ];

            if ($latestTelemetry) {
                $deviceData['latest_telemetry'] = [
                    'timestamp' => $latestTelemetry->data_timestamp,
                    'ac_power' => $latestTelemetry->ac_p,
                    'ac_voltage' => $latestTelemetry->ac_v,
                    'ac_current' => $latestTelemetry->ac_i,
                    'energy' => $latestTelemetry->energy,
                    'relay_state' => $latestTelemetry->relay_state,
                    'gps_location' => $latestTelemetry->lat && $latestTelemetry->lng ? [
                        'lat' => $latestTelemetry->lat,
                        'lng' => $latestTelemetry->lng
                    ] : null
                ];
            }

            $result[] = $deviceData;
        }

        return [
            'success' => true,
            'data' => $result,
            'count' => count($result)
        ];
    }

    /**
     * Get telemetry data for a specific device by device_id
     */
    public function actionDeviceTelemetry($device_id = null)
    {
        $user_id = Yii::$app->user->identity->user_id;

        if (!$device_id) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'Device ID is required'
            ];
        }

        // Verify that the user owns this device
        $userDevice = UserDevices::findOne([
            'user_id' => $user_id,
            'device_id' => $device_id,
            'is_active' => 1
        ]);

        if (!$userDevice) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Device not found or access denied'
            ];
        }

        $request = Yii::$app->request;

        // Get query parameters for filtering
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $limit = $request->get('limit', 100);
        $offset = $request->get('offset', 0);
        $order = $request->get('order', 'DESC'); // ASC or DESC

        $query = TelemetryData::find()
            ->where(['device_id' => $device_id]);

        // Apply date filters if provided
        if ($startDate) {
            $query->andWhere(['>=', 'data_timestamp', $startDate]);
        }

        if ($endDate) {
            $query->andWhere(['<=', 'data_timestamp', $endDate]);
        }

        // Apply pagination and ordering
        $totalCount = $query->count();
        $telemetryData = $query
            ->orderBy(['data_timestamp' => $order === 'ASC' ? SORT_ASC : SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        $formattedData = [];
        foreach ($telemetryData as $data) {
            $formattedData[] = [
                'id' => $data->id,
                'timestamp' => $data->data_timestamp,
                'ac_voltage' => $data->ac_v,
                'ac_current' => $data->ac_i,
                'ac_power' => $data->ac_p,
                'energy' => $data->energy,
                'frequency' => $data->freq,
                'power_factor' => $data->pf,
                'dc_voltage' => $data->dc_v,
                'dc_current' => $data->dc_i,
                'low_voltage_warning' => (bool)$data->low_v,
                'gps_fixed' => (bool)$data->gps_fixed,
                'latitude' => $data->lat,
                'longitude' => $data->lng,
                'relay_state' => (bool)$data->relay_state,
                'device_name' => $data->device_name,
                'sync_type' => $data->sync_type,
                'data_type' => $data->data_type,
                'created_at' => $data->created_at
            ];
        }

        return [
            'success' => true,
            'device' => [
                'id' => $userDevice->device_id,
                'alias' => $userDevice->device_alias,
                'description' => $userDevice->device_description
            ],
            'data' => $formattedData,
            'pagination' => [
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ];
    }

    /**
     * Get specific telemetry record by ID
     */
    public function actionDeviceTelemetryById($id = null)
    {
        $user_id = Yii::$app->user->identity->user_id;

        if (!$id) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'Telemetry record ID is required'
            ];
        }

        // Find the telemetry record
        $telemetry = TelemetryData::findOne($id);

        if (!$telemetry) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Telemetry record not found'
            ];
        }

        // Verify that the user owns the device associated with this telemetry record
        $userDevice = UserDevices::findOne([
            'user_id' => $user_id,
            'device_id' => $telemetry->device_id,
            'is_active' => 1
        ]);

        if (!$userDevice) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Access denied to this telemetry record'
            ];
        }

        // Format the response
        $formattedData = [
            'id' => $telemetry->id,
            'device_id' => $telemetry->device_id,
            'device_name' => $telemetry->device_name,
            'timestamp' => $telemetry->data_timestamp,
            'ac_voltage' => $telemetry->ac_v,
            'ac_current' => $telemetry->ac_i,
            'ac_power' => $telemetry->ac_p,
            'energy' => $telemetry->energy,
            'frequency' => $telemetry->freq,
            'power_factor' => $telemetry->pf,
            'dc_voltage' => $telemetry->dc_v,
            'dc_current' => $telemetry->dc_i,
            'low_voltage_warning' => (bool)$telemetry->low_v,
            'gps_fixed' => (bool)$telemetry->gps_fixed,
            'latitude' => $telemetry->lat,
            'longitude' => $telemetry->lng,
            'relay_state' => (bool)$telemetry->relay_state,
            'sample_count' => $telemetry->sample_count,
            'sync_type' => $telemetry->sync_type,
            'data_type' => $telemetry->data_type,
            'buffer_start_time' => $telemetry->buffer_start_time,
            'buffer_end_time' => $telemetry->buffer_end_time,
            'device_manufacturer' => $telemetry->device_manufacturer,
            'device_model' => $telemetry->device_model,
            'sync_interval' => $telemetry->sync_interval,
            'sync_iteration_count' => $telemetry->sync_iteration_count,
            'client_ip' => $telemetry->client_ip,
            'created_at' => $telemetry->created_at,
            'updated_at' => $telemetry->updated_at
        ];

        return [
            'success' => true,
            'data' => $formattedData
        ];
    }

    public function actionGetDevices()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Get token from request
            $token = $this->getTokenFromRequest();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No authorization token provided'
                ];
            }

            // Get secret key from params
            $secret = \Yii::$app->params['jwtSecret'] ?? 'your-default-secret-key';

            // Validate token
            $payload = JwtHelper::validate($token, $secret);

            // Extract user ID from payload
            $userId = $this->extractUserIdFromPayload($payload);

            if (!$userId) {
                return [
                    'success' => false,
                    'message' => 'User ID not found in token'
                ];
            }

            // Fetch devices
            $devices = UserDevices::find()
                ->where(['user_id' => $userId, 'is_active' => 1])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();

            $formattedDevices = [];
            foreach ($devices as $device) {
                $formattedDevices[] = [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'device_alias' => $device->device_alias,
                    'device_description' => $device->device_description,
                    'device_remark' => $device->device_remark,
                    'is_active' => (bool)$device->is_active,
                    'created_at' => $device->created_at,
                    'updated_at' => $device->updated_at,
                ];
            }

            return [
                'success' => true,
                'message' => 'Devices retrieved successfully',
                'data' => $formattedDevices
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get devices',
                'error' => $e->getMessage()
            ];
        }
    }

    private function getTokenFromRequest()
    {
        $request = \Yii::$app->request;

        // Check Authorization header
        $authHeader = $request->getHeaders()->get('Authorization');
        if ($authHeader && preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // Check POST data
        $token = $request->post('token');
        if ($token) {
            return trim($token);
        }

        // Check GET parameter (for testing)
        $token = $request->get('token');
        if ($token) {
            return trim($token);
        }

        return null;
    }

    private function extractUserIdFromPayload($payload)
    {
        // Check for user ID in various possible locations
        if (isset($payload['data']['id'])) {
            return (int)$payload['data']['id'];
        }

        if (isset($payload['user_id'])) {
            return (int)$payload['user_id'];
        }

        if (isset($payload['id'])) {
            return (int)$payload['id'];
        }

        if (isset($payload['sub'])) {
            return (int)$payload['sub'];
        }

        return null;
    }

    public function actionGetDevicesWithData()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Get token from request
            $token = $this->getTokenFromRequest();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No authorization token provided'
                ];
            }

            // Get secret key from params
            $secret = \Yii::$app->params['jwtSecret'] ?? 'your-default-secret-key';

            // Validate token
            $payload = JwtHelper::validate($token, $secret);

            // Extract user ID from payload
            $userId = $this->extractUserIdFromPayload($payload);

            if (!$userId) {
                return [
                    'success' => false,
                    'message' => 'User ID not found in token'
                ];
            }

            // Fetch devices
            $devices = UserDevices::find()
                ->where(['user_id' => $userId, 'is_active' => 1])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();

            $formattedDevices = [];
            foreach ($devices as $device) {
                // Get latest telemetry data for this device
                $latestTelemetry = TelemetryData::getLatestByDevice($device->device_id);

                // Format telemetry data
                $telemetryData = $latestTelemetry ? $this->formatTelemetryData($latestTelemetry) : null;

                // Calculate derived metrics
                $calculatedMetrics = $this->calculateDeviceMetrics($latestTelemetry);

                // Get device status
                $status = $this->getDeviceStatus($latestTelemetry);

                // Get location data if available
                $location = $this->getLocationData($latestTelemetry);

                $formattedDevices[] = [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'device_alias' => $device->device_alias,
                    'device_description' => $device->device_description,
                    'device_remark' => $device->device_remark,
                    'is_active' => (bool)$device->is_active,
                    'created_at' => $device->created_at,
                    'updated_at' => $device->updated_at,

                    // Telemetry data
                    'telemetry' => $telemetryData,
                    'metrics' => $calculatedMetrics,
                    'status' => $status,
                    'status_text' => $this->getStatusText($status),
                    'last_updated' => $telemetryData ? $telemetryData['data_timestamp'] : null,
                    'last_updated_ago' => $telemetryData ? $this->getTimeAgo($telemetryData['data_timestamp']) : 'Never',

                    // Location data
                    'location' => $location,
                    'has_location' => !empty($location),

                    // Device hardware info from telemetry
                    'hardware_info' => [
                        'manufacturer' => $latestTelemetry->device_manufacturer ?? null,
                        'model' => $latestTelemetry->device_model ?? null,
                        'sync_type' => $latestTelemetry->sync_type ?? null,
                        'data_type' => $latestTelemetry->data_type ?? null,
                    ],
                ];
            }

            return [
                'success' => true,
                'message' => 'Devices retrieved successfully with telemetry data',
                'data' => $formattedDevices,
                'summary' => [
                    'total_devices' => count($formattedDevices),
                    'active_devices' => count(array_filter($formattedDevices, function ($d) {
                        return $d['status'] === 'active';
                    })),
                    'warning_devices' => count(array_filter($formattedDevices, function ($d) {
                        return $d['status'] === 'warning';
                    })),
                    'offline_devices' => count(array_filter($formattedDevices, function ($d) {
                        return $d['status'] === 'offline';
                    })),
                    'with_telemetry' => count(array_filter($formattedDevices, function ($d) {
                        return !empty($d['telemetry']);
                    })),
                    'with_location' => count(array_filter($formattedDevices, function ($d) {
                        return $d['has_location'];
                    })),
                    'total_power' => array_sum(array_map(function ($d) {
                        return $d['metrics']['calculated_power'] ?? 0;
                    }, $formattedDevices)),
                    'total_energy' => array_sum(array_map(function ($d) {
                        return $d['telemetry']['energy'] ?? 0;
                    }, $formattedDevices)),
                    'total_carbon_reduction' => array_sum(array_map(function ($d) {
                        return $d['metrics']['carbon_reduction'] ?? 0;
                    }, $formattedDevices)),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get devices with data',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format telemetry data for API response
     */
    private function formatTelemetryData($telemetry)
    {
        if (!$telemetry) {
            return null;
        }

        return [
            'id' => $telemetry->id,
            'data_timestamp' => $telemetry->data_timestamp,

            // AC measurements
            'ac_voltage' => $telemetry->ac_v,
            'ac_current' => $telemetry->ac_i,
            'ac_power' => $telemetry->ac_p,
            'energy' => $telemetry->energy,
            'frequency' => $telemetry->freq,
            'power_factor' => $telemetry->pf,

            // DC measurements
            'dc_voltage' => $telemetry->dc_v,
            'dc_current' => $telemetry->dc_i,

            // Status flags
            'low_voltage_warning' => (bool)$telemetry->low_v,
            'gps_fixed' => (bool)$telemetry->gps_fixed,
            'relay_state' => (bool)$telemetry->relay_state,

            // Location
            'latitude' => $telemetry->lat,
            'longitude' => $telemetry->lng,

            // Metadata
            'sample_count' => $telemetry->sample_count,
            'sync_type' => $telemetry->sync_type,
            'data_type' => $telemetry->data_type,
            'buffer_start_time' => $telemetry->buffer_start_time,
            'buffer_end_time' => $telemetry->buffer_end_time,
            'original_sample_count' => $telemetry->original_sample_count,
            'sync_interval' => $telemetry->sync_interval,
            'sync_iteration_count' => $telemetry->sync_iteration_count,

            // Device info from telemetry
            'device_name_from_telemetry' => $telemetry->device_name,
            'device_manufacturer' => $telemetry->device_manufacturer,
            'device_model' => $telemetry->device_model,
        ];
    }

    /**
     * Calculate derived metrics from telemetry data
     */
    private function calculateDeviceMetrics($telemetry)
    {
        if (!$telemetry) {
            return [
                'status' => 'no_data',
                'calculated_power' => 0,
                'carbon_reduction' => 0,
                'uptime' => 0,
                'efficiency' => 0,
                'battery_percentage' => 0,
                'signal_quality' => 'unknown',
                'dc_power' => 0,
                'ac_dc_efficiency' => 0,
            ];
        }

        // Calculate AC power if not provided
        $calculatedAcPower = $telemetry->ac_p;
        if (!$calculatedAcPower && $telemetry->ac_v !== null && $telemetry->ac_i !== null) {
            $calculatedAcPower = $telemetry->ac_v * $telemetry->ac_i * ($telemetry->pf ?: 1);
        }

        // Calculate DC power
        $dcPower = 0;
        if ($telemetry->dc_v !== null && $telemetry->dc_i !== null) {
            $dcPower = $telemetry->dc_v * $telemetry->dc_i;
        }

        // Calculate efficiency (DC to AC if both available)
        $acDcEfficiency = 0;
        if ($dcPower > 0 && $calculatedAcPower > 0) {
            $acDcEfficiency = ($calculatedAcPower / $dcPower) * 100;
        }

        // Calculate carbon reduction (example formula)
        $carbonReduction = 0;
        if ($telemetry->energy) {
            // Assuming renewable energy, 0.5 kg CO2 per kWh saved from grid
            $carbonReduction = $telemetry->energy * 0.5;
        }

        // Calculate uptime based on last update time
        $uptime = $this->calculateUptime($telemetry->data_timestamp);

        // Calculate efficiency from power factor
        $efficiency = 0;
        if ($telemetry->pf) {
            $efficiency = $telemetry->pf * 100;
        }

        // Estimate battery percentage from DC voltage (example: 12V system)
        $batteryPercentage = 0;
        if ($telemetry->dc_v !== null) {
            // Example for 12V lead-acid battery
            if ($telemetry->dc_v >= 12.7) {
                $batteryPercentage = 100;
            } elseif ($telemetry->dc_v >= 12.4) {
                $batteryPercentage = 75;
            } elseif ($telemetry->dc_v >= 12.2) {
                $batteryPercentage = 50;
            } elseif ($telemetry->dc_v >= 12.0) {
                $batteryPercentage = 25;
            } else {
                $batteryPercentage = 0;
            }
        }

        return [
            'status' => 'active',
            'calculated_power' => round($calculatedAcPower, 2),
            'dc_power' => round($dcPower, 2),
            'carbon_reduction' => round($carbonReduction, 2),
            'uptime' => round($uptime, 1),
            'efficiency' => round($efficiency, 1),
            'ac_dc_efficiency' => round($acDcEfficiency, 1),
            'battery_percentage' => $batteryPercentage,
            'battery_voltage' => $telemetry->dc_v,
            'signal_quality' => 'good', // You can add signal strength if available
            'low_voltage' => (bool)$telemetry->low_v,
            'relay_on' => (bool)$telemetry->relay_state,
            'gps_locked' => (bool)$telemetry->gps_fixed,
            'frequency_stable' => $telemetry->freq ? (abs($telemetry->freq - 50) < 0.5 || abs($telemetry->freq - 60) < 0.5) : false,
        ];
    }

    /**
     * Calculate uptime percentage based on last update
     */
    private function calculateUptime($lastTimestamp)
    {
        if (!$lastTimestamp) {
            return 0;
        }

        $lastUpdate = strtotime($lastTimestamp);
        $now = time();
        $timeDiff = $now - $lastUpdate;

        // Return uptime based on how recent the data is
        if ($timeDiff < 300) { // Last 5 minutes
            return 100;
        } elseif ($timeDiff < 1800) { // Last 30 minutes
            return 95;
        } elseif ($timeDiff < 3600) { // Last hour
            return 90;
        } elseif ($timeDiff < 7200) { // Last 2 hours
            return 80;
        } elseif ($timeDiff < 14400) { // Last 4 hours
            return 70;
        } elseif ($timeDiff < 28800) { // Last 8 hours
            return 50;
        } else {
            return 0;
        }
    }

    /**
     * Determine device status based on telemetry
     */
    private function getDeviceStatus($telemetry)
    {
        if (!$telemetry) {
            return 'offline';
        }

        $timestamp = $telemetry->data_timestamp;
        if (!$timestamp) {
            return 'offline';
        }

        $lastUpdate = strtotime($timestamp);
        $now = time();
        $timeDiff = $now - $lastUpdate;

        // Check for warning conditions
        $hasWarnings = false;
        if ($telemetry->low_v) {
            $hasWarnings = true;
        }
        if ($telemetry->dc_v !== null && $telemetry->dc_v < 12.0) {
            $hasWarnings = true;
        }

        if ($timeDiff < 300) { // Last 5 minutes
            return $hasWarnings ? 'warning' : 'active';
        } elseif ($timeDiff < 3600) { // Last hour
            return 'idle';
        } else {
            return 'offline';
        }
    }

    /**
     * Get status text for display
     */
    private function getStatusText($status)
    {
        $statusTexts = [
            'active' => '🟢 Active',
            'warning' => '🟡 Warning',
            'idle' => '⚪ Idle',
            'offline' => '🔴 Offline',
            'no_data' => '⚫ No Data',
        ];

        return $statusTexts[$status] ?? 'Unknown';
    }

    /**
     * Get location data from telemetry
     */
    private function getLocationData($telemetry)
    {
        if (!$telemetry || !$telemetry->lat || !$telemetry->lng) {
            return null;
        }

        return [
            'latitude' => $telemetry->lat,
            'longitude' => $telemetry->lng,
            'gps_fixed' => (bool)$telemetry->gps_fixed,
            'formatted' => sprintf('%.6f, %.6f', $telemetry->lat, $telemetry->lng),
            'google_maps_link' => sprintf('https://maps.google.com/?q=%s,%s', $telemetry->lat, $telemetry->lng),
            'openstreetmap_link' => sprintf('https://www.openstreetmap.org/?mlat=%s&mlon=%s', $telemetry->lat, $telemetry->lng),
        ];
    }

    /**
     * Get time ago string
     */
    private function getTimeAgo($timestamp)
    {
        if (!$timestamp) {
            return 'Never';
        }

        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }

    public function actionSaveScrapeDataV2()
    {
        $startTime = microtime(true);
        $response = ['success' => false, 'message' => ''];

        try {
            // Get raw JSON input
            $rawData = Yii::$app->request->getRawBody();
            $jsonData = json_decode($rawData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON data: ' . json_last_error_msg());
            }

            // Validate required structure for V2
            if (!isset($jsonData['metadata']) || !isset($jsonData['data'])) {
                throw new \Exception('Missing required fields: metadata and data');
            }

            // Validate metadata
            if (!isset($jsonData['metadata']['scrapeTimestamp'])) {
                throw new \Exception('Missing scrapeTimestamp in metadata');
            }

            // Validate data structure has at least one source
            if (!isset($jsonData['data']['investing_com']) && !isset($jsonData['data']['myfxbook'])) {
                throw new \Exception('No data found from either investing.com or myfxbook');
            }

            // Extract pair (fixed for this scraper)
            $pair = $jsonData['data']['currency_pair'] ?? 'EURJPY';
            $timeframe = 'H4'; // Default timeframe for this scraper

            // Extract URLs if available
            $investingUrl = $jsonData['data']['investing_com']['url'] ?? null;
            $myfxbookUrl = $jsonData['data']['myfxbook']['url'] ?? null;

            // Use first available URL for logging
            $url = $investingUrl ?? $myfxbookUrl ?? '';

            // Process Investing.com data if present
            $investingData = null;
            if (isset($jsonData['data']['investing_com'])) {
                $investingData = $this->processInvestingData($jsonData['data']['investing_com'], $pair, $timeframe);
            }

            // Process Myfxbook data if present
            $myfxbookData = null;
            if (isset($jsonData['data']['myfxbook'])) {
                $myfxbookData = $this->processMyfxbookData($jsonData['data']['myfxbook'], $pair, $timeframe);
            }

            // Log the API request
            $responseTime = microtime(true) - $startTime;
            ScrapedDataLog::logScrapedData(
                'scraped-data/save-v2',
                'POST',
                $jsonData,
                $pair,
                $timeframe,
                200,
                $responseTime
            );

            // Save to database
            $dbSuccess = $this->saveToDatabaseV2([
                'pair' => $pair,
                'timeframe' => $timeframe,
                'investing_data' => $investingData,
                'myfxbook_data' => $myfxbookData,
                'combined_data' => $jsonData['data'],
                'scrape_timestamp' => $jsonData['metadata']['scrapeTimestamp'],
                'combined_at' => $jsonData['data']['combined_at'] ?? null,
                'investing_url' => $investingUrl,
                'myfxbook_url' => $myfxbookUrl
            ]);

            // Send success notification to Telegram if configured
            $this->sendV2SuccessNotification($jsonData, $pair, $timeframe, $dbSuccess);

            $response = [
                'success' => true,
                'message' => 'Dual source scraped data saved successfully',
                'data' => [
                    'pair' => $pair,
                    'timeframe' => $timeframe,
                    'sources_received' => array_filter([
                        'investing.com' => !empty($jsonData['data']['investing_com']),
                        'myfxbook' => !empty($jsonData['data']['myfxbook'])
                    ]),
                    'scrape_timestamp' => $jsonData['metadata']['scrapeTimestamp'],
                    'combined_at' => $jsonData['data']['combined_at'] ?? null,
                    'database_save_success' => $dbSuccess,
                    'response_time' => round($responseTime, 3) . 's'
                ]
            ];
        } catch (\Exception $e) {
            // Log error
            $responseTime = microtime(true) - $startTime;

            ScrapedDataLog::logScrapedData(
                'scraped-data/save-v2',
                'POST',
                $rawData,
                $pair ?? 'ERROR',
                $timeframe ?? 'ERROR',
                400,
                $responseTime
            );

            // Send error notification to Telegram
            TelegramHelper::sendSimpleError(
                "V2 Scraped data save failed: " . $e->getMessage(),
                "Pair: " . ($pair ?? 'UNKNOWN') . ", Timeframe: " . ($timeframe ?? 'UNKNOWN')
            );

            Yii::error('Scraped Data V2 API Error: ' . $e->getMessage());

            $response = [
                'success' => false,
                'message' => 'Error processing V2 scraped data: ' . $e->getMessage(),
                'error_code' => 400,
                'pair' => $pair ?? null,
                'timeframe' => $timeframe ?? null
            ];

            Yii::$app->response->statusCode = 400;
        }

        return $response;
    }

    /**
     * Process Investing.com data structure
     */
    private function processInvestingData($investingData, $pair, $timeframe)
    {
        $processed = [
            'currency_pair' => $investingData['currency_pair'] ?? $pair,
            'url' => $investingData['url'] ?? '',
            'source' => $investingData['source'] ?? 'investing.com',
            'scraped_at' => $investingData['scraped_at'] ?? date('Y-m-d H:i:s'),
            'overall_signal' => $investingData['overall_signal'] ?? null,
            'sections' => []
        ];

        // Process sections if available
        if (isset($investingData['sections']) && is_array($investingData['sections'])) {
            foreach ($investingData['sections'] as $sectionName => $sectionData) {
                $processed['sections'][$sectionName] = [
                    'headers' => $sectionData['headers'] ?? [],
                    'row_count' => isset($sectionData['rows']) ? count($sectionData['rows']) : 0,
                    'rows' => $sectionData['rows'] ?? []
                ];
            }
        }

        return $processed;
    }

    /**
     * Process Myfxbook data structure
     */
    private function processMyfxbookData($myfxbookData, $pair, $timeframe)
    {
        $processed = [
            'currency_pair' => $myfxbookData['currency_pair'] ?? $pair,
            'url' => $myfxbookData['url'] ?? '',
            'source' => $myfxbookData['source'] ?? 'myfxbook.com',
            'scraped_at' => $myfxbookData['scraped_at'] ?? date('Y-m-d H:i:s'),
            'price_info' => $myfxbookData['price_info'] ?? null,
            'technical_summary' => $myfxbookData['technical_summary'] ?? null,
            'sections' => []
        ];

        // Process sections if available
        if (isset($myfxbookData['sections']) && is_array($myfxbookData['sections'])) {
            foreach ($myfxbookData['sections'] as $sectionName => $sectionData) {
                $processed['sections'][$sectionName] = [
                    'headers' => $sectionData['headers'] ?? [],
                    'row_count' => isset($sectionData['rows']) ? count($sectionData['rows']) : 0,
                    'rows' => $sectionData['rows'] ?? []
                ];
            }
        }

        return $processed;
    }

    /**
     * Save processed data to database
     */
    private function saveToDatabaseV2($data)
    {
        try {
            // Create new model instance for dual source data
            $model = new DualSourceScrapedData();

            $model->pair = $data['pair'];
            $model->timeframe = $data['timeframe'];
            $model->investing_data = json_encode($data['investing_data']);
            $model->myfxbook_data = json_encode($data['myfxbook_data']);
            $model->combined_data = json_encode($data['combined_data']);
            $model->scrape_timestamp = $data['scrape_timestamp'];
            $model->combined_at = $data['combined_at'];
            $model->investing_url = $data['investing_url'];
            $model->myfxbook_url = $data['myfxbook_url'];
            $model->created_at = date('Y-m-d H:i:s');

            if ($model->save()) {
                Yii::info('Dual source scraped data saved to database: ' . $data['pair']);
                return true;
            } else {
                Yii::error('Failed to save dual source scraped data: ' . json_encode($model->errors));
                return false;
            }
        } catch (\Exception $e) {
            Yii::error('Database save error for dual source data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send success notification for V2 data
     */
    private function sendV2SuccessNotification($jsonData, $pair, $timeframe, $dbSuccess)
    {
        if (!Yii::$app->params['enableTelegramNotifications'] ?? false) {
            return;
        }

        try {
            $sources = [];
            if (!empty($jsonData['data']['investing_com'])) {
                $sources[] = 'Investing.com';
            }
            if (!empty($jsonData['data']['myfxbook'])) {
                $sources[] = 'Myfxbook';
            }

            $message = "✅ Dual Source Scraped Data Received\n";
            $message .= "📊 Pair: {$pair}\n";
            $message .= "🕐 Timeframe: {$timeframe}\n";
            $message .= "🌐 Sources: " . implode(', ', $sources) . "\n";
            $message .= "🔍 Scrape Time: " . ($jsonData['metadata']['scrapeTimestamp'] ?? 'N/A') . "\n";
            $message .= "📈 DB Save: " . ($dbSuccess ? 'Success' : 'Failed') . "\n";

            if (isset($jsonData['data']['investing_com']['overall_signal'])) {
                $message .= "Signal: " . $jsonData['data']['investing_com']['overall_signal'] . "\n";
            }

            TelegramHelper::sendSimpleMessage($message);
        } catch (\Exception $e) {
            Yii::error('Failed to send V2 success notification: ' . $e->getMessage());
        }
    }

    public function actionSaveInvestingData()
    {
        $startTime = microtime(true);
        $response = ['success' => false, 'message' => ''];

        try {
            // Get raw JSON input
            $rawData = Yii::$app->request->getRawBody();
            $jsonData = json_decode($rawData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON data: ' . json_last_error_msg());
            }

            // Validate required structure
            if (!isset($jsonData['metadata']) || !isset($jsonData['data'])) {
                throw new \Exception('Missing required fields: metadata and data');
            }

            // Validate metadata
            if (!isset($jsonData['metadata']['source']) || $jsonData['metadata']['source'] !== 'investing.com') {
                throw new \Exception('Invalid or missing source. Expected: investing.com');
            }

            // Validate data structure
            if (!isset($jsonData['data']['currency_pair']) || !isset($jsonData['data']['sections'])) {
                throw new \Exception('Missing required data fields: currency_pair or sections');
            }

            // Extract pair from data
            $pair = $jsonData['data']['currency_pair'] ?? 'UNKNOWN';
            $timeframe = null; // Investing.com doesn't use timeframe

            // Extract URL
            $url = $jsonData['data']['url'] ?? $jsonData['metadata']['url'] ?? null;

            // Extract sections
            $sections = $jsonData['data']['sections'] ?? [];

            // Extract overall signal
            $overallSignal = $jsonData['data']['overall_signal'] ?? null;

            // Scrape timestamp
            $scrapeTimestamp = $jsonData['data']['scraped_at'] ?? $jsonData['metadata']['scrapeTimestamp'] ?? date('Y-m-d H:i:s');

            // Prepare data for database
            $dbData = [
                'pair' => $pair,
                'timeframe' => $timeframe,
                'url' => $url,
                'overall_signal' => $overallSignal,
                'scrape_timestamp' => $scrapeTimestamp,
                'source' => 'investing.com',
                'status' => 1
            ];

            // Extract specific sections
            $dbData['technical_indicators'] = isset($sections['technical_indicators']) ?
                json_encode($sections['technical_indicators']) : null;

            $dbData['moving_averages'] = isset($sections['moving_averages']) ?
                json_encode($sections['moving_averages']) : null;

            $dbData['pivot_points'] = isset($sections['pivot_points']) ?
                json_encode($sections['pivot_points']) : null;

            $dbData['summary_data'] = isset($sections['summary']) ?
                json_encode($sections['summary']) : null;

            // Store other sections
            $otherSections = [];
            foreach ($sections as $key => $section) {
                if (!in_array($key, ['technical_indicators', 'moving_averages', 'pivot_points', 'summary'])) {
                    $otherSections[$key] = $section;
                }
            }

            $dbData['other_sections'] = !empty($otherSections) ?
                json_encode($otherSections) : null;

            // Store raw data
            $dbData['raw_data'] = json_encode($jsonData['data']);

            // Save to database
            $model = new InvestingScrapedData();
            $model->attributes = $dbData;

            if (!$model->save()) {
                throw new \Exception('Failed to save data to database: ' . json_encode($model->errors));
            }

            // Log the API request
            $responseTime = microtime(true) - $startTime;

            // Use existing log method if available, or create new one
            if (class_exists('ScrapedDataLog')) {
                ScrapedDataLog::logScrapedData(
                    'mobile/save-scrape-data-investing',
                    'POST',
                    $jsonData,
                    $pair,
                    $timeframe,
                    200,
                    $responseTime
                );
            }

            // Send success notification if configured
            $this->sendInvestingSuccessNotification($jsonData, $pair, $model->id);

            $response = [
                'success' => true,
                'message' => 'Investing.com data saved successfully',
                'data' => [
                    'id' => $model->id,
                    'pair' => $pair,
                    'timeframe' => $timeframe,
                    'url' => $url,
                    'overall_signal' => $overallSignal,
                    'scrape_timestamp' => $scrapeTimestamp,
                    'created_at' => $model->created_at,
                    'response_time' => round($responseTime, 3) . 's'
                ]
            ];
        } catch (\Exception $e) {
            // Handle error
            $responseTime = microtime(true) - $startTime;

            // Extract pair even on error
            $pair = 'ERROR';
            try {
                if (isset($jsonData['data']['currency_pair'])) {
                    $pair = $jsonData['data']['currency_pair'];
                }
            } catch (\Exception $ex) {
                // Ignore extraction error
            }

            // Log error
            if (class_exists('ScrapedDataLog')) {
                ScrapedDataLog::logScrapedData(
                    'mobile/save-scrape-data-investing',
                    'POST',
                    $rawData ?? [],
                    $pair,
                    null,
                    400,
                    $responseTime
                );
            }

            // Send error notification if configured
            if (class_exists('TelegramHelper')) {
                TelegramHelper::sendSimpleError(
                    "Investing.com data save failed: " . $e->getMessage(),
                    "Pair: {$pair}"
                );
            }

            Yii::error('Investing Data API Error: ' . $e->getMessage());

            $response = [
                'success' => false,
                'message' => 'Error processing Investing.com data: ' . $e->getMessage(),
                'error_code' => 400,
                'pair' => $pair
            ];

            Yii::$app->response->statusCode = 400;
        }

        return $response;
    }

    private function sendInvestingSuccessNotification($data, $pair, $recordId)
    {
        if (!class_exists('TelegramHelper')) {
            return;
        }

        try {
            $sections = $data['data']['sections'] ?? [];
            $sectionCount = count($sections);
            $overallSignal = $data['data']['overall_signal'] ?? 'N/A';

            $message = "✅ *Investing.com Data Saved Successfully*\n\n";
            $message .= "• *Pair:* {$pair}\n";
            $message .= "• *Record ID:* {$recordId}\n";
            $message .= "• *Sections:* {$sectionCount}\n";
            $message .= "• *Overall Signal:* {$overallSignal}\n";

            if (isset($data['metadata']['scrapeTimestamp'])) {
                $message .= "• *Scraped:* " . date('Y-m-d H:i:s', strtotime($data['metadata']['scrapeTimestamp'])) . "\n";
            }

            // List available sections
            if (!empty($sections)) {
                $message .= "• *Available Sections:* " . implode(', ', array_keys($sections)) . "\n";
            }

            TelegramHelper::sendMessage($message);
        } catch (\Exception $e) {
            Yii::error('Failed to send Investing.com notification: ' . $e->getMessage());
        }
    }


    public function actionSaveMyfxbookData()
    {
        $startTime = microtime(true);
        $response = ['success' => false, 'message' => ''];

        try {
            // Get raw JSON input
            $rawData = Yii::$app->request->getRawBody();
            $jsonData = json_decode($rawData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON data: ' . json_last_error_msg());
            }

            // Validate required structure
            if (!isset($jsonData['metadata']) || !isset($jsonData['data'])) {
                throw new \Exception('Missing required fields: metadata and data');
            }

            // Validate metadata
            if (!isset($jsonData['metadata']['source']) || $jsonData['metadata']['source'] !== 'myfxbook.com') {
                throw new \Exception('Invalid or missing source. Expected: myfxbook.com');
            }

            // Validate data structure
            if (!isset($jsonData['data']['pair']) || !isset($jsonData['data']['timeframe'])) {
                throw new \Exception('Missing required data fields: pair or timeframe');
            }

            // Extract pair and timeframe
            $pair = $jsonData['data']['pair'] ?? 'UNKNOWN';
            $timeframe = $jsonData['data']['timeframe'] ?? 'UNKNOWN';

            // Extract URL
            $url = $jsonData['data']['url'] ?? $jsonData['metadata']['url'] ?? null;

            // Extract sections
            $sections = $jsonData['data']['sections'] ?? [];

            // Extract additional info
            $priceInfo = $jsonData['data']['price_info'] ?? null;
            $technicalSummary = $jsonData['data']['technical_summary'] ?? null;

            // Scrape timestamp
            $scrapeTimestamp = $jsonData['data']['scraped_at'] ?? $jsonData['metadata']['scrapeTimestamp'] ?? date('Y-m-d H:i:s');

            // Prepare data for database
            $dbData = [
                'pair' => $pair,
                'timeframe' => $timeframe,
                'url' => $url,
                'price_info' => $priceInfo,
                'technical_summary' => $technicalSummary,
                'scrape_timestamp' => $scrapeTimestamp,
                'source' => 'myfxbook.com',
                'status' => 1
            ];

            // Extract specific sections
            $dbData['economic_calendar'] = isset($sections['economicCalendar']) ?
                json_encode($sections['economicCalendar']) : null;

            $dbData['technical_analysis'] = isset($sections['technicalAnalysis']) ?
                json_encode($sections['technicalAnalysis']) : null;

            $dbData['interest_rates'] = isset($sections['interestRates']) ?
                json_encode($sections['interestRates']) : null;

            // Store raw data
            $dbData['raw_data'] = json_encode($jsonData['data']);

            // Save to database
            $model = new MyfxbookScrapedDataNew();
            $model->attributes = $dbData;

            if (!$model->save()) {
                throw new \Exception('Failed to save data to database: ' . json_encode($model->errors));
            }

            // Log the API request
            $responseTime = microtime(true) - $startTime;

            // Use existing log method if available
            if (class_exists('ScrapedDataLog')) {
                ScrapedDataLog::logScrapedData(
                    'mobile/save-scrape-data-myfxbook',
                    'POST',
                    $jsonData,
                    $pair,
                    $timeframe,
                    200,
                    $responseTime
                );
            }

            // Send success notification if configured
            $this->sendMyfxbookSuccessNotification($jsonData, $pair, $timeframe, $model->id);

            $response = [
                'success' => true,
                'message' => 'Myfxbook data saved successfully',
                'data' => [
                    'id' => $model->id,
                    'pair' => $pair,
                    'timeframe' => $timeframe,
                    'url' => $url,
                    'scrape_timestamp' => $scrapeTimestamp,
                    'created_at' => $model->created_at,
                    'response_time' => round($responseTime, 3) . 's'
                ]
            ];
        } catch (\Exception $e) {
            // Handle error
            $responseTime = microtime(true) - $startTime;

            // Extract pair and timeframe even on error
            $pair = 'ERROR';
            $timeframe = 'ERROR';
            try {
                $pair = $jsonData['data']['pair'] ?? 'ERROR';
                $timeframe = $jsonData['data']['timeframe'] ?? 'ERROR';
            } catch (\Exception $ex) {
                // Ignore extraction error
            }

            // Log error
            if (class_exists('ScrapedDataLog')) {
                ScrapedDataLog::logScrapedData(
                    'mobile/save-scrape-data-myfxbook',
                    'POST',
                    $rawData ?? [],
                    $pair,
                    $timeframe,
                    400,
                    $responseTime
                );
            }

            // Send error notification if configured
            if (class_exists('TelegramHelper')) {
                TelegramHelper::sendSimpleError(
                    "Myfxbook data save failed: " . $e->getMessage(),
                    "Pair: {$pair}, Timeframe: {$timeframe}"
                );
            }

            Yii::error('Myfxbook Data API Error: ' . $e->getMessage());

            $response = [
                'success' => false,
                'message' => 'Error processing Myfxbook data: ' . $e->getMessage(),
                'error_code' => 400,
                'pair' => $pair,
                'timeframe' => $timeframe
            ];

            Yii::$app->response->statusCode = 400;
        }

        return $response;
    }

    private function sendMyfxbookSuccessNotification($data, $pair, $timeframe, $recordId)
    {
        if (!class_exists('TelegramHelper')) {
            return;
        }

        try {
            $sections = $data['data']['sections'] ?? [];
            $economicEvents = $sections['economicCalendar']['totalEvents'] ?? 0;
            $technicalPatterns = $sections['technicalAnalysis']['totalPatterns'] ?? 0;
            $interestRates = count($sections['interestRates'] ?? []);

            $message = "✅ *Myfxbook Data Saved Successfully*\n\n";
            $message .= "• *Pair:* {$pair}\n";
            $message .= "• *Timeframe:* {$timeframe}\n";
            $message .= "• *Record ID:* {$recordId}\n";
            $message .= "• *Economic Events:* {$economicEvents}\n";
            $message .= "• *Technical Patterns:* {$technicalPatterns}\n";
            $message .= "• *Interest Rates:* {$interestRates}\n";

            if (isset($data['metadata']['scrapeTimestamp'])) {
                $message .= "• *Scraped:* " . date('Y-m-d H:i:s', strtotime($data['metadata']['scrapeTimestamp'])) . "\n";
            }

            // Add price info if available
            if (isset($data['data']['price_info'])) {
                $message .= "• *Price Info:* " . substr($data['data']['price_info'], 0, 50) . "...\n";
            }

            TelegramHelper::sendMessage($message);
        } catch (\Exception $e) {
            Yii::error('Failed to send Myfxbook notification: ' . $e->getMessage());
        }
    }


    /**
     * Handle OPTIONS request for CORS preflight
     */
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return [];
    }
}
