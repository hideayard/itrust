<?php

namespace app\components;

use Yii;
use yii\base\Component;

class TelegramBotHelper extends Component
{
    public $token;

    public function __construct($token, $config = [])
    {
        $this->token = $token;
        parent::__construct($config);
    }

    public function sendMessage($chatId, $text, $replyToMessageId = null)
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text
        ];

        if ($replyToMessageId) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }

        return $this->sendRequest($url, $data);
    }

    public function sendMenu($chatId, $replyToMessageId = null)
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

        $text = "Choose an option:";
        $this->sendMessageWithKeyboard($chatId, $text, $keyboard, $replyToMessageId);
    }

    public function sendMessageWithKeyboard($chatId, $text, $keyboard, $replyToMessageId = null)
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode($keyboard)
        ];

        if ($replyToMessageId) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }

        return $this->sendRequest($url, $data);
    }

    protected function sendRequest($url, $data)
    {
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context  = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }
}
