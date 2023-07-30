<?php

namespace app\controllers;

use Yii;
use app\models\CourseSession;
use app\models\CourseSessionSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\Courses;
use yii\helpers\ArrayHelper;
use app\models\Users;

/**
 * CoursesessionController implements the CRUD actions for CourseSession model.
 */
class CoursesessionController extends Controller
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
     * Lists all CourseSession models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CourseSessionSearch;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        $course = Courses::find()->all();
        $course = ArrayHelper::map($course, 'course_id', 'course_title');
        $course = ['' => ''] + $course;

        $teacher = CourseSession::find()->select('cs_teacher')->where('cs_teacher IS NOT NULL')->andWhere(['<>', 'cs_teacher', ''])->distinct()->all();
        $teacher = ArrayHelper::map($teacher, 'cs_teacher', 'cs_teacher');
        $teacher = ['' => ''] + $teacher;

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'courses' => $course,
            'teacher' => $teacher,

        ]);
    }

    /**
     * Displays a single CourseSession model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->cs_id]);
        } else {
            return $this->render('view', ['model' => $model]);
        }
    }


    /**
     * Creates a new CourseSession model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new CourseSession;
        $users = ArrayHelper::map(Users::find()->orderBy(['user_nama' => SORT_ASC])->all(), 'user_id', 'user_nama');
        $course = ArrayHelper::map(Courses::find()->orderBy(['course_id' => SORT_ASC])->all(), 'course_id', 'course_title');
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->cs_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'users' => $users,
                'course' => $course,
            ]);
        }
    }

    /**
     * Updates an existing CourseSession model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->cs_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing CourseSession model.
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
     * Finds the CourseSession model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CourseSession the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CourseSession::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
