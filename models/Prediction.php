<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "prediction".
 *
 * @property int $id
 * @property int $data_id
 * @property float|null $s1
 * @property float|null $s2
 * @property float|null $s3
 * @property float|null $s4
 * @property float|null $s5
 * @property float|null $s6
 * @property float|null $s7
 * @property float|null $s8
 * @property float|null $s9
 * @property string $created_at
 * @property int|null $created_by
 * @property string $modified_at
 * @property int|null $modified_by
 * @property string|null $status
 * @property string|null $remark
 */
class Prediction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prediction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['data_id'], 'required'],
            [['data_id', 'created_by', 'modified_by'], 'integer'],
            [['s1', 's2', 's3', 's4', 's5', 's6', 's7', 's8', 's9'], 'number'],
            [['created_at', 'modified_at'], 'safe'],
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
            'data_id' => 'Data ID',
            's1' => 'S1',
            's2' => 'S2',
            's3' => 'S3',
            's4' => 'S4',
            's5' => 'S5',
            's6' => 'S6',
            's7' => 'S7',
            's8' => 'S8',
            's9' => 'S9',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'modified_at' => 'Modified At',
            'modified_by' => 'Modified By',
            'status' => 'Status',
            'remark' => 'Remark',
        ];
    }
}
