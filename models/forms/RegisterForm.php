<?php

namespace app\models\forms;

use app\models\Users;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use app\helpers\CustomHelper;

/**
 * RegisterForm is the model behind the login form.
 *
 * @property-read User|null $user This property is read-only.
 *
 */
class RegisterForm extends Model
{
    public $user_nama;
    public $user_name;
    public $user_pass;
    public $user_pass2;
    public $user_foto = '-';
    public $user_status = 0;
    public $user_hp;
    public $user_email;
    public $user_tipe = 'USER';
    public $created_at, $created_by, $modified_at, $modified_by, $is_deleted;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['user_name', 'user_pass', 'user_pass2', 'user_nama', 'user_hp', 'user_email'], 'required'],
            [['user_pass', 'user_foto'], 'string'],
            [['user_status', 'created_by', 'modified_by', 'is_deleted'], 'integer'],
            [['created_at', 'modified_at'], 'safe'],
            [['user_name', 'user_nama', 'user_email'], 'string', 'max' => 100],
            [['user_hp'], 'string', 'max' => 20],
            [['user_tipe'], 'string', 'max' => 15],
            ['is_deleted', 'default', 'value' => 0],
            [['user_pass', 'user_pass2'], 'string', 'min' => 8],

            ['user_name', 'validateUsername'],
            ['user_email', 'validateEmail'],
            ['user_pass', 'validatePassword'],
            ['user_pass2', 'compare', 'compareAttribute' => 'user_pass', 'message' => "Passwords don't match"],
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {

            if (!preg_match('/^(?=.*[A-Z]).{8,}$/', $this->$attribute)) {
                $this->addError($attribute, 'Password must contain at least one uppercase letter');
            } elseif (!preg_match('/^(?=.*[0-9])(?=.*[A-Z]).{8,20}$/', $this->$attribute)) {
                $this->addError($attribute, 'Password must contain at least one special character');
            }
        }
    }

    public function validateUsername($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (Users::find()->where(['user_name' => $this->user_name])->count() > 0) {
                $this->addError($attribute, 'Username is already taken.');
            }
        }
    }

    public function validateEmail($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (Users::find()->where(['user_email' => $this->user_email])->count() > 0) {
                $this->addError($attribute, 'Email is already registered.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function register()
    {
        if ($this->validate()) {
            $model = new Users;
            $model->setAttributes($this->attributes);
            $model->user_pass = Yii::$app->getSecurity()->generatePasswordHash($model->user_pass);
            $time_start = round(microtime(true) * 1000);
            $user_name = $model->user_name;
            $user_email = $model->user_email;
            $token = CustomHelper::hash($time_start, 6);
            $model->user_token = $token;
            if ($model->save()){
                Yii::$app->mailer->compose()
                ->setFrom('admin@rochat.id')
                ->setTo($user_email)
                ->setSubject('Email Confirmation for rochat.id')
                ->setTextBody('confirm email address')
                ->setHtmlBody('<p>Hi '.$user_name.',<br>Please '.\yii\helpers\Html::a('Click Here',
                Yii::$app->urlManager->createAbsoluteUrl(['site/confirm','email'=>$user_email,'token'=>$token]) ).' to confirm your email address.</p>' )
                ->send();
            }
            else{
                throw new Exception(current($model->errors)[0]);
            }

            return true;
        }
        return false;
    }

    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'user_name' => 'Username',
            'user_nama' => 'Name',
            'user_pass' => 'Password',
            'user_hp' => 'Mobile Number',
            'user_email' => 'Email',
            'user_tipe' => 'Type',
            'user_foto' => 'Photo',
            'user_status' => 'Status',
            'created_at' => 'Created at',
            'created_by' => 'Created by',
            'modified_at' => 'Modified at',
            'modified_by' => 'Modified by',
            'is_deleted' => 'Is Deleted',
            'user_token' => 'Token',
            'user_pass2' => 'Retype Password',
        ];
    }
}
