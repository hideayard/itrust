<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "do_exam".
 *
 * @property int $id
 * @property int|null $id_user
 * @property int|null $id_exam
 * @property int|null $id_soal
 * @property string|null $jawaban
 * @property string|null $nilai
 * @property string|null $created_at
 * @property int|null $modified_by
 * @property string|null $modified_at
 * @property int|null $status
 */
class DoExam extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'do_exam';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_user', 'id_exam', 'id_soal', 'modified_by', 'status'], 'integer'],
            [['created_at', 'modified_at'], 'safe'],
            [['jawaban', 'nilai'], 'string', 'max' => 5],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_user' => 'Id User',
            'id_exam' => 'Id Exam',
            'id_soal' => 'Id Soal',
            'jawaban' => 'Jawaban',
            'nilai' => 'Nilai',
            'created_at' => 'Created At',
            'modified_by' => 'Modified By',
            'modified_at' => 'Modified At',
            'status' => 'Status',
        ];
    }
}
