<?php

namespace app\controllers;

use Yii;
use DateTime;
use app\models\Node;
use app\models\Notif;
use app\models\Users;
use yii\web\Response;
use app\models\Enroll;
use yii\web\Controller;
use yii\web\UploadedFile;
use app\models\Prediction;
use yii\web\HttpException;
use app\models\DataSensors;
use yii\filters\VerbFilter;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\EnrollProgress;
use yii\filters\AccessControl;
use app\models\forms\LoginForm;
use app\helpers\DashboardHelper;
use app\models\forms\ContactForm;
use app\models\forms\RegisterForm;

class DashboardController extends Controller
{

    public $layout = 'dashboard';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $model = Users::findOne(Yii::$app->user->identity->user_id);    
        return $this->render('index', [
            'model' => $model
        ]);
    }

    public function actionDataPredict()
    {
        $request = Yii::$app->request;
        $device = $request->post('device') ?  $request->post('device') : 'RO1';
        $query = Prediction::find(['remark' => $device])->orderBy(['id' => SORT_DESC])->one();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $query;
    }


    public function actionDataPressure()
    {

        $request = Yii::$app->request;
        // $start = $request->post('start') ? (new DateTime($request->post('start')))->format('Y-m-d') : date('Y-m-d', strtotime((new DateTime())->format('Y-m-d') . ' - 1 month'));
        // $end = $request->post('end') ? (new DateTime($request->post('end')))->format('Y-m-d') : (new DateTime())->format('Y-m-d');
        $dateInput = $request->post('start') ? (new DateTime($request->post('start')))->format('Y-m-d') : (new DateTime())->format('Y-m-d');
        $device = $request->post('device') ?  $request->post('device') : 'RO1';

        $pressurePerDay = null;
        $date = $date2 = $s1 = $s2 = $s3 = $s4 = $s5 = $s6 = $s7 = $s8 = $s9 = [];

        // $key = "pressurePerDay-$device-$dateInput";

        $query = DataSensors::find()
            ->where(['remark' => $device])
            // ->andWhere(['between', 'DATE(`modified_at`)', $start, $end])
            ->andWhere(['DATE(`modified_at`)' => $dateInput])
            ->orderBy(['modified_at' => SORT_ASC]);
        // ->limit(50);

        $pressurePerDay = $query->all();
        $count = $query->count() ?? 0;

        $raw =  $query->createCommand()->rawSql;

        if ($pressurePerDay && count($pressurePerDay) > 0) {
            foreach ($pressurePerDay as $a) {
                $date[] = $a['modified_at'];
                $date2[] = $a['created_at'];
                $s1[] = $a['s1'];
                $s2[] = $a['s2'];
                $s3[] = $a['s3'];
                $s4[] = $a['s4'];
                $s5[] = $a['s5'];
                $s6[] = $a['s6'];
                $s7[] = $a['s7'];
                $s8[] = $a['s8'];
                $s9[] = $a['s9'];
            }
        } else {
            $date[] = $dateInput;
            $date2[] = $dateInput;
            $s1[] = 0;
            $s2[] = 0;
            $s3[] = 0;
            $s4[] = 0;
            $s5[] = 0;
            $s6[] = 0;
            $s7[] = 0;
            $s8[] = 0;
            $s9[] = 0;
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return [
            'date' => $date,
            'created_at' => $date2,
            's1' => $s1,
            's2' => $s2,
            's3' => $s3,
            's4' => $s4,
            's5' => $s5,
            's6' => $s6,
            's7' => $s7,
            's8' => $s8,
            's9' => $s9,
            'start' => $dateInput,
            // 'end' => $end,
            'device' => $device,
            'request' => $request->post(),
            'raw' => $raw, 'count' => $count
        ];
    }

    public function actionCreateNotif()
    {
        $request = Yii::$app->request;

        if ($request->post()) {

            $notif = new Notif();
            $notif->notif_from = "SYSTEM";
            $notif->notif_to = null;
            $notif->notif_date =  (new DateTime())->format('Y-m-d H:i:s');
            $notif->notif_processed = "false";
            $notif->notif_title = $request->post('notif_title') ?? "";
            $notif->notif_text = $request->post('notif_text') ?? "";

            if (!$notif->save()) {
                return ($notif->errors)[0];
                // return ($notif->errors);
            }

            return true;
        } else {
            return false;
        }
    }

    public function actionUpdateProfile()
    {
        $model = Users::findOne(Yii::$app->user->identity->user_id);

        if (!$model) {
            throw new HttpException(404, "User not found");
        }

        $model->scenario = Users::SCENARIO_UPDATE;

        if ($model->load(Yii::$app->request->post())) {
            $gambar = UploadedFile::getInstance($model, 'imageFile');

            if ($model->validate()) {
                $model->save();
                if (!empty($gambar)) {

                    if (!file_exists("uploads")) {
                        mkdir("uploads", 777, true);
                    }

                    $gambar->saveAs(Yii::getAlias('@webroot/uploads/') . $gambar->baseName . '.' . $gambar->extension);
                    $model->user_foto = 'uploads/' . $gambar->baseName . '.' . $gambar->extension;
                    $model->save(FALSE);
                }
            }

            $forms = Yii::$app->request->post('Users', null);
            if (isset($forms['user_pass']) && !empty($forms['user_pass'])) {
                $model->user_pass = Yii::$app->getSecurity()->generatePasswordHash($model->user_pass);
            }

            if ($model->save()) {
                return $this->redirect(['index']);
            }
        }

        return $this->render('update_profile', [
            'model' => $model,
        ]);
    }
}
