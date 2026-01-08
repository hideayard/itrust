<?php

namespace app\controllers\api;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;
use yii\filters\Cors;
use app\models\ScrapedDataLog;
use app\helpers\TelegramHelper;

class ScrapedDataController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['*'], // Adjust as needed for security
                    'Access-Control-Request-Method' => ['POST', 'GET', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => null,
                    'Access-Control-Max-Age' => 86400,
                    'Access-Control-Expose-Headers' => [],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'save' => ['POST'],
                    'logs' => ['GET'],
                    'statistics' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * Save scraped data endpoint
     * 
     * @return array
     */
    public function actionSave()
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
            $processingResult = $this->processAndSaveData($jsonData, $pair, $timeframe);
            
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
            $this->sendSuccessNotification($jsonData, $pair, $timeframe, $processingResult);
            
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

    /**
     * Get logs by pair and timeframe
     * 
     * @return array
     */
    public function actionLogs()
    {
        $pair = Yii::$app->request->get('pair', 'EURUSD');
        $timeframe = Yii::$app->request->get('timeframe', 'H4');
        $limit = Yii::$app->request->get('limit', 10);
        
        $logs = ScrapedDataLog::findByPairAndTimeframe($pair, $timeframe, $limit);
        
        $result = [];
        foreach ($logs as $log) {
            $result[] = [
                'id' => $log->id,
                'endpoint' => $log->endpoint,
                'method' => $log->method,
                'pair' => $log->pair,
                'timeframe' => $log->timeframe,
                'response_status' => $log->response_status,
                'response_time' => $log->response_time,
                'created_at' => $log->created_at,
                'url' => $log->getScrapedUrl(),
                'has_technical' => !empty($log->getTechnicalAnalysis()),
                'has_economic' => !empty($log->getEconomicCalendar()),
                'has_interest' => !empty($log->getInterestRates()),
            ];
        }
        
        return [
            'success' => true,
            'pair' => $pair,
            'timeframe' => $timeframe,
            'total' => count($result),
            'logs' => $result
        ];
    }

    /**
     * Get statistics for a pair
     * 
     * @return array
     */
    public function actionStatistics()
    {
        $pair = Yii::$app->request->get('pair', 'EURUSD');
        
        $statistics = ScrapedDataLog::getPairStatistics($pair);
        
        return [
            'success' => true,
            'pair' => $pair,
            'statistics' => $statistics
        ];
    }

    /**
     * Process and save the actual data to application tables
     *
     * @param array $data
     * @param string $pair
     * @param string $timeframe
     * @return array
     */
    private function processAndSaveData($data, $pair, $timeframe)
    {
        $result = [
            'economic_events_saved' => 0,
            'technical_patterns_saved' => 0,
            'interest_rates_saved' => 0,
            'metadata_saved' => false
        ];
        
        // Save metadata
        if ($this->saveMetadata($data['metadata'], $pair, $timeframe)) {
            $result['metadata_saved'] = true;
        }
        
        // Save economic calendar events
        if (isset($data['data']['economicCalendar']['events'])) {
            $events = $data['data']['economicCalendar']['events'];
            $result['economic_events_saved'] = $this->saveEconomicEvents($events, $pair, $timeframe);
        }
        
        // Save technical analysis
        if (isset($data['data']['technicalAnalysis'])) {
            $technicalData = $data['data']['technicalAnalysis'];
            $result['technical_patterns_saved'] = $this->saveTechnicalAnalysis($technicalData, $pair, $timeframe);
        }
        
        // Save interest rates
        if (isset($data['data']['interestRates'])) {
            $interestRates = $data['data']['interestRates'];
            $result['interest_rates_saved'] = $this->saveInterestRates($interestRates, $pair, $timeframe);
        }
        
        Yii::info("Data processed for {$pair}-{$timeframe}: " . json_encode($result));
        
        return $result;
    }

    /**
     * Save metadata
     *
     * @param array $metadata
     * @param string $pair
     * @param string $timeframe
     * @return bool
     */
    private function saveMetadata($metadata, $pair, $timeframe)
    {
        // TODO: Create and save to a metadata table if needed
        // Example: 
        // $model = new ScrapedMetadata();
        // $model->pair = $pair;
        // $model->timeframe = $timeframe;
        // $model->url = $metadata['url'];
        // $model->scrape_timestamp = $metadata['scrapeTimestamp'];
        // $model->scrape_count = $metadata['scrapeCount'] ?? 1;
        // $model->refresh_interval = $metadata['refreshInterval'] ?? 900;
        // return $model->save();
        
        return true;
    }

    /**
     * Save economic events
     *
     * @param array $events
     * @param string $pair
     * @param string $timeframe
     * @return int
     */
    private function saveEconomicEvents($events, $pair, $timeframe)
    {
        $saved = 0;
        
        foreach ($events as $event) {
            // TODO: Save to your economic_events table
            // $economicEvent = new EconomicEvent();
            // $economicEvent->pair = $pair;
            // $economicEvent->timeframe = $timeframe;
            // $economicEvent->time = $event['time'];
            // $economicEvent->currency = $event['currency'];
            // $economicEvent->event = $event['event'];
            // $economicEvent->impact = $event['impact'];
            // $economicEvent->previous = $event['previous'];
            // $economicEvent->forecast = $event['forecast'];
            // $economicEvent->country = $event['country'];
            // $economicEvent->country_code = $event['countryCode'];
            // if ($economicEvent->save()) {
            //     $saved++;
            // }
        }
        
        return $saved;
    }

    /**
     * Save technical analysis
     *
     * @param array $technicalAnalysis
     * @param string $pair
     * @param string $timeframe
     * @return int
     */
    private function saveTechnicalAnalysis($technicalAnalysis, $pair, $timeframe)
    {
        $saved = 0;
        
        if (isset($technicalAnalysis['patterns'])) {
            foreach ($technicalAnalysis['patterns'] as $pattern) {
                // TODO: Save to your technical_patterns table
                // $technicalPattern = new TechnicalPattern();
                // $technicalPattern->pair = $pair;
                // $technicalPattern->timeframe = $timeframe;
                // $technicalPattern->name = $pattern['name'];
                // $technicalPattern->signal = $pattern['signal'];
                // $technicalPattern->buy_timeframe = $pattern['buy'] ?? null;
                // $technicalPattern->sell_timeframe = $pattern['sell'] ?? null;
                // $technicalPattern->neutral_timeframe = $pattern['timeframes'] ?? null;
                // $technicalPattern->row_index = $pattern['rowIndex'];
                // if ($technicalPattern->save()) {
                //     $saved++;
                // }
            }
        }
        
        return $saved;
    }

    /**
     * Save interest rates
     *
     * @param array $interestRates
     * @param string $pair
     * @param string $timeframe
     * @return int
     */
    private function saveInterestRates($interestRates, $pair, $timeframe)
    {
        $saved = 0;
        
        foreach ($interestRates as $rate) {
            // TODO: Save to your interest_rates table
            // $interestRate = new InterestRate();
            // $interestRate->pair = $pair;
            // $interestRate->timeframe = $timeframe;
            // $interestRate->country = $rate['country'];
            // $interestRate->central_bank = $rate['centralBank'];
            // $interestRate->current_rate = $rate['currentRate'];
            // $interestRate->previous_rate = $rate['previousRate'];
            // $interestRate->next_meeting = $rate['nextMeeting'];
            // $interestRate->row_index = $rate['rowIndex'];
            // if ($interestRate->save()) {
            //     $saved++;
            // }
        }
        
        return $saved;
    }

    /**
     * Send success notification to Telegram
     *
     * @param array $data
     * @param string $pair
     * @param string $timeframe
     * @param array $processingResult
     */
    private function sendSuccessNotification($data, $pair, $timeframe, $processingResult)
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

    /**
     * Test endpoint
     * 
     * @return array
     */
    public function actionTest()
    {
        return [
            'success' => true,
            'message' => 'Scraped Data API is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'POST /api/scraped-data/save' => 'Save scraped data',
                'GET /api/scraped-data/logs?pair=EURUSD&timeframe=H4' => 'Get logs',
                'GET /api/scraped-data/statistics?pair=EURUSD' => 'Get statistics',
                'GET /api/scraped-data/test' => 'Test endpoint'
            ]
        ];
    }
}