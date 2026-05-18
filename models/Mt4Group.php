<?php

namespace app\models;

use app\models\Mt4GroupQuery;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * This is the model class for table "mt4_group".
 *
 * @property int $id
 * @property string $name
 * @property string|null $desc
 * @property string|null $mt4_ids
 * @property string|null $remark
 * @property int|null $created_by
 * @property string $created_at
 * @property int|null $modified_by
 * @property string|null $modified_at
 * @property int $status
 */
class Mt4Group extends ActiveRecord
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mt4_group';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'modified_at',
                'value' => new \yii\db\Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'modified_by',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['desc', 'mt4_ids', 'remark'], 'string'],
            [['created_by', 'modified_by', 'status'], 'integer'],
            [['created_at', 'modified_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'unique'],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['mt4_ids'], 'validateMt4Ids'],
        ];
    }

    /**
     * Validates mt4_ids as valid JSON array
     */
    public function validateMt4Ids($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            $decoded = Json::decode($this->$attribute, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError($attribute, 'MT4 IDs must be a valid JSON format.');
                return;
            }
            if (!is_array($decoded)) {
                $this->addError($attribute, 'MT4 IDs must be a valid array.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'desc' => Yii::t('app', 'Description'),
            'mt4_ids' => Yii::t('app', 'MT4 IDs'),
            'remark' => Yii::t('app', 'Remark'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'modified_by' => Yii::t('app', 'Modified By'),
            'modified_at' => Yii::t('app', 'Modified At'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[ModifiedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModifiedBy()
    {
        return $this->hasOne(User::class, ['id' => 'modified_by']);
    }

    /**
     * Get MT4 IDs as array
     *
     * @return array
     */
    public function getMt4IdsArray()
    {
        if (empty($this->mt4_ids)) {
            return [];
        }
        
        try {
            return Json::decode($this->mt4_ids, true) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set MT4 IDs from array
     *
     * @param array $ids
     */
    public function setMt4IdsArray($ids)
    {
        if (is_array($ids)) {
            $this->mt4_ids = Json::encode(array_values(array_unique($ids)));
        }
    }

    /**
     * Get status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $statuses = [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
        
        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Get status options for dropdown
     *
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_ACTIVE => Yii::t('app', 'Active'),
            self::STATUS_INACTIVE => Yii::t('app', 'Inactive'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return Mt4GroupQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new Mt4GroupQuery(get_called_class());
    }
}