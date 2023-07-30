<?php

namespace app\controllers;

use Yii;
use app\models\Enroll;
use app\models\EnrollSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\Courses;
use app\models\CourseSession;
use app\models\Users;
use yii\helpers\ArrayHelper;

/**
 * EnrollController implements the CRUD actions for Enroll model.
 */
class EnrollController extends Controller
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
     * Lists all Enroll models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EnrollSearch;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        $course = Courses::find()->all();
        $course = ArrayHelper::map($course, 'course_id', 'course_title');
        $course = ['' => ''] + $course;

        $users = Users::find()->where(['is_deleted' => 0])->andWhere(['user_tipe' => 'USER'])->distinct()->all();
        $users = ArrayHelper::map($users, 'user_id', 'user_nama');
        $users = ['' => ''] + $users;

        $course_session = CourseSession::find()->all(); //->select('cs_teacher')->distinct()->all();
        $course_session = ArrayHelper::map($course_session, 'cs_id', 'cs_teacher');
        $course_session = array_merge(['' => ''], $course_session);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'courses' => $course,
            'users' => $users,
            'course_session' => $course_session,
        ]);
    }

    /**
     * Displays a single Enroll model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->enroll_id]);
        } else {
            return $this->render('view', ['model' => $model]);
        }
    }

    /**
     * Creates a new Enroll model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Enroll;

        $users = ArrayHelper::map(Users::find()->orderBy(['user_nama' => SORT_ASC])->all(), 'user_id', 'user_nama');

        $courses = ArrayHelper::map(Courses::find()->orderBy(['course_title' => SORT_ASC])->all(), 'course_id', 'course_title');

        $cs = ArrayHelper::map(CourseSession::find()->orderBy(['cs_teacher' => SORT_ASC])->all(), 'cs_id', 'cs_teacher');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->enroll_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'users' => $users,
                'courses' => $courses,
                'cs' => $cs,
            ]);
        }
    }

    /**
     * Updates an existing Enroll model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $users = ArrayHelper::map(Users::find()->orderBy(['user_nama' => SORT_ASC])->all(), 'user_id', 'user_nama');

        $courses = ArrayHelper::map(Courses::find()->orderBy(['course_title' => SORT_ASC])->all(), 'course_id', 'course_title');

        $cs = ArrayHelper::map(CourseSession::find()->orderBy(['cs_teacher' => SORT_ASC])->all(), 'cs_id', 'cs_teacher');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->enroll_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'users' => $users,
                'courses' => $courses,
                'cs' => $cs,
            ]);
        }
    }

    /**
     * Deletes an existing Enroll model.
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
     * Finds the Enroll model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Enroll the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Enroll::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
