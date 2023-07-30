<?php

namespace app\controllers;

use Yii;
use app\models\Courses;
use app\models\CourseSection;
use app\models\CoursesSearch;
use app\models\Users;
use Exception;
use PDO;
use Throwable;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * CoursesController implements the CRUD actions for Courses model.
 */
class CoursesController extends Controller
{

    public $enableCsrfValidation = false;
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
                    //'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Courses models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CoursesSearch;
        $dataProvider = $searchModel->searchForTrainer(Yii::$app->request->getQueryParams(), Yii::$app->user->identity->user_id);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single Courses model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $sections = [];

        foreach ($model->courseSections as $k => $v) {
            if (!isset($sections[$v['section_title']])) {
                $sections[$v['section_title']] = [];
            }

            $sections[$v['section_title']][] = $v;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->course_id]);
        } else {
            return $this->render('view', [
                'model' => $model,
                'sections' => $sections,
            ]);
        }
    }

    /**
     * Creates a new Courses model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Courses;
        $users = ArrayHelper::map(Users::find()->orderBy(['user_nama' => SORT_ASC])->all(), 'user_id', 'user_nama');

        if ($model->load(Yii::$app->request->post())) {
            $gambar = UploadedFile::getInstance($model, 'course_photo');
            $model->course_created_by = Yii::$app->user->identity->user_id;
            if ($model->validate()) {
                $model->save();
                if (!empty($gambar)) {

                    if (!file_exists("uploads")) {
                        mkdir("uploads", 777, true);
                    }
                    $microtime = (microtime(true)*10000);
                    $gambar->saveAs(Yii::getAlias('@webroot/uploads/') . $gambar->baseName .$microtime . '.' . $gambar->extension);
                    $model->course_photo = '/uploads/' . $gambar->baseName .$microtime . '.' . $gambar->extension;
                    $model->save(FALSE);
                }
            }
            if (!$model->save()) {
                die(var_dump($model->errors));
            }
            return $this->redirect(['sections', 'id' => $model->course_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'users' => $users,
            ]);
        }
    }

    public function actionCreateFromTemplate()
    {

        $model = new Courses;
        $users = ArrayHelper::map(Users::find()->orderBy(['user_nama' => SORT_ASC])->all(), 'user_id', 'user_nama');
        $courses = ArrayHelper::map(Courses::find()->orderBy(['course_title' => SORT_ASC])->all(), 'course_id', 'course_title');

        if (Yii::$app->request->isPost) {

            $template = Courses::find()->where(Yii::$app->request->post('template'))->asArray()->one();

            if (!$template) {
                throw new Exception("Invalid template");
            }

            $sections = CourseSection::find()->where(['course_id' => $template])->asArray()->all();


            $model->setAttributes($template);
            $model->course_trainer = Yii::$app->request->post('trainer');
            if (!$model->save()) {
                throw new Exception("Failed to save course");
            }

            foreach ($sections as $section) {
                $newSection = new CourseSection;
                $newSection->setAttributes($section);
                $newSection->course_id = $model->course_id;
                if (!$newSection->save()) {
                    throw new Exception("Failed to save template");
                }
            }

            return $this->redirect(['sections', 'id' => $model->course_id]);
        } else {
            return $this->render('create_from_template', [
                'model' => $model,
                'users' => $users,
                'courses' => $courses,
            ]);
        }
    }

    /**
     * Updates an existing Courses model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {

        if (Yii::$app->user->identity->user_tipe != 'ADMIN') {
            throw new HttpException(403, "You are not authorized");
        }

        $model = $this->findModel($id);
        $users = ArrayHelper::map(Users::find()->orderBy(['user_nama' => SORT_ASC])->all(), 'user_id', 'user_nama');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['sections', 'id' => $model->course_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'users' => $users,
            ]);
        }
    }

    public function actionSections($id)
    {

        if (Yii::$app->user->identity->user_tipe != 'ADMIN' && Yii::$app->user->identity->user_tipe != 'TRAINER') {
            throw new HttpException(403, "You are not authorized");
        }

        $model = Courses::findOne($id);
        $sections = $model->getCourseSections()->orderBy(['section_order' => SORT_ASC, 'subsection_order' => SORT_ASC])->all();

        if (!$model) {
            throw new \yii\web\NotFoundHttpException("Page not found");
        }

        return $this->render('sections', [
            'model' => $model,
            'sections' => $sections,
        ]);
    }

    public function actionSaveSections()
    {

        Yii::$app->response->format = Response::FORMAT_JSON;

        $transaction = Yii::$app->db->beginTransaction();
        $response = [
            'status' => true,
            'status_code' => 200,
            'message' => 'Success',
            'result' => null
        ];

        try {

            $request = Yii::$app->request;
            $courseId = $request->post('course_id');
            $sections = $request->post('sections');

            $course = Courses::findOne($courseId);

            if (!$course) {
                throw new Exception("Course not found");
            }

            CourseSection::deleteAll('course_id = ' . $courseId);

            $subsectionOrder = 1;
            foreach ($sections as $section) {
                unset($section['id']);
                $courseSection = new CourseSection;
                $courseSection->setAttributes($section);
                $courseSection->subsection_order = $subsectionOrder;
                if (!$courseSection->save()) {
                    throw new Exception("Failed to save course section: " . current($courseSection->errors)[0]);
                }

                $subsectionOrder++;
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            $response['status'] = false;
            $response['status_code'] = 500;
            $response['message'] = $e->getMessage();
        } finally {
            return $response;
        }
    }

    /**
     * Deletes an existing Courses model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {

        if (Yii::$app->user->identity->user_tipe != 'ADMIN') {
            throw new HttpException(403, "You are not authorized");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {

            CourseSection::deleteAll("course_id = $id");
            $this->findModel($id)->delete();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            die($e->getMessage());
        }


        return $this->redirect(['index']);
    }

    /**
     * Finds the Courses model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Courses the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Courses::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
