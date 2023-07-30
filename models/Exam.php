<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "exam".
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $remark
 * @property string|null $created_at
 * @property int|null $modified_by
 * @property string|null $modified_at
 * @property int|null $status
 */
class Exam extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'exam';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'modified_at'], 'safe'],
            [['modified_by', 'status'], 'integer'],
            [['title', 'remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'remark' => 'Remark',
            'created_at' => 'Created At',
            'modified_by' => 'Modified By',
            'modified_at' => 'Modified At',
            'status' => 'Status',
        ];
    }
}
