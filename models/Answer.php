<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "answer".
 *
 * @property int $id
 * @property int $question_id
 * @property int $user_id
 * @property string $answer
 */
class Answer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'answer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['question_id', 'user_id', 'answer'], 'required'],
            [['question_id', 'user_id'], 'integer'],
            [['answer'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'question_id' => 'Question ID',
            'user_id' => 'User ID',
            'answer' => 'Answer',
        ];
    }
}
