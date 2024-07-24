<?php

namespace app\helpers;

use Exception;
use Yii;

class TelegramHelper
{

    private static $ch; //cURL Handle
    private static $defaultChatId =  -742356225; //bot common

    private static $groups = [];

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
        curl_setopt(self::$ch, CURLOPT_POSTFIELDS, http_build_query($params));

        if ($method == 'sendPhoto' || $method == 'sendDocument') {
            curl_setopt(self::$ch, CURLOPT_HTTPHEADER, [
                "Content-Type: multipart/form-data"
            ]);
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $params);
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

    public static function sendMessage($params, $group = null, $botToken = null)
    {

        if (!isset($params['text']) || empty($params['text'])) {
            return false;
        }

        if (!isset($params['parse_mode'])) {
            $params['parse_mode'] = 'html';
        }

        $params['chat_id'] = ($group) ? (is_numeric($group) ? $group : self::$groups[$group]) : self::$defaultChatId;

        return self::send('sendMessage', $params, $botToken);
    }

    public static function sendDocument($params, $group = null, $botToken = null)
    {

        if (!isset($params['document']) || empty($params['document'])) {
            return false;
        }

        if (!isset($params['parse_mode'])) {
            $params['parse_mode'] = 'html';
        }

        $params['chat_id'] = ($group) ? (is_numeric($group) ? $group : self::$groups[$group]) : self::$defaultChatId;

        return self::send('sendDocument', $params, $botToken);
    }

    public static function sendPhoto($params, $group = null, $botToken = null)
    {

        if (!isset($params['photo']) || empty($params['photo'])) {
            return false;
        }

        if (!isset($params['parse_mode'])) {
            $params['parse_mode'] = 'html';
        }

        $params['chat_id'] = ($group) ? (is_numeric($group) ? $group : self::$groups[$group]) : self::$defaultChatId;

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
}
