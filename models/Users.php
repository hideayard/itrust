<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $user_id
 * @property string $user_name
 * @property string|null $user_nama
 * @property string $user_pass
 * @property string|null $user_hp
 * @property string|null $user_email
 * @property string $user_tipe
 * @property string $user_foto
 * @property int $user_status
 * @property string|null $created_at
 * @property int|null $created_by
 * @property string|null $modified_at
 * @property int|null $modified_by
 * @property int|null $is_deleted
 */
class Users extends \yii\db\ActiveRecord implements
    \yii\web\IdentityInterface
{

    public $imageFile;

    const SCENARIO_DEFAULT= 'default';
    const SCENARIO_LOGIN = 'login';
    const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['user_id', 'user_name', 'user_nama', 'user_hp', 'user_pass', 'user_email', 'user_hp', 'user_foto', 'user_status', 'is_deleted', 'created_at', 'created_by', 'modified_at', 'modified_by'],
            self::SCENARIO_LOGIN => ['user_name', 'user_pass'],
            self::SCENARIO_UPDATE => ['user_id', 'user_name', 'user_nama', 'user_hp', 'user_email', 'user_hp', 'user_foto', 'user_tipe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_name', 'user_pass', 'user_foto', 'user_status'], 'required'],
            [['user_pass', 'user_foto'], 'string'],
            [['user_status', 'created_by', 'modified_by', 'is_deleted'], 'integer'],
            [['created_at', 'modified_at','user_license_expired'], 'safe'],
            [['user_name', 'user_nama', 'user_email','user_account','user_license'], 'string', 'max' => 100],
            [['user_hp'], 'string', 'max' => 20],
            [['user_tipe','user_token'], 'string', 'max' => 15],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => ['png', 'jpg'], 'maxFiles' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
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
            'user_account' => 'Account',
            'user_license' => 'License',
            'user_license_expired' => 'License Expired',
        ];
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['user_name' => $username]);
    }

    public function beforeSave($insert)
    {

        if ($this->isNewRecord) {
            $this->created_at = date("Y-m-d H:i:s");
            $this->created_by = !Yii::$app->user->isGuest ? Yii::$app->user->identity->user_id : null;
        } else {
            $this->modified_at = date("Y-m-d H:i:s");
            $this->modified_by = !Yii::$app->user->isGuest ? Yii::$app->user->identity->user_id : null;
        }

        return parent::beforeSave($insert);
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
    }

    public function getId()
    {
        return $this->user_id;
    }

    public function getAuthKey()
    {
    }

    public function validateAuthKey($authKey)
    {
    }
}
