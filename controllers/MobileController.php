<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\forms\LoginForm;
use app\helpers\TelegramHelper;

class MobileController extends Controller
{

    public $enableCsrfValidation = false; // Disable CSRF validation for this controller


    public function actionIndex()
    {
        Yii::debug('debug index mobile controller'); // Use Yii's logging
        return 'index';
    }

    public function actionLogin()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

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
            // $model->enableAutoLogin = true;

            // var_dump($model);die;

            // Attempt login
            if ($model->login()) {
                // Get the logged-in user
                $user = Yii::$app->user->identity;
                // var_dump($user);die;

                // Generate JWT token
                $token = $this->generateJwtToken($user);
                // var_dump($token);die;

                // Log the login activity
                $clientIp = \app\helpers\CustomHelper::get_client_ip() ?? 'localhost';
                // var_dump($clientIp);die;

                TelegramHelper::sendMessage(
                    [
                        'text' => "Mobile User Login : " . $model->user_name . "\nFrom : " . $clientIp,
                        'parse_mode' => 'html'
                    ],
                    Yii::$app->params['group_id']
                );

                // var_dump($user);die;

                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => [
                        'id' => $user->user_id,
                        'username' => $user->user_name,
                        'user_tipe' => $user->user_tipe, 
                        'user_email' => $user->user_email, 
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
                'userId' => $user->id,
                'username' => $user->user_name,
            ]
        ];

        $token = $this->manualJwtEncode($payload, $secret);
        return $token;
    }

    /**
     * Manual JWT encoding (fallback if JWT library not available)
     */
    private function manualJwtEncode($payload, $secret)
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        // Helper function for base64url encoding
        $base64url = function ($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };

        // Encode header and payload
        $headerEncoded = $base64url(json_encode($header));
        $payloadEncoded = $base64url(json_encode($payload));

        // Create signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $signatureEncoded = $base64url($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    public function actionValidateToken()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

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

        try {
            // If using firebase/php-jwt
            if (class_exists('\Firebase\JWT\JWT')) {
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));
                return time() < $decoded->exp;
            }

            // Manual validation
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }

            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

            // Verify signature
            $signature = base64_decode($signatureEncoded);
            $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);

            if (!hash_equals($signature, $expectedSignature)) {
                return false;
            }

            // Check expiration
            $payload = json_decode(base64_decode($payloadEncoded), true);
            return time() < $payload['exp'];
        } catch (\Exception $e) {
            Yii::error('JWT validation error: ' . $e->getMessage());
            return false;
        }
    }
}
