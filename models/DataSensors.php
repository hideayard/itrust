<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_sensors".
 *
 * @property int $id
 * @property string $s1
 * @property string $s2
 * @property string $s3
 * @property string $s4
 * @property string $s5
 * @property string $s6
 * @property string $s7
 * @property string $s8
 * @property string $s9
 * @property string|null $ip
 * @property string $created_at
 * @property int|null $created_by
 * @property string $modified_at
 * @property int|null $modified_by
 * @property string|null $status
 * @property string|null $remark
 */
class DataSensors extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'data_sensors';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'modified_at'], 'safe'],
            [['created_by', 'modified_by'], 'integer'],
            [['s1', 's2', 's3', 's4', 's5', 's6', 's7', 's8', 's9'], 'string', 'max' => 10],
            [['ip'], 'string', 'max' => 16],
            [['status'], 'string', 'max' => 100],
            [['remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            's1' => 'S1',
            's2' => 'S2',
            's3' => 'S3',
            's4' => 'S4',
            's5' => 'S5',
            's6' => 'S6',
            's7' => 'S7',
            's8' => 'S8',
            's9' => 'S9',
            'ip' => 'Ip',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'modified_at' => 'Modified At',
            'modified_by' => 'Modified By',
            'status' => 'Status',
            'remark' => 'Remark',
        ];
    }
}
