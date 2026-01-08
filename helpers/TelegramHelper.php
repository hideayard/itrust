<?php

namespace app\helpers;

use Exception;
use Yii;

class TelegramHelper
{
    const MAX_MESSAGE_LENGTH = 4000; // Slightly under 4096 to be safe

    private static $ch; //cURL Handle
    private static $defaultChatId =  -1002149598297; //bot common

    private static $groups = [];


    /**
     * Send a simple error message without model
     * 
     * @param string $errorMessage The error message/description
     * @param string|null $context Additional context information
     * @param string|int|null $group Group ID (optional, defaults to error_group_id from params)
     * @param int|null $threadId Thread ID (optional, defaults to error_thread_id from params)
     * @return mixed
     */
    public static function sendSimpleError($errorMessage, $context = null, $group = null, $threadId = null)
    {
        // Determine group ID
        $chatId = $group ?? Yii::$app->params['error_group_id'] ?? Yii::$app->params['group_id'] ?? self::$defaultChatId;

        // Determine thread ID
        $messageThreadId = $threadId ?? (Yii::$app->params['error_thread_id'] ?? null);

        // Build error message
        $message = "🚨 <b>Error Alert</b>\n";
        $message .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";

        if ($context) {
            $message .= "📋 <b>Context:</b> " . htmlspecialchars($context) . "\n";
        }

        $message .= "❌ <b>Error:</b> " . htmlspecialchars($errorMessage);

        // Truncate if needed
        $message = self::truncateMessage($message);

        // Prepare parameters
        $params = [
            'text' => $message,
            'parse_mode' => 'html',
            'chat_id' => $chatId
        ];

        // Add thread ID if available
        if ($messageThreadId !== null) {
            $params['message_thread_id'] = (int)$messageThreadId;
        }

        try {
            return self::send('sendMessage', $params);
        } catch (\Exception $e) {
            Yii::error('Failed to send Telegram simple error: ' . $e->getMessage());
            return false;
        }
    }

    // // Simple error with default settings
    // TelegramHelper::sendSimpleError("Database connection failed");

    // // Error with context
    // TelegramHelper::sendSimpleError(
    //     "API request timeout", 
    //     "Fetching EUR/USD rates from provider"
    // );

    // // Error with custom group and thread
    // TelegramHelper::sendSimpleError(
    //     "Cron job failed",
    //     "Daily report generation",
    //     Yii::$app->params['monitoring_group_id'],
    //     789
    // );

