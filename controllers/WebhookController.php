<?php

namespace app\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use app\models\Users;
use app\helpers\CustomHelper;
use app\helpers\SecurityHelper;
use app\helpers\TelegramHelper;
use app\jobs\McaJob;
use app\jobs\TelegramJob;
use app\models\Answer;
use app\models\Channel;
use app\models\GitLog;
use app\models\GitStatistic;
use app\models\Group;
use app\models\Pc;
use app\models\Prepaid;
use app\models\Question;
use app\models\Service;
use app\models\TelegramToken;
use app\models\UserTele;
use app\models\Vendor;
use app\models\VendorPr;
use app\models\VendorRequest;
use app\models\VendorTeamMapping;
use ErrorException;
use Exception;
use Google\Cloud\Firestore\FirestoreClient;
use PDO;
use Throwable;
use yii\rest\CreateAction;

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
    private $from_name;
    private $command;

    private $is_admin = false;

    public function actionTelegram()
    {

        try {

            date_default_timezone_set('Asia/Jakarta');
            set_time_limit(60 * 5); //5 menit

            // https://github.com/php-telegram-bot/core/wiki/Securing-&-Hardening-your-Telegram-Bot
            // if (!isset($_GET['secret']) || $_GET['secret'] !== 'p5G6Jz4RQpMV7X8N0P5Zjdjns7BsZ') {
            //     Yii::$app->queue->push(new TelegramJob([
            //         'request' => ['text' => "Telegram Webhook: Invalid secret code"],
            //         'group' => 'security_reports'
            //     ]));
            //     throw new Exception("Nice try");
            // }

            // Set the ranges of valid Telegram IPs.
            // https://core.telegram.org/bots/webhooks#the-short-version

            // $telegram_ip_ranges = [
            //     ['lower' => '149.154.160.0', 'upper' => '149.154.175.255'], // literally 149.154.160.0/20
            //     ['lower' => '91.108.4.0',    'upper' => '91.108.7.255'],    // literally 91.108.4.0/22
            // ];

            // $ipAddress = SecurityHelper::getRealIp();
            // $ip_dec = (float)sprintf("%u", ip2long($ipAddress));
            // $ok = false;

            // foreach ($telegram_ip_ranges as $telegram_ip_range) if (!$ok) {
            //     // Make sure the IP is valid.
            //     $lower_dec = (float)sprintf("%u", ip2long($telegram_ip_range['lower']));
            //     $upper_dec = (float)sprintf("%u", ip2long($telegram_ip_range['upper']));
            //     if ($ip_dec >= $lower_dec and $ip_dec <= $upper_dec) $ok = true;
            // }

            // if (!$ok) {
            //     Yii::$app->queue->push(new TelegramJob([
            //         'request' => ['text' => "Telegram Webhook: Unauthorized IP address '$ipAddress'"],
            //         'group' => 'security_reports'
            //     ]));
            //     throw new Exception("Nice try");
            // }

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
            $this->from_name        = implode(' ', [ArrayHelper::getValue($update, "message.from.first_name", ""), ArrayHelper::getValue($update, "message.from.last_name", "")]);
            $this->is_admin         = in_array($from_username, $this->bot_admin);
            $text                   = ArrayHelper::getValue($update, "message.text", null);

            if (isset($update['callback_query'])) {
                if ($update['callback_query']['message']['reply_to_message']['from']['username'] == $update['callback_query']['from']['username']) {
                    $callback_reply_to_message = $update['callback_query']['message']['reply_to_message']['text'];

                    if (preg_match('/^\/pull(.*)$/', $callback_reply_to_message)) {

                        $contextOptions = [
                            "ssl" => [
                                "verify_peer" => false,
                                "verify_peer_name" => false,
                            ],
                        ];

                        $response = 'no message';
                        if ($update['callback_query']['data'] )
                        {
                            $response = $update['callback_query']['data'];
                        }

                        $encodedKeyboard = json_encode([
                            'inline_keyboard' => [[]]
                        ]);
                        
                        // return TelegramHelper::editMessageText([
                        //     'chat_id' => $update['callback_query']['message']['chat']['id'],
                        //     'message_id' => $update['callback_query']['message']['message_id'],
                        //     'parse_mode' => 'html',
                        //     'text' => "<pre>$response</pre>",
                        //     'reply_markup' => $encodedKeyboard
                        // ]);
                        return TelegramHelper::sendMessage(['reply_to_message_id' => $update['callback_query']['message']['message_id'], 'text' => $response ], $update['callback_query']['message']['chat']['id']);

                    }

                }
            }
            else if (isset($update['message'])) {
                if (preg_match('/^\/(?<command>[\w-]+)(?<username>@' . $this->bot_username . '+)?(((?:\s{1}(?<param>.*))))?$/', $text, $match)) {

                    $command = ArrayHelper::getValue($match, 'command', "");
                    $this->command = $command;;
                    $params  = (isset($match['param']) && !empty(trim($match['param']))) ? explode(" ", trim($match['param'])) : [];

                    switch ($match['command']) {
                        case "id";
                            return $this->chatId();
                            break;
                        case "coba";
                            return $this->coba();
                            break;
                        case "start";
                            return $this->start();
                            break;
                        case "list";
                            return $this->getlist();
                            break;
                        case "kanal";
                            return $this->getchannel();
                            break;
                        case "sambungkan";
                            return $this->sambungkan($params);
                        case "pilih";
                            return $this->pilih($params);
                        default;
                            return $this->defaultAction($params);
                            break;
                    }
                }
            } else {
                return "invalid request";
            }
        } catch (Throwable $e) {
            TelegramHelper::sendMessage(['text' => $e->getMessage()],  -118356001);
            return $e->getMessage();
        }
    }
    
    private function coba()
    {
        $keyboard = [
            ['7', '8', '9'],
            ['4', '5', '6'],
            ['1', '2', '3'],
                 ['0']
        ];
                

        $encodedKeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => 'Profile', 'callback_data' => "profile"],
                    ['text' => 'Biro Jodoh', 'callback_data' => "biro_jodoh"],
                    
                ],
                [
                    ['text' => 'Donasi', 'callback_data' => "donasi"],
                    ['text' => 'Unit Usaha', 'callback_data' => "unit_usaha"],
                    
                ],
                [
                    ['text' => 'Pendaftaran Sekolah', 'callback_data' => "pendaftaran_sekolah"],
                ]
            ]
        ]);
        
        return TelegramHelper::sendMessage(['reply_to_message_id' => $this->message_id, 'text' => 'Selamat Datang, Silahkan pilih menu berikut', 'reply_to_message_id' => $this->message_id, 'reply_markup' => $encodedKeyboard], $this->chat_id);
        
        // return TelegramHelper::editMessageText([
        //     'chat_id' => $this->chat_id, 
        //     'message_id' => $this->message_id,
        //     'text' => 'Selamat Datang, Silahkan pilih menu berikut',
        //     'reply_markup' => $encodedKeyboard
        // ]);
    }

    private function chatId()
    {
        return TelegramHelper::sendMessage(['reply_to_message_id' => $this->message_id, 'text' => "Chat ID : " . $this->chat_id], $this->chat_id);
    }

    private function start()
    {
        $message = "Selamat datang di layanan Rochat.id\nSilahkan ketik layanan dengan format <pre>/sambungkan &lt;nama layanan&gt;</pre>";

        $user = UserTele::findOne(['telegram_id' => $this->from_id]);

        if (!$user) {
            $user = new UserTele;
        }

        $user->name = $this->from_name;
        $user->telegram_id = $this->from_id;
        $user->telegram_username = $this->from_username;
        $user->service = 0;

        if (!$user->save()) {
            throw new Exception(current($user->errors)[0]);
        }

        return TelegramHelper::sendMessage(['reply_to_message_id' => $this->message_id, 'text' => $message], $this->chat_id);
    }

    private function getchannel()
    {

        $user = UserTele::findOne(['telegram_id' => $this->from_id]);

        if (!$user) {
            $user = new UserTele;
            $user->name = $this->from_name;
            $user->telegram_id = $this->from_id;
            $user->telegram_username = $this->from_username;

            if (!$user->save()) {
                throw new Exception(current($user->errors)[0]);
            }
        }

        $service = Channel::find()->all();

        if (!$service) {
            return $this->reply("Mohon maaf, Belum ada Channel, hubungi CS rochat di @rochat.id");
        }
        $msg = "List Channel yang terdaftar pada rochat.id : \n";
        $last_code = null;
        $i = 1;
        foreach ($service as $key) {
            $msg .= "<pre>" . $i . ". " . $key->code . "</pre>\n";
            $last_code = $key->code;
            $i++;
        }

        $msg .= "\nContoh ketik :\n<pre>/sambungkan " . $last_code . "</pre>\n";

        return $this->reply($msg);
    }

    private function getlist()
    {

        $user = UserTele::findOne(['telegram_id' => $this->from_id]);

        if (!$user) {
            $user = new UserTele;
            $user->name = $this->from_name;
            $user->telegram_id = $this->from_id;
            $user->telegram_username = $this->from_username;

            if (!$user->save()) {
                throw new Exception(current($user->errors)[0]);
            }
        }

        $service = Service::find()->all();

        if (!$service) {
            return $this->reply("Mohon maaf, Belum ada Layanan, hubungi CS rochat di @rochat.id");
        }
        $msg = "List layanan yang terdaftar pada rochat.id : \n";
        $last_code = null;
        $i = 1;
        foreach ($service as $key) {
            $msg .= "<pre>" . $i . ". " . $key->code . "</pre>\n";
            $last_code = $key->code;
            $i++;
        }

        $msg .= "\nContoh ketik :\n<pre>/pilih " . $last_code . "</pre>\n";

        return $this->reply($msg);
    }

    private function sambungkan($params)
    {

        if (count($params) != 1) {
            return $this->reply("Format pesan tidak valid, silahkan ketik dengan format <pre>/sambungkan &lt;spasi&gt;&lt;nama kanal&gt;</pre>");
        }

        $user = UserTele::findOne(['telegram_id' => $this->from_id]);

        if (!$user) {
            $user = new UserTele;
            $user->name = $this->from_name;
            $user->telegram_id = $this->from_id;
            $user->telegram_username = $this->from_username;

            if (!$user->save()) {
                throw new Exception(current($user->errors)[0]);
            }
        }

        $channel = Channel::findOne(['code' => trim($params[0])]);

        if (!$channel) {
            return $this->reply("Kanal " . $params[0] . " tidak ditemukan");
        }

        $user->channel = $channel->id;
        if (!$user->save()) {
            throw new Exception(current($user->errors)[0]);
        }

        $message = "Anda telah terhubung dengan kanal <strong>" . $channel->name . "</strong>,\nBerikut layanan kami yang bisa anda pilih :";

        $services = Service::find()->where(['channel_id' => $channel->id])->all();
        $i = 1;
        foreach ($services as $service) {
            $message .= "<pre>" . $i . ". " . $service->name . " &lt;" . $service->code . "&gt;</pre>";
            $i++;
        }

        $message .= "Silahkan pilih layanan kami dengan cara ketik :  <pre>/pilih " . current($services)->code . "</pre>";

        $this->reply($message);

        if (!empty($channel->greeting)) {
            $this->reply($channel->greeting);
        }
    }

    private function pilih($params)
    {

        if (count($params) != 1) {
            return $this->reply("Format pesan tidak valid, silahkan ketik dengan format <pre>/sambungkan &lt;spasi&gt;&lt;nama layanan&gt;</pre>");
        }

        $user = UserTele::findOne(['telegram_id' => $this->from_id]);

        if (!$user) {
            $user = new UserTele;
            $user->name = $this->from_name;
            $user->telegram_id = $this->from_id;
            $user->telegram_username = $this->from_username;

            if (!$user->save()) {
                throw new Exception(current($user->errors)[0]);
            }
        }

        $service = Service::findOne(['code' => trim($params[0])]);

        if (!$service) {
            return $this->reply("Layanan tidak ditemukan");
        }

        $user->service = $service->id;
        $user->question = 1;
        if (!$user->save()) {
            throw new Exception(current($user->errors)[0]);
        }

        $this->reply("Berhasil sambungkan ke layanan <strong>" . $service->name . "</strong>");

        $greeting = "";

        if (!empty($service->greeting)) {
            $greeting .= $service->greeting . "\n\n";
        }

        $firstQuestion = Yii::$app->db->createCommand("SELECT * FROM question
            INNER JOIN section ON question.section_id = section.id
            INNER JOIN service ON section.service_id = service.id
            INNER JOIN `channel` ON service.channel_id = `channel`.id
            WHERE `channel`.id = :channel_id
            AND service.id = :service_id
            ORDER BY question.`order` 
            LIMIT 1;")
            ->bindValue(':channel_id', $user->channel, PDO::PARAM_INT)
            ->bindValue(':service_id', $user->service, PDO::PARAM_INT)
            ->queryOne();

        if ($firstQuestion) {
            $greeting .= $firstQuestion['question'] . "\n";
            $greeting .= "Balas dengan formmat : <pre>/" . $firstQuestion['keyword'] . " &lt;spasi&gt;&lt;jawaban&gt;</pre>";
        }

        $this->reply($greeting);
    }

    private function reply($text)
    {
        return TelegramHelper::sendMessage(['reply_to_message_id' => $this->message_id, 'text' => $text], $this->chat_id);
    }

    private function defaultAction($params)
    {

        $user = UserTele::findOne(['telegram_id' => $this->from_id]);

        if (!$user) {
            $user = new UserTele;
            $user->name = $this->from_name;
            $user->telegram_id = $this->from_id;
            $user->telegram_username = $this->from_username;

            if (!$user->save()) {
                throw new Exception(current($user->errors)[0]);
            }
        }

        $nextQuestion = Yii::$app->db->createCommand("SELECT question.* FROM question
            INNER JOIN section ON question.section_id = section.id
            INNER JOIN service ON section.service_id = service.id
            INNER JOIN `channel` ON service.channel_id = `channel`.id
            WHERE `channel`.id = :channel_id
            AND service.id = :service_id
            AND question.`keyword` = :keyword
            ORDER BY question.`order` 
            LIMIT 1;")
            ->bindValue(':channel_id', $user->channel, PDO::PARAM_INT)
            ->bindValue(':service_id', $user->service, PDO::PARAM_INT)
            ->bindValue(':keyword', $this->command, PDO::PARAM_STR)
            ->queryOne();

        if ($this->command == $nextQuestion['keyword']) {

            $cari = Answer::find()
                ->where(["question_id" => $nextQuestion['id']])
                ->andWhere(["user_id" => $user->id])
                ->one();

            if (!$cari) {
                $answer = new Answer;
                $answer->question_id = $nextQuestion['id'];
                $answer->user_id = $user->id;
                $answer->answer = implode(' ', $params);

                if (!$answer->save()) {
                    throw new Exception(current($answer->errors)[0]);
                }
            } else {
                $cari->answer = implode(' ', $params);
                $cari->modified_at = date('Y-m-d H:i:s');

                if (!$cari->save()) {
                    throw new Exception(current($cari->errors)[0]);
                }
            }

            $user->question += 1;
            if (!$user->save()) {
                throw new Exception(current($user->errors)[0]);
            }

            $message = "";

            $nextQuestion = Yii::$app->db->createCommand("SELECT * FROM question
            INNER JOIN section ON question.section_id = section.id
            INNER JOIN service ON section.service_id = service.id
            INNER JOIN `channel` ON service.channel_id = `channel`.id
            WHERE `channel`.id = :channel_id
            AND service.id = :service_id
            AND question.`order` = :question_order
            ORDER BY question.`order` 
            LIMIT 1;")
                ->bindValue(':channel_id', $user->channel, PDO::PARAM_INT)
                ->bindValue(':service_id', $user->service, PDO::PARAM_INT)
                ->bindValue(':question_order', $user->question, PDO::PARAM_INT)
                ->queryOne();

            $this->reply("Terima kasih, data anda telah kami terima");

            if ($nextQuestion) {
                $message = $nextQuestion['question'] . "\n";
                $message .= "Balas dengan formmat : <pre>/" . $nextQuestion['keyword'] . " &lt;spasi&gt;&lt;jawaban&gt;</pre>";

                $this->reply($message);
            }
        } else {
            return TelegramHelper::sendMessage(['reply_to_message_id' => $this->message_id, 'text' => 'Perintah tidak dikenal'], $this->chat_id);
        }
    }
}
