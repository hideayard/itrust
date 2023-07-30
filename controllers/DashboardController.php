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
        $progress = [];

        $nodeId = Node::findOne(['node_id'=>1]);
        $nodes = ArrayHelper::map(Node::find()->all(), 'node_name', 'node_name');

        $start = Yii::$app->request->post('start') ? (new DateTime(Yii::$app->request->post('start')))->format('Y-m-d') : date('Y-m-d', strtotime((new DateTime())->format('Y-m-d'))); //. ' - 1 month'));
        $end = Yii::$app->request->post('end') ? (new DateTime(Yii::$app->request->post('end')))->format('Y-m-d') : (new DateTime())->format('Y-m-d');
        $maintenance1 = DataSensors::findOne(['remark'=>$nodeId["node_name"]]);

        $dataTraining = $formatted = $formatted3 = $formatted2 = [];
        $Anomaly = array();
        $countAnomaly['s1'] = $countAnomaly['s2'] = $countAnomaly['s3'] = $countAnomaly['s4'] = $countAnomaly['s5'] = $countAnomaly['flow1'] = $countAnomaly['flow2'] = $countAnomaly['con1'] = $countAnomaly['con2'] = 0;
        
        $result_sensors = DataSensors::find()
                            ->where(['remark'=>$nodeId["node_name"]])
                            // ->andWhere(['between', 'DATE(`modified_at`)', $start, $end])
                            ->andWhere(['DATE(`modified_at`)' => $start])
                            ->orderBy(['modified_at' => SORT_ASC])
                            ->limit(100)
                            ->asArray()
                            ->all();
        $i=0;
        foreach($result_sensors as $key => $value)
        {
          $j=0;
          $formatted[$i]['S'.++$j] = floatval($value['s1']);
          $formatted[$i]['S'.++$j] = floatval($value['s2']);
          $formatted[$i]['S'.++$j] = floatval($value['s3']);
          $formatted[$i]['S'.++$j] = floatval($value['s4']);
          $formatted[$i]['S'.++$j] = floatval($value['s5']);
          $formatted[$i]['yaxis'] = (string)($value['created_at']);
        
          // 6 & 7 -> flow
          // 8 & 9 -> con
          $formatted3[$i]['S'.++$j] = floatval($value['s6']);
          $formatted3[$i]['S'.++$j] = floatval($value['s7']);
          $formatted3[$i]['xflow'] = (string)($value['created_at']);
        
          $formatted2[$i]['S'.++$j] = floatval($value['s8']);
          $formatted2[$i]['S'.++$j] = floatval($value['s9']);
          $formatted2[$i]['xcon'] = (string)($value['created_at']);
        
          $j=0;
          $dataTraining[$i] = array(floatval($value['s1']) , floatval($value['s2']) , floatval($value['s3']) , floatval($value['s4']) , floatval($value['s5']) );
        
          if((floatval($value['s1']) < 3) || (floatval($value['s1']) > 10))
          {
            $Anomaly['s1'] = false;  
            if(++$countAnomaly['s1'] > 5)
            {
                $Anomaly['s1'] = floatval($value['s1']);
            }
          }
          if((floatval($value['s2']) < 3) || (floatval($value['s2']) > 10))
          {
            $Anomaly['s2'] = false;  
            if(++$countAnomaly['s2'] > 5)
            {
                $Anomaly['s2'] = floatval($value['s2']);
            }
          }
          if((floatval($value['s3']) < 3) || (floatval($value['s3']) > 10))
          {
            $Anomaly['s3'] = false;  
            if(++$countAnomaly['s3'] > 5)
            {
                $Anomaly['s3'] = floatval($value['s3']);
            }
          }
          if((floatval($value['s4']) < 3) || (floatval($value['s4']) > 10))
          {
            $Anomaly['s4'] = false;  
            if(++$countAnomaly['s4'] > 5)
            {
                $Anomaly['s4'] = floatval($value['s4']);
            }
          }
          if((floatval($value['s5']) < 3) || (floatval($value['s5']) > 10))
          {
            $Anomaly['s5'] = false;  
            if(++$countAnomaly['s5'] > 5)
            {
                $Anomaly['s5'] = floatval($value['s5']);
            }
          }
        
          if((floatval($value['s6']) < 0) || (floatval($value['s6']) > 8))
          {
            $Anomaly['flow1'] = false;  
            if(++$countAnomaly['flow1'] > 5)
            {
                $Anomaly['flow1'] = floatval($value['s6']);
            }
          }
        
          if((floatval($value['s7']) < 0) || (floatval($value['s7']) > 8))
          {
            $Anomaly['flow2'] = false;  
            if(++$countAnomaly['flow2'] > 5)
            {
                $Anomaly['flow2'] = floatval($value['s7']);
            }
          }
        
          if((floatval($value['s8']) < 0.7 ) || (floatval($value['s8']) > 0.9 ))
          {
            $Anomaly['con1'] = false;  
            if(++$countAnomaly['con1'] > 5)
            {
                $Anomaly['con1'] = floatval($value['s8']);
            }
          }
        
          if((floatval($value['s9']) < 0.7 ) || (floatval($value['s9']) > 0.9 ))
          {
            $Anomaly['con2'] = false;  
            if(++$countAnomaly['con2'] > 5)
            {
                $Anomaly['con2'] = floatval($value['s9']);
            }
          }
          $i++;
        }

        foreach ($Anomaly as $key => $value)
            if ($value == FALSE)
                unset($Anomaly[$key]);


        
