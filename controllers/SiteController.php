<?php

namespace app\controllers;

use PDO;
use Yii;
use Exception;
use Throwable;
use app\models\Exam;
use app\models\Soal;
use app\models\Notif;
use app\models\Users;
use yii\helpers\Html;
use yii\web\Response;
use app\models\Banner;
use app\models\DoExam;
use app\models\Enroll;
use app\models\Courses;
use yii\web\Controller;
use yii\data\Pagination;
use app\models\Discussion;
use yii\helpers\VarDumper;
use yii\web\HttpException;
use app\models\DataSensors;
use yii\filters\VerbFilter;
use yii\widgets\ActiveForm;
use app\helpers\CustomHelper;
use app\models\CourseSection;
use app\models\EnrollProgress;
use yii\filters\AccessControl;
use app\helpers\TelegramHelper;
use app\models\forms\LoginForm;
use app\models\forms\ContactForm;
use Codeception\Lib\Notification;
use app\models\forms\RegisterForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                    'submit-section' => ['post'],
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
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
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

        $reviews = CustomHelper::getReviews()??[];

        // var_dump( $reviews );die;
        $banner_list = Banner::find()
            ->where(['b_status' => 1])
            ->limit(5)
            ->orderBy('b_id DESC')->all();

        return $this->render('index', ['banner_list' => $banner_list,'reviews' => $reviews]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['dashboard/index']);
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $clientIp = CustomHelper::get_client_ip()??'localhost';

            TelegramHelper::sendMessage([
                'text' => "User Login : ".$model->user_name ."\nFrom : ".$clientIp,
                'parse_mode' => 'html']
                ,  -820543545);
            return $this->redirect(['dashboard/index']);

        }


        $model->user_pass = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionCheckNotif()
    {
        echo "-";
        $notif = Notif::find()
        // ->where(['=','notif_processed',false])
        ->where(['!=','notif_text',''])
        ->andWhere(['!=','notif_processed',1])
        ->all();

        if($notif) {
            foreach($notif as $value) {

                $newNotif = Notif::find()->where(['notif_id'=>$value->notif_id])->one();
                $newNotif->notif_processed = "true";

                if($newNotif->save()) {
                    TelegramHelper::sendMessage([
                        'text' => "<strong>Notification :</strong>\nFrom : ".$value->notif_from ."\n".$value->notif_text,
                        'parse_mode' => 'html']
                        ,  -820543545);
                } else {
                    TelegramHelper::sendMessage([
                        'text' => "<strong>ERROR :</strong> \nactionCheckNotif : ".current($newNotif->errors)[0],
                        'parse_mode' => 'html']
                        ,  -820543545);
                }

                
            }
        }
    }

    public function actionGetDataSensors() {
        $result_sensors = DataSensors::find()
                            ->andWhere(['modified_by' => null])
                            ->orderBy(['id' => SORT_ASC])
                            ->limit(500)
                            ->asArray()
                            ->all();
        return json_encode($result_sensors);
    }

    public function actionGetDataSensors2() {
        $result_sensors = DataSensors::find()
                            ->andWhere(['modified_by' => null])
                            ->orderBy(['id' => SORT_ASC])
                            ->limit(500)
                            ->asArray()
                            ->all();
        return json_encode($result_sensors);
    }

    public function actionSync() {
        $request = Yii::$app->request;
        $ids = $request->get('ids') ? json_decode($request->get('ids')) : [];
        $synced = DataSensors::updateAll([ 'modified_by' => 999 ], ['in', 'id', $ids]);                     
        if(!$synced) {
            return false;
        }
        return true;
    }


    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionConfirm($email, $token)
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $response = 0;
            $checkemail = Users::find()->where(['user_email' => $email])->count();
            $userActive = Users::find()->where(['user_email' => $email, 'user_status' => 1])->count();

            if ($checkemail >= 1) {

                if ($userActive <= 0) {
                    $users = Users::find()->where(['user_email' => $email, 'user_token' => $token])->one();

                    if ($users) {

                        $users->user_token = null;
                        $users->user_status = 1;
                        if ($users->save()) {
                            $response = 1;
                            $transaction->commit();
                        }
                    }
                } else {
                    $response = 2; //2 = user is already active
                }
            } else {
                $response = 3; //2 = user is already active
            }
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw new \yii\web\NotFoundHttpException("Oops, There was an error <br>" . $e->getMessage());
        } finally {
            return $this->render('confirm', [
                'email' => $email,
                'status' => $response,
            ]);
        }
    }

    public function actionResend($token)
    {

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $email = CustomHelper::decrypt($token, 'R3$3nD');
            $response = 0;
            $checkemail = Users::find()->where(['user_email' => $email])->count();
            $userActive = Users::find()->where(['user_email' => $email, 'user_status' => 1])->count();

            if ($checkemail >= 1) {
                if ($userActive <= 0) {
                    $users = Users::find()->where(['user_email' => $email, 'user_status' => 0])->one();

                    if ($users) {

                        $time_start = round(microtime(true) * 1000);
                        $token = CustomHelper::hash($time_start, 6);
                        $user_name = $users->user_name;
                        $users->user_token = $token;
                        if ($users->save()) {
                            $response = 1;
                            Yii::$app->mailer->compose()
                                ->setFrom('admin@rochat.id')
                                ->setTo($email)
                                ->setSubject('Email Confirmation for rochat.id')
                                ->setTextBody('confirm email address')
                                ->setHtmlBody('<p>Hi ' . $user_name . ',<br>Please ' . \yii\helpers\Html::a(
                                    'Click Here',
                                    Yii::$app->urlManager->createAbsoluteUrl(['site/confirm', 'email' => $email, 'token' => $token])
                                ) . ' to confirm your email address.</p>')
                                ->send();
                            $transaction->commit();
                        }
                    }
                } else {
                    $response = 2; //2 = user is already active
                }
            } else {
                $response = 3; //3 = email not found
            }
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw new \yii\web\NotFoundHttpException("Oops, There was an error <br>" . $e->getMessage());
        } finally {
            return $this->render('resend', [
                'email' => $email,
                'status' => $response,
            ]);
        }
    }



    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionCourses($page = 1)
    {


        if ($page < 1) {
            $page = 1;
        }

        $q = Yii::$app->request->get('q', '');

        $query = Courses::find()->select("*")
            // ->innerJoinWith('users','courses.course_trainer = users.user_id')
            ->join(
                'INNER JOIN',
                'users',
                'users.user_id =courses.course_trainer'
            )
            ->where(['course_is_deleted' => 0])
            ->Andwhere(['course_status' => 1])
            ->andFilterWhere(['like', 'course_title', $q])
            ->orFilterWhere(['like', 'course_desc', $q]);

        $offset = ($page - 1) * 6;

        $courses = $query->orderBy(['course_id' => SORT_DESC, 'course_created_at' => SORT_DESC])->limit(6)->offset($offset)->asArray()->all();
        $count = $query->limit(6)->offset($page - 1)->count();

        $pagination = new Pagination(['pageSize' => 6, 'totalCount' => $count, 'page' => $page - 1]);


        return $this->render('courses', [
            'courses' => $courses,
            'pagination' => $pagination,
            'q' => $q,
        ]);
    }

    public function actionDetailcourse($id)
    {
        // $course_detail = Courses::findOne($id);
        $course_detail = Courses::find()->select("*")
            ->join('INNER JOIN', 'users', 'users.user_id = courses.course_trainer')
            ->join('LEFT JOIN', 'course_session', 'courses.course_id = course_session.cs_course_id')
            ->where(['course_is_deleted' => 0])
            ->Andwhere(['course_status' => 1])
            ->Andwhere(['=', 'course_id', $id])->asArray()->all();
        // var_dump($course_detail);die;
        if (!$course_detail) {
            throw new \yii\web\NotFoundHttpException("Page not found");
        }

        $enroll = $participant = $limit = $penuh = null;

        if (!Yii::$app->user->isGuest) {
            $enroll = Enroll::find()->where(['enroll_courseid' =>  $course_detail[0]["course_id"], 'enroll_userid' => Yii::$app->user->identity->user_id])->one();
            $participant = Enroll::find()->where(['enroll_courseid' =>  $course_detail[0]["course_id"]])->count();
            $limit = Courses::find()->where(['course_id' =>  $course_detail[0]["course_id"]])->one();
            $penuh = ($participant >= $limit->course_participant_limit);
        }

        if (!$enroll) {
            $enroll = new Enroll();
        }

        if ($enroll->load(Yii::$app->request->post()) && $enroll->validate()) {
            if ($penuh) {
                throw new \yii\web\NotFoundHttpException("Course is closed due to Participant limit has been reached");
            }
            $enroll->enroll_created_at = date("Y-m-d H:i:s");
            $enroll->enroll_modified_at = date("Y-m-d H:i:s");
            if (!$enroll->save()) {
                die(current($enroll->errors)[0]);
            }
            return $this->redirect(['site/enroll', 'id' => $enroll->enroll_id]);
        }

        return $this->render('detail_course', ['id' => $id, 'course_detail' => $course_detail, 'enroll' => $enroll, 'penuh' => $penuh]);
    }

    public function actionAdmissions()
    {
        return $this->render('admissions');
    }

    public function actionRegister()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new RegisterForm();

        // validate any AJAX requests fired off by the form
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post()) && $model->register()) {
            return $this->render('register', [
                'model' => $model,
                'success' => true
            ]);
        }

        $model->user_pass = '';
        return $this->render('register', [
            'model' => $model,
            'success' => false
        ]);
    }

    public function actionCourse()
    {
        return $this->render('course');
    }

    public function actionSubmitSection($id = 0, $id_section = 0)
    {
        $post_data = Yii::$app->request->post();
        $id = $post_data["id"];
        $id_section = $post_data["id_section"];

        $transaction = Yii::$app->db->beginTransaction();

        try {

            $enroll = Enroll::findOne($id);

            if (!$enroll) {
                throw new \yii\web\NotFoundHttpException("Data Enrollment not found");
            }

            $nextSectionId = 0;

            ///cek data section
            $data_section = CourseSection::find()
                ->where(['course_id' => $enroll->enroll_courseid])
                ->AndWhere(['id' => $id_section])
                ->AndWhere(['is_deleted' => 0])
                ->orderBy('section_order ASC')->one();

            if (!$data_section) {
                throw new \yii\web\NotFoundHttpException("Data Section not found");
            }
            ///cek enroll progress
            $enroll_progress = EnrollProgress::find()
                ->where(['ep_enroll_id' =>  $enroll->enroll_id, 'ep_section_id' => $id_section])
                ->orderBy('ep_id DESC')
                ->one();

            if (!$enroll_progress) //no progress means start from zero
            {
                throw new \yii\web\NotFoundHttpException("Progress Not Found");
            } else {
                $enroll_progress->ep_status = 1;
                $enroll_progress->ep_modified_by = $enroll->enroll_userid;
                $enroll_progress->ep_modified_at = date("Y-m-d H:i:s");
                $enroll_progress->save();

                if ($data_section->section_next != null && $data_section->section_next != 0) { //section next != null berarti belum sampai akhir

                    $nextSectionId = Yii::$app->db->createCommand("SELECT id FROM course_section WHERE course_id = :course_id AND subsection_order = :order")
                        ->bindValue(':course_id', $enroll->enroll_courseid, PDO::PARAM_INT)
                        ->bindValue(':order', $data_section->section_next, PDO::PARAM_INT)
                        ->queryScalar();

                    // insert new progress on first chapter
                    $enroll_progress_new = new EnrollProgress();
                    $enroll_progress_new->ep_enroll_id = $enroll->enroll_id;
                    if ($data_section->type == "quiz") {
                        $enroll_progress_new->ep_remark = "quiz";
                    } else {
                        $enroll_progress_new->ep_remark = "next progress";
                    }
                    $enroll_progress_new->ep_section_id = $nextSectionId;
                    $enroll_progress_new->ep_created_by = $enroll->enroll_userid;
                    $enroll_progress_new->ep_modified_by = $enroll->enroll_userid;
                    $enroll_progress_new->ep_created_at = date("Y-m-d H:i:s");
                    $enroll_progress_new->ep_modified_at = date("Y-m-d H:i:s");
                    if (!$enroll_progress_new->save()) {
                        die(current($enroll_progress_new->errors)[0]);
                    }
                } else {
                    $enroll->enroll_status = 1;
                    $enroll->enroll_modified_by = $enroll->enroll_userid;
                    $enroll->enroll_modified_at = date("Y-m-d H:i:s");
                    $enroll->save();
                }
            }

            $transaction->commit();

            // return to next url 
            return $this->redirect(['site/enroll', 'id' => $enroll->enroll_courseid, 'id_section' => $nextSectionId]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function actionEnroll($id, $id_section = 0)
    {
        $next = $prev = 0;
        $data_exam = $data_jawaban = array();
        $transaction = Yii::$app->db->beginTransaction();

        try {

            $debug[] = "start";

            $enroll = Enroll::find()
                ->where(['enroll_courseid' =>  $id])
                ->andWhere(['enroll_userid' =>  Yii::$app->user->identity->user_id])->one();

            if (!$enroll) {

                $enroll = new Enroll;
                $enroll->enroll_courseid = $id;
                $enroll->enroll_userid = Yii::$app->user->identity->user_id;
                $enroll->enroll_cs = 1;
                $enroll->enroll_created_at = date("Y-m-d H:i:s");
                $enroll->enroll_modified_at = date("Y-m-d H:i:s");
                $enroll->enroll_status = 0;
                $enroll->enroll_is_deleted = 0;

                if (!$enroll->save()) {
                    throw new HttpException(500, current($enroll->errors)[0]);
                }
            }

            //throw new Exception("enroll id: " . $enroll->enroll_id);
            $data_section = null;

            $enroll_progress_qry = EnrollProgress::find()
                ->where(['ep_enroll_id' =>  $enroll->enroll_id])
                ->orderBy('ep_id ASC');
            $enroll_progress_count = $enroll_progress_qry->count();
            $all_enroll_progress = $enroll_progress_qry->all();

            if ($id_section == 0) //jika user tidak meng inputkan id section, makan select baru
            {   //select berdasarkan progress


                //initial data section
                //karena id section 0, maka select id section terkecil
                $data_section = CourseSection::find()
                    ->where(['course_id' => $id])
                    ->AndWhere(['is_deleted' => 0])
                    ->orderBy('id ASC')->one();
                // $id_section = $data_section->id;

                if (!$data_section) {
                    throw new HttpException(500, "Course section data not found");
                }

                //throw new Exception("\$enroll_progress_count: " . $enroll_progress_count);

                if ($enroll_progress_count <= 0) {
                    // jika tidak ditemukan progress maka buat progress baru
                    $debug[] = "id_section==0";
                    // insert new progress on first chapter
                    $enroll_progress = new EnrollProgress();
                    $enroll_progress->ep_enroll_id = $enroll->enroll_id;
                    $enroll_progress->ep_remark = "new data";
                    $enroll_progress->ep_section_id = $data_section->id;
                    $enroll_progress->ep_created_by = Yii::$app->user->identity->user_id;
                    $enroll_progress->ep_modified_by = Yii::$app->user->identity->user_id;
                    $enroll_progress->ep_created_at = date("Y-m-d H:i:s");
                    $enroll_progress->ep_modified_at = date("Y-m-d H:i:s");
                    if (!$enroll_progress->save()) {
                        die(current($enroll_progress->errors)[0]);
                    }
                    $debug[] = "create new progress";
                } else {
                    // jika ditemukan progress maka select progress terakhir
                    $enroll_progress = EnrollProgress::find()
                        ->where(['ep_enroll_id' =>  $enroll->enroll_id])
                        ->orderBy('ep_id DESC')
                        ->one();

                    //update data section
                    $data_section = CourseSection::find()
                        ->where(['course_id' => $enroll->enroll_courseid])
                        ->AndWhere(['id' => $enroll_progress->ep_section_id])
                        ->AndWhere(['is_deleted' => 0])
                        ->orderBy('section_order ASC')->one();
                }
            } else {
                // else jika user meng-inputkan id section
                $enroll_progress = EnrollProgress::find()
                    ->where(['ep_enroll_id' =>  $enroll->enroll_id])
                    ->andWhere(['ep_section_id' => $id_section])
                    ->orderBy('ep_id DESC')
                    ->one();


                if (!$enroll_progress) {
                    // jika tidak ditemukan progress maka tampilkan error
                    throw new \yii\web\NotFoundHttpException("You must complete the previous section first");
                } else {
                    $data_section = CourseSection::find()
                        ->where(['course_id' => $enroll->enroll_courseid])
                        ->AndWhere(['id' => $id_section])
                        ->AndWhere(['is_deleted' => 0])
                        ->orderBy('section_order ASC')->one();
                    // $enroll_progress->ep_status;

                }
            }

            if (!$data_section) {
                throw new \yii\web\NotFoundHttpException("No Section yet");
            } else {
                if ($data_section->type == "quiz") {
                    $data_exam = Yii::$app->db->createCommand('SELECT exam.*,soal.* FROM exam 
                                    left join soal on soal.id_exam=exam.id
                                    WHERE exam.id=:id AND exam.status=:status;')
                        ->bindValue(':id', $data_section->content)
                        ->bindValue(':status', 1)
                        ->queryAll();
                    //cek jika status sudah 1 maka show all jawaban, jika iya maka disabled jika tidak maka enable
                    if ($enroll_progress->ep_status == 1) {
                        //select d
                        $data_jawaban = DoExam::findAll(['id_exam' => $data_section->content, 'id_user' => Yii::$app->user->identity->user_id]);
                    }
                }
            }

            $nextSection = CourseSection::find()->where([
                'course_id' => $id,
                'subsection_order' => $data_section->section_next
            ])->one();
            $next = $nextSection ? $nextSection->id : 0;

            $prevSection = CourseSection::find()->where([
                'course_id' => $id,
                'subsection_order' => $data_section->section_prev
            ])->one();
            $prev = $prevSection ? $prevSection->id : 0;

            $sections = [];

            foreach ($enroll->enrollCourse->courseSections as $section) {
                if ($section->is_deleted == 0) {
                    if (!isset($sections[$section->section_title])) {
                        $sections[$section->section_title] = [];
                    }

                    $sections[$section->section_title][] = $section;
                }
            }
            $transaction->commit();

            return $this->render('enroll', [
                'enroll' => $enroll,
                'sections' => $sections,
                'prev' => $prev,
                'next' => $next,
                'data_section' => $data_section,
                'data_exam' => $data_exam,
                'data_jawaban' => $data_jawaban,
                'enroll_progress' => $enroll_progress,
                'all_enroll_progress' => $all_enroll_progress
            ]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function actionGetDiscussions()
    {

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $data = Yii::$app->db->createCommand("SELECT d.id, d.title, d.message, d.date, u.user_id, u.user_nama AS name, u.user_foto
        FROM discussion d
        INNER JOIN users u ON d.user_id = u.user_id 
        INNER JOIN courses c ON d.course_id = c.course_id
        ORDER BY d.`date` DESC")
            ->queryAll();
        return $data;
    }

    public function actionGetMoreDiscussions()
    {

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $offset = Yii::$app->request->post('offset', null);

        if (!$offset) {
            return [];
        }

        $data = Yii::$app->db->createCommand("SELECT d.id, d.title, d.message, d.date, u.user_id, u.user_nama AS name, u.user_foto
        FROM discussion d
        INNER JOIN users u ON d.user_id = u.user_id 
        INNER JOIN courses c ON d.course_id = c.course_id
        ORDER BY d.`date` DESC
        limit :offset, 10")
            ->bindValue(':offset', $offset, PDO::PARAM_INT)
            ->queryAll();
        return $data;
    }

    public function actionCreateQuestion()
    {

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $req = Yii::$app->request;
        $response = [
            'status' => true,
            'message' => 'Success'
        ];

        $courseId = $req->post('course_id');
        $title = $req->post('title');
        $message = $req->post('message');

        $model = new Discussion;
        $model->course_id = $courseId;
        $model->user_id = Yii::$app->user->identity->user_id;
        $model->title = $title;
        $model->message = $message;
        $model->date = date("Y-m-d H:i:s");
        $model->is_deleted = 0;
        $model->created_at = date("Y-m-d H:i:s");
        $model->created_by = Yii::$app->user->identity->user_id;
        $model->modified_at = date("Y-m-d H:i:s");
        $model->modified_by = Yii::$app->user->identity->user_id;

        if (!$model->save()) {
            $response['status'] = false;
            $response['message'] = current($model->errors)[0];
        }

        return $response;
    }

    public function actionSubmitQuiz()
    {

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $req = Yii::$app->request;
        $debug = [];
        $status = true;
        $message = 'Success';

        $transaction = Yii::$app->db->beginTransaction();

        try {

            $courseId = $req->post('course_id');
            $id_exam = $req->post('id_exam');
            $id_enroll = $req->post('id_enroll');
            $id_section = $req->post('id_section');
            $jumlahsoal = $req->post('jumlahsoal');

            $debug['courseid'] = $courseId;
            $debug['jumlahsoal'] = $jumlahsoal;
            $debug['user_id'] = Yii::$app->user->identity->user_id;
            $debug['id_exam'] = $id_exam;
            $debug['id_enroll'] = $id_enroll;
            $debug['id_section'] = $id_section;

            $soal = $jawaban = $arrdata = $kunci_jawaban = [];
            //delete semua jawaban sebelumnya
            DoExam::deleteAll([
                'id_user' => Yii::$app->user->identity->user_id,
                'id_exam' => $id_exam
            ]);
            // select semua soal dalam satu id exam
            $data_soal = Soal::find()
                ->where(['id_exam' => $id_exam])
                ->AndWhere(['status' => 1])
                ->orderBy('id ASC')->all();

            // looping berdasarkan jumlah soal
            foreach ($req->post() as $key => $value) {
                if (str_contains($key, "soal") && $key != 'jumlahsoal') {
                    $soal[intval(str_replace("soal", '', $key)) - 1] = $value;
                    //generate null data
                    $jawaban[intval(str_replace("soal", '', $key)) - 1] = null;
                } else if (str_contains($key, "jawaban")) {
                    $jawaban[intval(str_replace("jawaban", '', $key)) - 1] = $value;
                }
            }
            $debug['soal'] = $soal;
            $debug['jawaban'] = $jawaban;
            $totalnilai = 0;
            //input jawaban k DB
            foreach ($data_soal as &$value_data_soal) {
                $nilai = 0;
                $debug["data_soal"][] = $value_data_soal;

                foreach ($soal as $key => &$value) {
                    $debug["k"][] = $key;
                    // // cek jika kode soal sama
                    if ($value_data_soal->id == $value) {
                        ///simpan data jawaban ke kunci jawaban
                        $kunci_jawaban[$key] = $value_data_soal->jawaban;
                        // //cek jika jawaban sama denga kunci
                        if ($value_data_soal->jawaban == $jawaban[$key]) {
                            $nilai = 1;
                            $totalnilai++;
                        }
                        $debug[] = "value_data_soal->id == value";
                        $debug[] = $kunci_jawaban[$key];
                    }
                }
                $debug["i"][] = "before";
                $arrdata[] = [Yii::$app->user->identity->user_id, intval($id_exam), intval($value), $jawaban[$key], $nilai];
                $debug["j"][] = "after";
            }
            $debug['arrdata'] = $arrdata;
            Yii::$app->db->createCommand()->batchInsert('do_exam', ['id_user', 'id_exam', 'id_soal', 'jawaban', 'nilai'],  $arrdata)->execute();
            $enroll_progress = EnrollProgress::find()
                ->where(['ep_enroll_id' =>  $id_enroll, 'ep_section_id' => $id_section])
                ->one();
            $debug["enroll_progress"] = $enroll_progress;
            $enroll_progress->ep_nilai = $totalnilai . '/' . $jumlahsoal;
            $debug["ep_nilai"] = $enroll_progress->ep_nilai;

            if (!$enroll_progress->save()) {
                die(current($enroll_progress->errors)[0]);
            }
            $message = 'Success! <br> Your Mark ' . $totalnilai . '/' . $jumlahsoal;
            $debug["message"] = $message;

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            // throw $e;
            $status = false;
            $message = "Something Wrong!!!";
            $debug["error"] = $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $status = false;
            $message = "Something Wrong!!!";
            $debug["error"] = $e;
        }
        // return [
        //     'status' => $status,
        //     'message' => $message,
        //     'debug' => $debug
        // ];
        return json_encode([
            'status' => $status,
            'message' => $message,
            // 'debug' => $debug
        ]);
    }
}
