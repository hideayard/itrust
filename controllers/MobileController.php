<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\Cors;
use app\models\forms\LoginForm;
use app\helpers\TelegramHelper;
use app\helpers\JwtHelper;

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
        if (in_array($action->id, ['telemetry', 'averaged-telemetry', 'sync-status'])) {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }

        return parent::beforeAction($action);
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

                TelegramHelper::sendMessage(
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

            TelegramHelper::sendMessage(
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

            TelegramHelper::sendMessage(
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

            TelegramHelper::sendMessage(
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

            TelegramHelper::sendMessage(
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

                TelegramHelper::sendMessage(
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

                TelegramHelper::sendMessage(
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

                TelegramHelper::sendMessage(
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
}