    /**
     * Report error to Telegram
     * 
     * @param string $title Error title/context
     * @param mixed $data Error data (will be JSON encoded)
     * @param string|null $additionalInfo Additional context
     * @param string|int|null $groupId Telegram group ID (optional, will use default from params)
     * @param int|null $threadId Thread ID for forum topics (optional)
     */
    public static function report($title, $data, $additionalInfo = null, $groupId = null, $threadId = null)
    {
        // Build error message
        $message = "⚠️ <b>Error Report</b>\n";
        $message .= "📝 <b>Title:</b> " . htmlspecialchars($title) . "\n";
        $message .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";

        if ($additionalInfo) {
            $message .= "📋 <b>Context:</b> " . htmlspecialchars($additionalInfo) . "\n";
        }

        // Format data
        if (is_array($data) || is_object($data)) {
            $formattedData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $formattedData = (string)$data;
        }

        $message .= "❌ <b>Error Details:</b>\n<code>" . htmlspecialchars($formattedData) . "</code>";

        // Truncate if needed
        $message = self::truncateMessage($message);

        // Determine group ID and thread ID
        $targetGroupId = $groupId ?? Yii::$app->params['error_group_id'] ?? Yii::$app->params['group_id'] ?? self::$defaultChatId;

        // Get thread ID for errors if not explicitly provided
        if ($threadId === null) {
            $threadId = Yii::$app->params['error_thread_id'] ?? null;
        }

        if (empty($targetGroupId)) {
            Yii::error('Telegram group ID not configured for error reporting');
            return false;
        }

        try {
            $params = [
                'text' => $message,
                'parse_mode' => 'html'
            ];

            // Add thread ID if provided
            if ($threadId !== null) {
                $params['message_thread_id'] = (int)$threadId;
            }

            return self::sendMessage($params, $targetGroupId);
        } catch (\Exception $e) {
            Yii::error('Failed to send Telegram error report: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Truncate message for Telegram limits
     */
    private static function truncateMessage($text, $maxLength = null)
    {
        $maxLength = $maxLength ?? self::MAX_MESSAGE_LENGTH;

        if (mb_strlen($text, 'UTF-8') <= $maxLength) {
            return $text;
        }

        // Try to truncate at a reasonable point
        $truncated = mb_substr($text, 0, $maxLength - 100, 'UTF-8');

        // Find last newline before the limit
        $lastNewline = mb_strrpos($truncated, "\n", 0, 'UTF-8');
        if ($lastNewline > $maxLength - 200) {
            $truncated = mb_substr($truncated, 0, $lastNewline, 'UTF-8');
        }

        return $truncated . "\n\n... [message truncated due to Telegram length limits]";
    }

    /**
     * Report Yii model errors
     */
    public static function reportModelError($model, $context = null, $groupId = null, $threadId = null)
    {
        $title = 'Model Validation Error';
        $additional = $context ? "Context: {$context}" : "Model: " . get_class($model);

        return self::report($title, $model->errors, $additional, $groupId, $threadId);
    }

    private static function send($method, $params, $botToken = null, $log = false)
    {
        if (!self::$ch) {
            self::$ch = curl_init();
            curl_setopt(self::$ch, CURLOPT_POST, true);
            curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(self::$ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt(self::$ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt(self::$ch, CURLOPT_TIMEOUT, 10);
            curl_setopt(self::$ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, true);
        }

        if (!$botToken || empty($botToken)) {
            $botToken = Yii::$app->params['telegramBotToken'];
        }

        $endPoint = 'https://api.telegram.org/bot' . $botToken . '/' . $method;
        curl_setopt(self::$ch, CURLOPT_URL, $endPoint);

        // Handle multipart/form-data for file uploads
        if ($method == 'sendPhoto' || $method == 'sendDocument') {
            curl_setopt(self::$ch, CURLOPT_HTTPHEADER, [
                "Content-Type: multipart/form-data"
            ]);
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        if ($log) {
            Yii::error($botToken);
            Yii::error($params);
        }

        $response = curl_exec(self::$ch);

        if (curl_errno(self::$ch)) {
            throw new Exception("cURL error: " . curl_error(self::$ch));
        }

        return $response;
    }

    public static function getFile($file_id, $botToken)
    {
        if (!self::$ch) {
            self::$ch = curl_init();
            curl_setopt(self::$ch, CURLOPT_POST, true);
            curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(self::$ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt(self::$ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt(self::$ch, CURLOPT_TIMEOUT, 10);
            curl_setopt(self::$ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, true);
        }

        if (!$botToken || empty($botToken)) {
            $botToken = Yii::$app->params['telegramBotToken'];
        }

        $endPoint = 'https://api.telegram.org/bot' . $botToken . '/getFile';
        curl_setopt(self::$ch, CURLOPT_URL, $endPoint);
        curl_setopt(self::$ch, CURLOPT_POSTFIELDS, http_build_query([
            'file_id' => $file_id
        ]));

        $response = curl_exec(self::$ch);

        if (curl_errno(self::$ch)) {
            throw new Exception(curl_error(self::$ch));
        }

        return $response;
    }

    public static function downloadFile($file_path, $botToken)
    {
        if (!$botToken || empty($botToken)) {
            $botToken = Yii::$app->params['telegramBotToken'];
        }

        $endPoint = 'https://api.telegram.org/file/bot' . $botToken . '/' . $file_path;

        $contextOptions = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];

        return file_get_contents($endPoint, false, stream_context_create($contextOptions));
    }

    public static function answerCallbackQuery($params, $botToken = null)
    {
        return self::send('answerCallbackQuery', $params, $botToken);
    }

    public static function editMessageReplyMarkup($params)
    {
        return self::send('editMessageReplyMarkup', $params);
    }

    public static function sendAnimation($params)
    {
        return self::send('sendAnimation', $params);
    }

    public static function deleteMessage($params)
    {
        return self::send('deleteMessage', $params);
    }

    public static function editMessageText($params, $botToken = null)
    {
        return self::send('editMessageText', $params, $botToken);
    }

    /**
     * Send message to Telegram
     * 
     * @param array $params Message parameters
     * @param string|int|null $group Group ID or alias
     * @param string|null $botToken Bot token (optional)
     * @param int|null $threadId Thread ID for forum topics (optional)
     * @return mixed
     */
    public static function sendMessage($params, $group = null, $botToken = null, $threadId = null)
    {
        if (!isset($params['text']) || empty($params['text'])) {
            return false;
        }

        if (!isset($params['parse_mode'])) {
            $params['parse_mode'] = 'html';
        }

        // Determine chat ID
        $params['chat_id'] = ($group) ? (is_numeric($group) ? $group : self::$groups[$group]) : self::$defaultChatId;

        // Add thread ID if provided or from params for regular messages
        if ($threadId !== null) {
            $params['message_thread_id'] = (int)$threadId;
        } elseif ($params['chat_id'] == (Yii::$app->params['group_id'] ?? null) && isset(Yii::$app->params['thread_id'])) {
            // Default thread for main group
            $params['message_thread_id'] = (int)Yii::$app->params['thread_id'];
        }

        return self::send('sendMessage', $params, $botToken);
    }

    /**
     * Send document to Telegram
     * 
     * @param array $params Document parameters
     * @param string|int|null $group Group ID or alias
     * @param string|null $botToken Bot token (optional)
     * @param int|null $threadId Thread ID for forum topics (optional)
     * @return mixed
     */
    public static function sendDocument($params, $group = null, $botToken = null, $threadId = null)
    {
        if (!isset($params['document']) || empty($params['document'])) {
            return false;
        }

        if (!isset($params['parse_mode'])) {
            $params['parse_mode'] = 'html';
        }

        $params['chat_id'] = ($group) ? (is_numeric($group) ? $group : self::$groups[$group]) : self::$defaultChatId;

        // Add thread ID if provided
        if ($threadId !== null) {
            $params['message_thread_id'] = (int)$threadId;
        } elseif ($params['chat_id'] == (Yii::$app->params['group_id'] ?? null) && isset(Yii::$app->params['thread_id'])) {
            // Default thread for main group
            $params['message_thread_id'] = (int)Yii::$app->params['thread_id'];
        }

        return self::send('sendDocument', $params, $botToken);
    }

    /**
     * Send photo to Telegram
     * 
     * @param array $params Photo parameters
     * @param string|int|null $group Group ID or alias
     * @param string|null $botToken Bot token (optional)
     * @param int|null $threadId Thread ID for forum topics (optional)
     * @return mixed
     */
    public static function sendPhoto($params, $group = null, $botToken = null, $threadId = null)
    {
        if (!isset($params['photo']) || empty($params['photo'])) {
            return false;
        }

        if (!isset($params['parse_mode'])) {
            $params['parse_mode'] = 'html';
        }

        $params['chat_id'] = ($group) ? (is_numeric($group) ? $group : self::$groups[$group]) : self::$defaultChatId;

        // Add thread ID if provided
        if ($threadId !== null) {
            $params['message_thread_id'] = (int)$threadId;
        } elseif ($params['chat_id'] == (Yii::$app->params['group_id'] ?? null) && isset(Yii::$app->params['thread_id'])) {
            // Default thread for main group
            $params['message_thread_id'] = (int)Yii::$app->params['thread_id'];
        }

        return self::send('sendPhoto', $params, $botToken);
    }

    public static function sendPoll($params, $group = null)
    {
        if (!isset($params['parse_mode'])) {
            $params['parse_mode'] = 'html';
        }

        $params['chat_id'] = ($group) ? (is_numeric($group) ? $group : self::$groups[$group]) : self::$defaultChatId;

        return self::send('sendPoll', $params);
    }

    // Function to send a request to the Telegram API
    public static function sendTelegramRequest($method, $data)
    {
        $botToken = Yii::$app->params['telegramBotToken'];
        $url = "https://api.telegram.org/bot$botToken/$method";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Send a simple message with default group and thread settings
     * 
     * @param string $message Message text
     * @param string|int|null $group Group ID (optional, defaults to group_id from params)
     * @param int|null $threadId Thread ID (optional, defaults to thread_id from params)
     * @return mixed
     */
    public static function sendSimpleMessage($message, $group = null, $threadId = null)
    {
        // Determine group ID
        $chatId = $group ?? Yii::$app->params['group_id'] ?? self::$defaultChatId;

        // Determine thread ID
        $messageThreadId = $threadId ?? (Yii::$app->params['thread_id'] ?? null);

        // Prepare parameters
        $params = [
            'text' => $message,
            'parse_mode' => 'html',
            'chat_id' => $chatId
        ];

        // Add thread ID if available
        if ($messageThreadId !== null) {
            $params['message_thread_id'] = (int)$messageThreadId;
        }

        return self::send('sendMessage', $params);
    }
}
