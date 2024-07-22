<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class BotController extends Controller
{
    public $enableCsrfValidation = false;

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
        $text = $message['text'];

        if ($text == '/start') {
            $this->sendMenu($chatId);
        } else {
            $this->sendMessage($chatId, "Hey You said: " . $text);
        }
    }

    protected function sendMessage($chatId, $text)
    {
        $token = Yii::$app->params['telegramBotToken'];
        $url = "https://api.telegram.org/bot$token/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context  = stream_context_create($options);
        file_get_contents($url, false, $context);
    }

    protected function sendMenu($chatId)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => 'Option 1']],
                [['text' => 'Option 2']],
                [['text' => 'Option 3']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];

        $this->sendMessageWithKeyboard($chatId, "Choose an option:", $keyboard);
    }

    protected function sendMessageWithKeyboard($chatId, $text, $keyboard)
    {
        $token = Yii::$app->params['telegramBotToken'];
        $url = "https://api.telegram.org/bot$token/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode($keyboard)
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context  = stream_context_create($options);
        file_get_contents($url, false, $context);
    }
}
