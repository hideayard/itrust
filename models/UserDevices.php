<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "user_devices".
 *
 * @property int $id
 * @property int $user_id
 * @property string $device_id
 * @property string|null $device_alias
 * @property string|null $device_name
 * @property string|null $device_description
 * @property string|null $device_remark
 * @property int $is_active
 * @property string $created_at
 * @property string|null $updated_at
 * @property int|null $created_by
 * @property int|null $modified_by
 */
class UserDevices extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_devices';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'device_id'], 'required'],
            [['user_id', 'is_active', 'created_by', 'modified_by'], 'integer'],
            [['device_description','device_remark'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['device_id'], 'string', 'max' => 100],
            [['device_name','device_alias'], 'string', 'max' => 255],
            [['user_id', 'device_id'], 'unique', 'targetAttribute' => ['user_id', 'device_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'device_id' => 'Device ID',
            'device_name' => 'Device Name',
            'device_alias' => 'Device Alias',
            'device_description' => 'Device Description',
            'device_remark' => 'Device Remark',
            'is_active' => 'Is Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'modified_by' => 'Modified By',
        ];
    }

    /**
     * Get the user associated with this device
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['user_id' => 'user_id']);
    }

    /**
     * Get telemetry data for this device
     */
    public function getTelemetryData()
    {
        return $this->hasMany(TelemetryData::className(), ['device_id' => 'device_id']);
    }
}