<?php

namespace app\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use app\helpers\SecurityHelper;
use app\helpers\TelegramHelper;
use Throwable;

class WebhookController extends Controller
{

    public $enableCsrfValidation = false;

    private $bot_token;
    private $bot_username;
    private $bot_admin;

    private $message_id;
    private $chat_id;
    private $chat_type;
    // private $response;
    private $from_id;
    private $from_username;

    private $is_admin = false;

    public function actionTelegram()
    {

        try {

            date_default_timezone_set('Asia/Jakarta');
            set_time_limit(60 * 5); //5 menit

            // SecurityHelper::telegram('p5G6Jz4RQpMV7X8N0P5Zjdjns7BsZ');

            $content  = urldecode(file_get_contents("php://input"));
            $update   = json_decode($content, true);

            // file_put_contents("logs.txt", $content);

            $this->bot_token        = Yii::$app->params['telegramBotToken'];
            $this->bot_username     = Yii::$app->params['telegramBotUsername'];
            $this->bot_admin        = Yii::$app->params['telegramBotAdmin'];
            $this->message_id       = ArrayHelper::getValue($update, 'message.message_id', null);
            $this->chat_id          = ArrayHelper::getValue($update, 'message.chat.id', null);
            $this->chat_type        = ArrayHelper::getValue($update, 'message.chat.type', "");
            $this->from_id          = ArrayHelper::getValue($update, "message.from.id", "");
            $this->from_username    = ArrayHelper::getValue($update, "message.from.username", "");
            $from_username          = ArrayHelper::getValue($update, "message.from.username", "");
            $this->is_admin         = in_array($from_username, $this->bot_admin);
            $text                   = ArrayHelper::getValue($update, "message.text", null);

            if (isset($update['message'])) {
                if (preg_match('/^\/(?<command>[\w-]+)(?<username>@' . $this->bot_username . '+)?(((?:\s{1}(?<param>.*))))?$/', $text, $match)) {

                    $command = ArrayHelper::getValue($match, 'command', "");
                    $params  = (isset($match['param']) && !empty(trim($match['param']))) ? explode(" ", trim($match['param'])) : [];

                    switch ($match['command']) {
                        case "id";
                            return $this->chatId();
                            break;
                        case "pull";
                            return $this->pull();
                            break;
                        default;
                            return TelegramHelper::sendMessage(['reply_to_message_id' => $this->message_id, 'text' => 'Perintah tidak dikenal'], $this->chat_id);
                            break;
                    }
                }
            } else {
                return "Invalid request";
            }
        } catch (Throwable $e) {
            Yii::error($e);
            return $e->getMessage();
        }
    }

    private function chatId()
    {
        return TelegramHelper::sendMessage(['reply_to_message_id' => $this->message_id, 'text' => "Chat ID : " . $this->chat_id], $this->chat_id);
    }

    private function pull()
    {
        if (!$this->is_admin) {
            return TelegramHelper::sendDocument(['reply_to_message_id' => $this->message_id, 'document' => Yii::$app->params['webhookTelegramGif']], $this->chat_id);
        }

        $contextOptions = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];

        $response = 'no message';
        chdir("/var/www/aifarm.id");
        $response = shell_exec("sudo git pull");

        return TelegramHelper::sendMessage(['reply_to_message_id' => $this->message_id, 'text' => "<pre>" . htmlentities($response) . "</pre>", 'reply_to_message_id' => $this->message_id], $this->chat_id);
    }
}
