<?php

namespace app\controllers;

use Yii;
use app\models\Notif;
use app\models\NotifSearch;
use yii\web\Controller;
use yii\web\Response;
use app\helpers\TelegramBotHelper;

class BotController extends Controller
{
    public $enableCsrfValidation = false;

    protected $botHelper;

    public function actionIndex()
    {
        return "BOT OK";
    }

    public function init()
    {
        $this->botHelper = new TelegramBotHelper(Yii::$app->params['telegramBotToken']);
    }

    public function actionWebhook()
    {
        $update = json_decode(Yii::$app->request->getRawBody(), true);
        if ($update) {
            // Process the update
            $this->processUpdate($update);
        }
        return 'OK';
    }

    protected function processUpdate($update)
    {
        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $messageId = $message['message_id'];
        $text = $message['text'];

        if ($text == '/start') {
            $this->botHelper->sendMenu($chatId, $messageId);
        } else {
            $this->botHelper->sendMessage($chatId, "You said: " . $text, $messageId);
        }
    }
    // public function actionWebhook()
    // {
    //     $update = json_decode(Yii::$app->request->getRawBody(), true);
    //     $notif = new Notif();
    //     $notif->notif_from = "SYSTEM TELELOG";
    //     $notif->notif_to = null;
    //     $notif->notif_date = null; // (new DateTime())->format('Y-m-d H:i:s');
    //     $notif->notif_processed = "false";
    //     $notif->notif_title = "TELE";
    //     $notif->notif_text = Yii::$app->request->getRawBody();

    //     if (!$notif->save()) {
    //         return ($notif->errors)[0];
    //         // return ($notif->errors);
    //     }
    //     if ($update) {
    //         // Process the update
    //         $this->processUpdate($update);
    //     }
    //     return 'OK';
    // }

    // protected function processUpdate($update)
    // {
    //     $message = $update['message'];
    //     $chatId = $message['chat']['id'];
    //     $messageId = $message['message_id'];
    //     $text = $message['text'];

    //     if ($text == '/start') {
    //         $this->sendMenu($chatId, $messageId);
    //     } else {
    //         $this->sendMessage($chatId, "You said: " . $text, $messageId);
    //     }
    // }

    // protected function sendMessage($chatId, $text, $replyToMessageId = null)
    // {
    //     $token = Yii::$app->params['telegramBotToken'];
    //     $url = "https://api.telegram.org/bot$token/sendMessage";

    //     $data = [
    //         'chat_id' => $chatId,
    //         'text' => $text
    //     ];

    //     if ($replyToMessageId) {
    //         $data['reply_to_message_id'] = $replyToMessageId;
    //     }

    //     $options = [
    //         'http' => [
    //             'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
    //             'method'  => 'POST',
    //             'content' => http_build_query($data),
    //         ],
    //     ];
    //     $context  = stream_context_create($options);
    //     file_get_contents($url, false, $context);
    // }

    // protected function sendMenu($chatId, $replyToMessageId = null)
    // {
    //     $keyboard = [
    //         'keyboard' => [
    //             [['text' => 'Option 1']],
    //             [['text' => 'Option 2']],
    //             [['text' => 'Option 3']]
    //         ],
    //         'resize_keyboard' => true,
    //         'one_time_keyboard' => true
    //     ];

    //     $this->sendMessageWithKeyboard($chatId, "Choose an option:", $keyboard, $replyToMessageId);
    // }

    // protected function sendMessageWithKeyboard($chatId, $text, $keyboard, $replyToMessageId = null)
    // {
    //     $token = Yii::$app->params['telegramBotToken'];
    //     $url = "https://api.telegram.org/bot$token/sendMessage";

    //     $data = [
    //         'chat_id' => $chatId,
    //         'text' => $text,
    //         'reply_markup' => json_encode($keyboard)
    //     ];

    //     if ($replyToMessageId) {
    //         $data['reply_to_message_id'] = $replyToMessageId;
    //     }

    //     $options = [
    //         'http' => [
    //             'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
    //             'method'  => 'POST',
    //             'content' => http_build_query($data),
    //         ],
    //     ];
    //     $context  = stream_context_create($options);
    //     file_get_contents($url, false, $context);
    // }

    //--------------------------------------

    // protected function processUpdate($update)
    // {
    //     $message = $update['message'];
    //     $chatId = $message['chat']['id'];
    //     $text = $message['text'];

    //     if ($text == '/start') {
    //         $this->sendMenu($chatId);
    //     } else {
    //         $this->sendMessage($chatId, "Hey You said: " . $text);
    //     }
    // }

    // protected function sendMessage($chatId, $text)
    // {
    //     $token = Yii::$app->params['telegramBotToken'];
    //     $url = "https://api.telegram.org/bot$token/sendMessage";

    //     $data = [
    //         'chat_id' => $chatId,
    //         'thread_id' => Yii::$app->params['thread_id'],
    //         'text' => $text
    //     ];

    //     $options = [
    //         'http' => [
    //             'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
    //             'method'  => 'POST',
    //             'content' => http_build_query($data),
    //         ],
    //     ];
    //     $context  = stream_context_create($options);
    //     file_get_contents($url, false, $context);
    // }

    // protected function sendMenu($chatId)
    // {
    //     $keyboard = [
    //         'keyboard' => [
    //             [['text' => 'Option 1']],
    //             [['text' => 'Option 2']],
    //             [['text' => 'Option 3']]
    //         ],
    //         'resize_keyboard' => true,
    //         'one_time_keyboard' => true
    //     ];

    //     $this->sendMessageWithKeyboard($chatId, "Choose an option:", $keyboard);
    // }

    // protected function sendMessageWithKeyboard($chatId, $text, $keyboard)
    // {
    //     $token = Yii::$app->params['telegramBotToken'];
    //     $url = "https://api.telegram.org/bot$token/sendMessage";

    //     $data = [
    //         'chat_id' => $chatId,
    //         'text' => $text,
    //         'reply_markup' => json_encode($keyboard)
    //     ];

    //     $options = [
    //         'http' => [
    //             'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
    //             'method'  => 'POST',
    //             'content' => http_build_query($data),
    //         ],
    //     ];
    //     $context  = stream_context_create($options);
    //     file_get_contents($url, false, $context);
    // }

}
