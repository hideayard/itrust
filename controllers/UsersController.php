<?php

namespace app\controllers;

use app\models\Enroll;
use Yii;
use app\models\Users;
use app\models\UsersSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\HttpException;
use yii\web\UploadedFile;

/**
 * UsersController implements the CRUD actions for Users model.
 */
class UsersController extends Controller
{

    public $layout = 'dashboard';

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
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Users models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UsersSearch;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());
        $dataProvider->pagination->pageSize = 9;

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single Users model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $enrolls = Enroll::find()
            ->where(['enroll_userid' => Yii::$app->user->identity->user_id])
            ->Andwhere(['enroll_is_deleted' => 0])
            ->all();

        $temp = 0;
        foreach ($enrolls as $enroll){
            $temp += $enroll->enrollProgress;
        }

        $averageProgress = $temp / count($enrolls);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->user_id]);
        } else {
            return $this->render('view', ['model' => $model, 'enrolls' => $enrolls, 'averageProgress' => $averageProgress]);
        }
    }

    /**
     * Creates a new Users model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Users;

        if ($model->load(Yii::$app->request->post())) {
            $gambar = UploadedFile::getInstance($model, 'imageFile');

            if ($model->validate()) {
                $model->save();
                if (!empty($gambar)) {

                    if (!file_exists("uploads")) {
                        mkdir("uploads", 777, true);
                    }

                    $gambar->saveAs(Yii::getAlias('@webroot/uploads/') . $gambar->baseName . '.' . $gambar->extension);
                    $model->b_foto = 'uploads/' . $gambar->baseName . '.' . $gambar->extension;
                    $model->save(FALSE);
                }
            }

            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->user_id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Users model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
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
                return $this->redirect(['view', 'id' => $model->user_id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Users model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Users model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Users the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Users::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionEnroll($id){
        $user = Users::findOne($id);

        if (!$user){
            throw new HttpException(404, "User not found");
        }

        return $this->render('enroll', [
            'user' => $user,
        ]);
    }
}