$dataTraining = $lastData = $lastTrainingData = [];
$i=0;
foreach($result_sensors as $key => $value)
{
    
    $dataTraining[$i] = array(floatval($value['s1']) 
                        , floatval($value['s2']) 
                        , floatval($value['s3']) 
                        , floatval($value['s4']) 
                        , floatval($value['s5']) 
                        , floatval($value['s6']) 
                        , floatval($value['s7']) 
                        , floatval($value['s8']) 
                        , floatval($value['s9']) );
    if($i==0)
    {
        $lastData = $dataTraining[$i];
    }
    $i++;
}

//DATE(created_at)
// $results_pressure2 = $db2->arraybuilder()->paginate("data_sensors", $page);
// $results_pressure2 = $db2->getOne("data_sensors",["id","s1","s2","s3","s4","s5","s6","s7","s8","s9","created_at","remark","status"]);
$results_pressure2 = $result_sensors[0]??[];
$i=0;
if($results_pressure2 && count($results_pressure2)>0) {
    $lastTrainingData = array(floatval($results_pressure2['s1']) 
    , floatval($results_pressure2['s2']) 
    , floatval($results_pressure2['s3']) 
    , floatval($results_pressure2['s4']) 
    , floatval($results_pressure2['s5']) 
    , floatval($results_pressure2['s6']) 
    , floatval($results_pressure2['s7']) 
    , floatval($results_pressure2['s8']) 
    , floatval($results_pressure2['s9']) );

}
else {
    $lastTrainingData = array(0,0,0,0,0,0,0,0,0 );

}


$dataML = json_encode(array($dataTraining,$lastData,($results_pressure2["id"]??0),$lastTrainingData));
        // var_dump($nodes);die;
        return $this->render('index', [
            'progress' => $progress,
            'nodeId' => $nodeId,
            'nodes' => $nodes,
            'start' => $start,
            'end' => $end,
            'dataML' => $dataML,
            'Anomaly' => $Anomaly,
            // 'formatted' => $formatted,
            // 'formatted2' => $formatted2,
            // 'formatted3' => $formatted3,
            'maintenance1' => $maintenance1["modified_at"],
        ]);
    }

    public function actionDataPredict()
    {
        $request = Yii::$app->request;
        $device = $request->post('device') ?  $request->post('device') : 'RO1';
        $query = Prediction::find(['remark'=>$device])->orderBy(['id' => SORT_DESC])->one();
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
        ->where(['remark'=>$device])
        // ->andWhere(['between', 'DATE(`modified_at`)', $start, $end])
        ->andWhere(['DATE(`modified_at`)' => $dateInput])
        ->orderBy(['modified_at' => SORT_ASC]);
        // ->limit(50);

        $pressurePerDay = $query->all();
        $count = $query->count()??0;

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
            'raw' => $raw
            ,'count' => $count 
        ];
    }

    public function actionCreateNotif()
    {
        $request = Yii::$app->request;

        if ( $request->post() ) {
            
                $notif = new Notif();
                $notif->notif_from = "SYSTEM";
                $notif->notif_to = null;
                $notif->notif_date =  (new DateTime())->format('Y-m-d H:i:s');
                $notif->notif_processed = "false";
                $notif->notif_title = $request->post('notif_title')??"";
                $notif->notif_text = $request->post('notif_text')??"";

                if(!$notif->save()) {
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
