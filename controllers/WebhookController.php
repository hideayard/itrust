<?php

namespace app\controllers;

use PDO;
use Yii;
use DateTime;
use Exception;
use Throwable;
use app\models\Pc;
use ErrorException;
use app\jobs\McaJob;
use yii\helpers\Url;
use app\models\Group;
use app\models\Notif;
use app\models\Users;
use app\models\Answer;
use app\models\GitLog;
use app\models\Vendor;
use app\models\Channel;
use app\models\Prepaid;
use app\models\Service;
use yii\web\Controller;
use app\models\Question;
use app\models\UserTele;
use app\models\VendorPr;
use app\jobs\TelegramJob;
use app\models\CloseOrder;
use yii\rest\CreateAction;
use app\models\GitStatistic;
use yii\helpers\ArrayHelper;
use app\helpers\CustomHelper;
use app\models\TelegramToken;
use app\models\VendorRequest;
use app\helpers\SecurityHelper;
use app\helpers\TelegramHelper;
use app\models\VendorTeamMapping;
use Google\Cloud\Firestore\FirestoreClient;

$baseImageUrl = Url::base() . '/images';

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
    private $callback_query;
    private $is_admin = false;
    private static $defaultChatId =  -1002149598297; //bot common

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
            $this->callback_query   = ArrayHelper::getValue($update, 'callback_query', null);
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

                $callback_reply_to_message = $update['callback_query']['message']['reply_to_message']['text'];

                if ( ( $update['callback_query']['message']['reply_to_message']['from']['username'] == $update['callback_query']['from']['username'] ) || $this->bot_admin) {
                    $callback_reply_to_message = $update['callback_query']['message']['reply_to_message']['text'];
                    $response = "You (@" . $update['callback_query']['from']['username'] . ") choose " . $update['callback_query']['data'];

                    if (preg_match('/^\/pull(.*)$/', $callback_reply_to_message)) {

                        $contextOptions = [
                            "ssl" => [
                                "verify_peer" => false,
                                "verify_peer_name" => false,
                            ],
                        ];


                        $encodedKeyboard = json_encode([
                            'inline_keyboard' => [[]]
                        ]);

                        return TelegramHelper::editMessageText([
                            'chat_id' => $update['callback_query']['message']['chat']['id'],
                            'message_id' => $update['callback_query']['message']['message_id'],
                            'parse_mode' => 'html',
                            'text' => "<pre>$response</pre>",
                            'reply_markup' => $encodedKeyboard
                        ]);
                    } elseif (preg_match('/^\/build(.*)$/', $callback_reply_to_message)) {

                        $matches = [];
                        if (preg_match('/^(?<project_name>[\w_]+)\\|(?<server>[\w_]+)$/', $update['callback_query']['data'], $matches)) {

                            $projectName = $matches['project_name'];
                            $server = $matches['server'];

                            if ($matches['server'] == 'no_server') {

                                $projectName = $matches['project_name'];

                                // Yii::error("1st step: " . $update['callback_query']['data']);
                                // Yii::error("1st step: " . $projectName . " (parsed)");

                                $encodedKeyboard = json_encode([
                                    'inline_keyboard' => [
                                        [
                                            ['text' => 'Server G', 'callback_data' => "$projectName|server_g"],
                                            ['text' => 'Server I', 'callback_data' => "$projectName|server_i"],
                                        ],
                                    ]
                                ]);

                                TelegramHelper::editMessageText([
                                    'chat_id' => $update['callback_query']['message']['chat']['id'],
                                    'message_id' => $update['callback_query']['message']['message_id'],
                                    'text' => 'Server yang mana?',
                                    'reply_markup' => $encodedKeyboard
                                ]);
                            } else {

                                // Yii::error("2nd step: " . $update['callback_query']['data']);
                                // Yii::error("2nd step: " . $matches['project_name'] . " (parsed)");

                                $url = '';
                                $host = ($server == 'server_g') ? '139.99.8.31' : '194.233.75.76';

                                if ($projectName == 'ptnw') {
                                    $url = "http://$host:9090/job/ptnw-3.0/build?token=5ug10n0";
                                } elseif ($projectName == "tsel") {
                                    $url = "http://$host:9090/job/dashboard-oss-v2-frontend/build?token=5ug10n0";
                                } elseif ($projectName == "bod") {
                                    $url = "http://$host:9090/job/bod-frontend/build?token=73ho5tdf73";
                                } elseif ($projectName == "cs") {
                                    $url = "http://$host:9090/job/ptnw-3.0-cs/build?token=237ydn3cfj";
                                } elseif ($projectName == "doc") {
                                    $url = "http://$host:9090/job/ptnw-3.0-doc/build?token=943ncihrci3o3";
                                } elseif ($projectName == "training") {
                                    $url = "http://$host:9090/job/ptnw-3.0-training/build?token=34gb39g3tetjew";
                                } elseif ($projectName == "payment") {
                                    $url = "http://$host:9090/job/ptnw-3.0-payment/build?token=8dHl27X84d8";
                                } elseif ($projectName == "pmo") {
                                    $url = "http://$host:9090/job/ptnw-3.0-pmo/build?token=347cn83engcfy3ns";
                                } elseif ($projectName == "fakturonline") {
                                    $url = "http://$host:9090/job/fakturonline/build?token=459y5tv54hvtmo3i";
                                }

                                $encodedKeyboard = json_encode([
                                    'inline_keyboard' => [[]]
                                ]);

                                TelegramHelper::editMessageText([
                                    'chat_id' => $update['callback_query']['message']['chat']['id'],
                                    'message_id' => $update['callback_query']['message']['message_id'],
                                    'text' => 'Sedang diproses',
                                    'reply_markup' => $encodedKeyboard
                                ]);

                                $arrContextOptions = [
                                    "ssl" => [
                                        "verify_peer"      => false,
                                        "verify_peer_name" => false,
                                    ],
                                ];

                                return file_get_contents($url, false, stream_context_create($arrContextOptions));
                            }
                        } else {
                            $encodedKeyboard = json_encode([
                                'inline_keyboard' => [[]]
                            ]);

                            TelegramHelper::editMessageText([
                                'chat_id' => $update['callback_query']['message']['chat']['id'],
                                'message_id' => $update['callback_query']['message']['message_id'],
                                'text' => 'Invalid callback data',
                                'reply_markup' => $encodedKeyboard
                            ]);
                        }
                    } elseif (preg_match('/^\/restart(.*)$/', $callback_reply_to_message)) {
                        $response = 'Invalid callback data';

                        if ($update['callback_query']['data'] == 'wa_cowok') {
                            $response = shell_exec("/var/scriptsh/restart_wa_cowok.sh > /dev/null 2>/dev/null &");
                        } elseif ($update['callback_query']['data'] == 'wa_cewek') {
                            $response = shell_exec("/var/scriptsh/restart_wa_cewek.sh > /dev/null 2>/dev/null &");
                        }

                        $encodedKeyboard = json_encode([
                            'inline_keyboard' => [[]]
                        ]);

                        return TelegramHelper::editMessageText([
                            'chat_id' => $update['callback_query']['message']['chat']['id'],
                            'message_id' => $update['callback_query']['message']['message_id'],
                            'parse_mode' => 'html',
                            'text' => "Selesai",
                            'reply_markup' => $encodedKeyboard
                        ]);
                    } else {
                        $this->handleCallbackQuery($update['callback_query']);

                        // if (preg_match('/^\/check(.*)$/', $callback_reply_to_message)) {
                        //     // $this->matchCommand('check', $params);
                        //     // $this->check();
                        //     // $gifUrl = 'https://itrust-care.com/' . Url::base() . '/images/no.gif';
                        //     // $this->sendGif($gifUrl);
                        //     TelegramHelper::sendMessage(['text' => "You (@" . $update['callback_query']['from']['username'] . ") choose " . $update['callback_query']['data']], $update['callback_query']['id']);
                        // } else {
                        //     // $chat_id = $update['callback_query']['id'];
                        //     // $message_id = $update['callback_query']['message']['chat']['id'];
                        //     TelegramHelper::sendMessage(
                        //         [
                        //             'reply_to_message_id' => $update['callback_query']['id'],
                        //             'text' => "MASUK ELSE 1". $update['callback_query']['data']
                        //         ],
                        //         $update['callback_query']['message']['chat']['id']
                        //     );

                        //     TelegramHelper::sendMessage(
                        //         [
                        //             'reply_to_message_id' => $update['callback_query']['message']['chat']['id'],
                        //             'text' => "MASUK ELSE 2"
                        //         ],
                        //         $update['callback_query']['id']
                        //     );

                        //     TelegramHelper::answerCallbackQuery([
                        //         'callback_query_id' => $update['callback_query']['id'],
                        //         'text' => ' ya! 293 ',
                        //         'show_alert' => true
                        //     ]);
                        // }
                    }
                } else {
                    return TelegramHelper::answerCallbackQuery([
                        'callback_query_id' => $update['callback_query']['id'],
                        'text' => 'gak boleh nakal ya!',
                        'show_alert' => true
                    ]);
                }

                //     if ($update['callback_query']) {
                //         $this->handleCallbackQuery($update['callback_query']);
                //     }
                //     if ($update['callback_query']['message']['reply_to_message']['from']['username'] == $update['callback_query']['from']['username']) {
                //         $callback_reply_to_message = $update['callback_query']['message']['reply_to_message']['text'];

                // }
                // }
                // else if (isset($update['message'])) {
                // }

            } else if (isset($update['message'])) {
                if (preg_match('/^\/(?<command>[\w-]+)(?<username>@' . $this->bot_username . '+)?(((?:\s{1}(?<param>.*))))?$/', $text, $match)) {

                    $command = ArrayHelper::getValue($match, 'command', "");
                    $this->command = $command;;
                    $params  = (isset($match['param']) && !empty(trim($match['param']))) ? explode(" ", trim($match['param'])) : [];
                    $this->matchCommand($match['command'], $params);
                }
            } else {
                return "invalid request";
            }
        } catch (Throwable $e) {
            TelegramHelper::sendMessage(['text' => $e->getMessage()],  -1002149598297);
            return $e->getMessage();
        }
    }

    private function matchCommand($val, $params, $callbackQuery = null)
    {
        switch ($val) {
            case "id";
                return $this->chatId();
                break;
            case "gif";
                $gifUrl = 'https://itrust-care.com/' . Url::base() . '/images/no.gif';
                return $this->sendGif($gifUrl);
                break;
            case "menu";
                return $this->menu();
                break;
            case "start";
                return $this->start();
                break;
            case "outlook";
                return $this->outlook($callbackQuery);
                break;
            case "maxop";
                return $this->maxop();
                break;
            case "setmaxop";
                return $this->setmaxop($params);
                break;
            case "close_all";
                return $this->close_all();
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
            case "check";
                return $this->check($callbackQuery);
            default;
                return $this->defaultAction($params);
                break;
        }
    }

    private function sendGif($gifUrl)
    {
        if (!$gifUrl) {
            $gifUrl = 'https://itrust-care.com/' . Url::base() . '/images/no.gif';
        }
        // TelegramHelper::sendMessage(['text' => "You choose gif : " . $this->message_id ." - " . $gifUrl ], $this->chat_id);
        $data = [
            'chat_id' => $this->chat_id,
            'animation' => $gifUrl,
            'caption' => "Nope!"
        ];
        TelegramHelper::sendTelegramRequest('sendAnimation', $data);
    }

    private function notifLog($from, $title, $callbackQueryId, $chatId, $data, $log_string)
    {
        $notif = new Notif();
        $notif->notif_from = $from;
        $notif->notif_to = null;
        $notif->notif_date =  (new DateTime())->format('Y-m-d H:i:s');
        $notif->notif_processed = "false";
        $notif->notif_title = "title - " . $title;
        $notif->notif_text = "text " . $callbackQueryId . " | chatID=" . $chatId . " | data=" . $data . " | log=" . $log_string;

        if (!$notif->save()) {
            return TelegramHelper::sendMessage(['reply_to_message_id' => $callbackQueryId, 'text' => "ERROR notifLog" . $notif->errors], $chatId);
        }
    }

    protected function handleCallbackQuery($callbackQuery)
    {
        $chat_id = $callbackQuery['id'];
        $message_id = $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'];

        $message = "You (@" . $callbackQuery['from']['username'] . ") choose " . $data;
        // $log[] = "callbackId=" . $callbackQuery['id'];
        // $log[] = "callbackfromID=" . $callbackQuery['from']['id'];
        // $log[] = "callbackfromUsername=" . $callbackQuery['from']['username'];
        // $log[] = "message=" . $message;
        // $log_string = implode(", ", $log);
        // $this->notifLog('handleCallbackQuery', 'handleCallbackQuery', $message_id, $chat_id, $data, $log_string);
        //logs to notif
        if (($callbackQuery['message']['reply_to_message']['from']['username'] == $callbackQuery['from']['username']) || $callbackQuery['message']['reply_to_message']['from']['username'] == 'hideayard' || $this->bot_admin) {
            TelegramHelper::sendMessage(['text' => "" . $message . "."], $message_id);
            $this->matchCommand($data, null, $callbackQuery);
        } else {
            return TelegramHelper::answerCallbackQuery([
                'callback_query_id' => $callbackQuery['id'],
                'text' => 'Ga boleh ya!',
                'show_alert' => true
            ]);
        }
    }

    private function check($callbackQuery = null)
    {
        try {
            $message_id     = $this->message_id;
            $from_username  = $this->from_username;
            $from_id        = $this->from_id;
            $chat_id        = $this->chat_id;
            $message = "no callbackQuery";

            if ($callbackQuery) {
                $message_id = $callbackQuery['id'];
                $from_username = $callbackQuery['from']['username'] ?? " _username_ ";
                $from_id = $callbackQuery['from']['id'] ?? " _id_ ";
                $chat_id = $callbackQuery['message']['chat']['id'];
                $message = "callbackQuery";

                $this->notifLog('check', 'check', $message_id, $chat_id, $from_id, $from_username);
            }

            // $debug = "check -" . $message . " - from_id : " . $from_id . " - from_username : " . $from_username . " - chat_id : " . $chat_id . " - message_id : " . $message_id;
            // TelegramHelper::sendMessage(['text' => $debug], $this->defaultChatId);

            $user = $this->getUser($from_id, $from_username);
            if (!$user) {
                return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "User " . $from_username . "(" . $from_id . ")" . " <b>Belum Terdaftar</b>"], $chat_id);
            }
            return TelegramHelper::sendMessage(['text' => "User " . $from_username . "(" . $from_id . ")" . " <b>Terdaftar</b>"], $chat_id);
        } catch (\Exception $ex) {
            return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "ERROR - " . $ex->getMessage()], $chat_id);
        }
    }

    private function outlook($callbackQuery = null)
    {
        try {
            $message_id = $this->message_id ?? "";
            $from_username = $this->from_username ?? "";
            $from_id = $this->from_id ?? "";
            $chat_id = $this->chat_id ?? "";
            $message = "no callbackQuery";

            if ($callbackQuery) {
                $message_id = $callbackQuery['id'];
                $from_username = $callbackQuery['from']['username'] ?? " _username_ ";
                $from_id = $callbackQuery['from']['id'] ?? " _id_ ";
                $chat_id = $callbackQuery['message']['chat']['id'];
                $message = "callbackQuery";
            }

            //  $debug = "outlook -" . $message . " - from_id : " . $from_id . " - from_username : " . $from_username . " - chat_id : " . $chat_id . " - message_id : " . $message_id;
            // TelegramHelper::sendMessage(['text' => $debug], $this->chat_id);
            // TelegramHelper::sendMessage(['text' => "outlook-" . $message], $chat_id);

            $this->notifLog('outlook', 'outlook', $message_id, $chat_id, $from_id, $from_username);

            $user = $this->getUser($from_id, $from_username);
            if (!$user) {
                TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "Outlook Error - User " . $from_username . "(" . $from_id . ")" . " <b>Belum Terdaftar</b>"], $this->chat_id);
                // $gifUrl = 'https://itrust-care.com/' . Url::base() . '/images/no.gif';
                // return $this->sendGif($gifUrl);
                return TelegramHelper::sendDocument(['reply_to_message_id' => $this->message_id, 'document' => Yii::$app->params['webhookTelegramGif']], $this->chat_id);
            }

            $account = $user->user_account ?? null;
            if ($account) {
                $order = new CloseOrder();
                $order->order_account = $account;
                $order->order_cmd = "outlook";
                $order->order_status = 1;
                $order->order_date =  (new DateTime())->format('Y-m-d H:i:s');
                $emptyKeyboard = json_encode(['inline_keyboard' => [[]]]);

                if ($callbackQuery) {
                    if (!$order->save()) {
                        // TelegramHelper::editMessageText([
                        //     'chat_id' => $chat_id,
                        //     'message_id' => $message_id,
                        //     'text' => "Outlook command <ERROR</b> @" . $from_username . "(" . ($order->errors)[0] . ")",
                        //     'reply_markup' => $emptyKeyboard
                        // ]);

                        TelegramHelper::sendMessage([
                            'reply_to_message_id' => $message_id,
                            'text' => "Outlook command <ERROR</b> @" . $from_username . "(" . ($order->errors)[0] . ")"
                        ], $this->chat_id);
                    }
                    TelegramHelper::sendMessage(['text' => "Outlook command <bSent</b>. @" . $from_username . "(" . $from_id . ")"], $chat_id);

                    // TelegramHelper::editMessageText([
                    //     'chat_id' => $chat_id,
                    //     'message_id' => $message_id,
                    //     'text' => "Outlook command <bSent</b> @" . $from_username . "(" . $from_id . ")",
                    //     'reply_markup' => $emptyKeyboard
                    // ]);
                    TelegramHelper::sendMessage([
                        'reply_to_message_id' => $chat_id,
                        'text' => "Outlook command <bSent</b>. @" . $from_username . "(" . $from_id . ")"
                    ], $this->chat_id);
                } else {
                    if (!$order->save()) {
                        TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "Outlook command <ERROR</b> @" . $from_username . "(" . ($order->errors)[0] . ")"], $chat_id);
                    }
                    // return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "Outlook command <bSent</b> for user " . $account], $chat_id);
                    TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "Outlook command <bSent</b> @" . $from_username . "(" . $from_id . ")"], $this->chat_id);
                }
            } else {
                TelegramHelper::sendMessage(['text' => "<b>Failed</b> to send command for user  @" . $from_username . "(" . $from_id . ")"], $this->chat_id);

                // TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "<b>Failed</b> to send command for user  @" . $from_username . "(" . $from_id . ")"], $chat_id);
            }
        } catch (\Exception $ex) {
            TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "ERROR - " . $ex->getMessage()], $this->chat_id);
        }
    }

    private function close_all()
    {
        try {
            $message_id     = $this->message_id;
            $from_username  = $this->from_username;
            $from_id        = $this->from_id;
            $chat_id        = $this->chat_id;
            $callbackQuery  = $this->callback_query;

            if ($callbackQuery) {
                $message_id = $callbackQuery['id'];
                $from_username = $callbackQuery['from']['username'] ?? " _username_ ";
                $from_id = $callbackQuery['from']['id'] ?? " _id_ ";
                $chat_id = $callbackQuery['message']['chat']['id'];
            }
            $this->notifLog('close_all', 'close_all', $message_id, $chat_id, $from_id, $from_username);

            $user = $this->getUser($from_id, $from_username);
            if (!$user) {
                return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "User " . $from_username . "(" . $from_id . ")" . " <b>Belum Terdaftar</b>"], $chat_id);
            }
            $account = $user->user_account ?? null;

            if ($account) {
                $order = new CloseOrder();
                $order->order_account = $account;
                $order->order_cmd = "close_all";
                $order->order_status = 0;
                $order->order_date =  (new DateTime())->format('Y-m-d H:i:s');
                if (!$order->save()) {
                    return ($order->errors)[0];
                }
                return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "CLOSE ALL ORDER command <bSent</b> for user " . $account], $chat_id);
            } else {
                return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "FAILED to CLOSE ALL ORDER command <bSent</b> for user " . $account], $chat_id);
            }
        } catch (\Exception $ex) {
            return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "ERROR - " . $ex->getMessage()], $chat_id);
        }
    }

    private function maxop()
    {
        try {
            $message        = "Setting MAXOP dari bot dengan format\n<pre>/setmaxop &lt;nilai_max_op&gt;</pre>";
            $message_id     = $this->message_id;
            $from_username  = $this->from_username;
            $from_id        = $this->from_id;
            $chat_id        = $this->chat_id;
            $callbackQuery  = $this->callback_query;

            if ($callbackQuery) {
                $message_id = $callbackQuery['id'];
                $from_username = $callbackQuery['from']['username'] ?? " _username_ ";
                $from_id = $callbackQuery['from']['id'] ?? " _id_ ";
                $chat_id = $callbackQuery['message']['chat']['id'];

                $this->notifLog('maxop', 'maxop', $message_id, $chat_id, $from_id, $from_username);

                $user = $this->getUser($from_id, $from_username);
                if (!$user) {
                    TelegramHelper::editMessageText([
                        'chat_id' => $chat_id,
                        'message_id' => $message_id,
                        'text' => "User " . $from_username . "(" . $from_id . ")" . " <b>Belum Terdaftar</b>",
                        'reply_markup' => json_encode(['inline_keyboard' => [[]]])
                    ]);
                }

                TelegramHelper::editMessageText([
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => $message,
                    'reply_markup' => json_encode(['inline_keyboard' => [[]]])
                ]);
            } else {
                $this->notifLog('maxop', 'maxop', $message_id, $chat_id, $from_id, $from_username);

                $user = $this->getUser($from_id, $from_username);
                if (!$user) {
                    return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "User " . $from_username . "(" . $from_id . ")" . " <b>Belum Terdaftar</b>"], $chat_id);
                }

                return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => $message], $chat_id);
            }
        } catch (\Exception $ex) {
            return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "ERROR - " . $ex->getMessage()], $chat_id);
        }
    }

    private function setmaxop($params)
    {
        try {
            $callbackQuery  = $this->callback_query ?? null;

            $message_id = $this->message_id ?? "";
            $from_username = $this->from_username ?? "";
            $from_id = $this->from_id ?? "";
            $chat_id = $this->chat_id ?? "";

            if ($callbackQuery) {
                $message_id = $callbackQuery['id'];
                $from_username = $callbackQuery['from']['username'] ?? " _username_ ";
                $from_id = $callbackQuery['from']['id'] ?? " _id_ ";
                $chat_id = $callbackQuery['message']['chat']['id'];
            }

            $this->notifLog('setmaxop', 'setmaxop', $message_id, $chat_id, $from_id, $from_username);

            if (count($params) != 1) {
                return $this->reply("Format pesan tidak valid, silahkan ketik dengan format <pre>/maxop &lt;spasi&gt;&lt;jumlah&gt;</pre>");
            }

            $user = $this->getUser($from_id, $from_username);
            if (!$user) {
                return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "User " . $from_username . "(" . $from_id . ")" . " <b>Belum Terdaftar</b>"], $chat_id);
            }
            $maxop = trim($params[0]);

            $account = $user->user_account ?? null;

            if ($account) {

                $order = new CloseOrder();
                $order->order_account = $account;
                $order->order_cmd = "OP" . $maxop;
                $order->order_status = 0;
                $order->order_date =  (new DateTime())->format('Y-m-d H:i:s');

                if (!$order->save()) {
                    return ($order->errors)[0];
                }
                // return ['success' => true, 'message' => "SET MAX OP command sent"];
                return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "SET MAX OP (" . $maxop . ") command <bSent</b> @" . $from_username . "(" . $from_id . ")"], $chat_id);
            } else {
                return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "FAILED to set MAX OP (" . $maxop . ") command <bSent</b>  @" . $from_username . "(" . $from_id . ")"], $chat_id);
            }
        } catch (\Exception $ex) {
            return TelegramHelper::sendMessage(['reply_to_message_id' => $message_id, 'text' => "ERROR - " . $ex->getMessage()], $chat_id);
        }
    }

    private function menu()
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
                    ['text' => 'Check', 'callback_data' => "check"],
                ],
                [
                    ['text' => 'Outlook', 'callback_data' => "outlook"],
                    ['text' => 'MaxOP', 'callback_data' => "maxop"],
                ],
                [
                    ['text' => 'Close BUY', 'callback_data' => "close_buy"],
                    ['text' => 'Close SELL', 'callback_data' => "close_sell"],
                ],
                [
                    ['text' => 'Close All Order', 'callback_data' => "close_all"],
                ],
                [
                    ['text' => 'Pendaftaran Lisensi', 'callback_data' => "license"],
                ]
            ],
            // 'resize_keyboard' => true,
            // 'one_time_keyboard' => true,
            'selective' => true,
        ]);

        return TelegramHelper::sendMessage(
            [
                'reply_to_message_id' => $this->message_id,
                'text' => 'halo ' . $this->from_name . ', Silahkan pilih menu berikut',
                'reply_markup' => $encodedKeyboard
            ],
            $this->chat_id
        );

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

    private function getUser($from_id, $from_name = '')
    {
        $user = Users::find()
            ->where(['telegram_id' => $from_id])
            ->orWhere(['telegram_username' => $from_name])
            ->one();
        return $user;
    }

    private function start()
    {
        $message = "halo @" . $this->from_name . "\nSelamat datang di layanan iTrust Trading Bot\nSilahkan ketik lisensi dengan format <pre>/link &lt;nomor lisensi&gt;</pre>\nUntuk menampilkan menu, silahkan ketik /menu";

        $user = $this->getUser($this->from_id, $this->from_name);

        if ($user) {
            $message = "halo @" . $this->from_username . " (" . $user->user_nama . "|" . $user->user_license . ") " . "\nSelamat datang di layanan iTrust Trading Bot" . "\nUntuk menampilkan menu, silahkan ketik /menu";
        }

        // if (!$user) {
        //     $user = new Users;
        // }

        // $user->user_nama = $this->from_name;
        // $user->telegram_id = $this->from_id;
        // $user->telegram_username = $this->from_username;

        // if (!$user->save()) {
        //     throw new Exception(current($user->errors)[0]);
        // }

        // disabled create new user from tele

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
            return $this->reply("Mohon maaf, Belum ada Channel, hubungi CS iTrust di admin@itrust-care.com");
        }
        $msg = "List Channel yang terdaftar pada iTrust.id : \n";
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
            return $this->reply("Mohon maaf, Belum ada Layanan, hubungi CS iTrust di admin@itrust-care.com");
        }
        $msg = "List layanan yang terdaftar pada iTrust.id : \n";
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


    private function lisensi($params)
    {

        if (count($params) != 1) {
            return $this->reply("Format pesan tidak valid, silahkan ketik dengan format <pre>/lisensi &lt;spasi&gt;&lt;nama kanal&gt;</pre>");
        }

        $user = Users::findOne(['telegram_id' => $this->from_id]);

        if (!$user) {
            $user = new Users;
            $user->name = $this->from_name;
            $user->telegram_id = $this->from_id;
            $user->telegram_username = $this->from_username;

            if (!$user->save()) {
                throw new Exception(current($user->errors)[0]);
            }
        }

        // $channel = Channel::findOne(['code' => trim($params[0])]);

        // if (!$channel) {
        //     return $this->reply("Kanal " . $params[0] . " tidak ditemukan");
        // }

        // $user->channel = $channel->id;
        // if (!$user->save()) {
        //     throw new Exception(current($user->errors)[0]);
        // }

        // $message = "User Anda telah terhubung dengan lisensi <strong>" . $channel->name . "</strong>,\nBerikut layanan kami yang bisa anda pilih :";

        // $services = Service::find()->where(['channel_id' => $channel->id])->all();
        // $i = 1;
        // foreach ($services as $service) {
        //     $message .= "<pre>" . $i . ". " . $service->name . " &lt;" . $service->code . "&gt;</pre>";
        //     $i++;
        // }

        // $message .= "Silahkan pilih layanan kami dengan cara ketik :  <pre>/pilih " . current($services)->code . "</pre>";

        // $this->reply($message);

        // if (!empty($channel->greeting)) {
        //     $this->reply($channel->greeting);
        // }
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
