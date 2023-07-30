<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "soal".
 *
 * @property int $id
 * @property int|null $id_exam
 * @property string|null $title
 * @property string|null $soal_a
 * @property string|null $soal_b
 * @property string|null $soal_c
 * @property string|null $soal_d
 * @property string|null $jawaban
 * @property string|null $created_at
 * @property int|null $modified_by
 * @property string|null $modified_at
 * @property int|null $status
 */
class Soal extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'soal';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_exam', 'modified_by', 'status'], 'integer'],
            [['created_at', 'modified_at'], 'safe'],
            [['title', 'soal_a', 'soal_b', 'soal_c', 'soal_d'], 'string', 'max' => 255],
            [['jawaban'], 'string', 'max' => 5],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_exam' => 'Id Exam',
            'title' => 'Title',
            'soal_a' => 'Soal A',
            'soal_b' => 'Soal B',
            'soal_c' => 'Soal C',
            'soal_d' => 'Soal D',
            'jawaban' => 'Jawaban',
            'created_at' => 'Created At',
            'modified_by' => 'Modified By',
            'modified_at' => 'Modified At',
            'status' => 'Status',
        ];
    }
}
