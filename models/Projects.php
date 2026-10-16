<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "projects".
 *
 * @property int $project_id
 * @property string $project_code
 * @property string $project_name
 * @property string|null $project_description
 * @property int $project_status
 * @property string|null $created_at
 * @property int|null $created_by
 * @property string|null $modified_at
 * @property int|null $modified_by
 * @property int|null $is_deleted
 */
class Projects extends \yii\db\ActiveRecord
{
    const SCENARIO_DEFAULT = 'default';
    const SCENARIO_UPDATE = 'update';

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['project_id', 'project_code', 'project_name', 'project_description', 'project_status', 'is_deleted', 'created_at', 'created_by', 'modified_at', 'modified_by'],
            self::SCENARIO_UPDATE => ['project_id', 'project_code', 'project_name', 'project_description', 'project_status'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'projects';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['project_code', 'project_name'], 'required'],
            [['project_description'], 'string'],
            [['project_status', 'created_by', 'modified_by', 'is_deleted'], 'integer'],
            [['created_at', 'modified_at'], 'safe'],
            [['project_name'], 'string', 'max' => 100],
            [['project_code'], 'string', 'max' => 50],
            [['project_code'], 'match', 'pattern' => '/^[a-z0-9_]+$/', 'message' => 'Code may only contain lowercase letters, numbers and underscores.'],
            [['project_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'project_id' => 'Project ID',
            'project_code' => 'Code',
            'project_name' => 'Name',
            'project_description' => 'Description',
            'project_status' => 'Status',
            'created_at' => 'Created at',
            'created_by' => 'Created by',
            'modified_at' => 'Modified at',
            'modified_by' => 'Modified by',
            'is_deleted' => 'Is Deleted',
        ];
    }

    /**
     * Finds an active project by its code.
     *
     * @param string $code
     * @return static|null
     */
    public static function findByCode($code)
    {
        return static::findOne(['project_code' => $code, 'is_deleted' => 0]);
    }

    /**
     * The suffix appended to project-scoped table names, e.g. "gmr" for users_gmr.
     *
     * @return string
     */
    public function getTableSuffix()
    {
        return $this->project_code;
    }

    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->created_at = date("Y-m-d H:i:s");
            $this->created_by = !Yii::$app->user->isGuest ? Yii::$app->user->identity->user_id : null;
            if ($this->project_status === null) {
                $this->project_status = self::STATUS_ACTIVE;
            }
        } else {
            $this->modified_at = date("Y-m-d H:i:s");
            $this->modified_by = !Yii::$app->user->isGuest ? Yii::$app->user->identity->user_id : null;
        }

        return parent::beforeSave($insert);
    }
}
