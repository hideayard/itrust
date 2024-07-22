<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "question".
 *
 * @property int $id
 * @property int|null $section_id
 * @property string|null $question
 * @property string|null $keyword
 * @property string|null $type
 * @property int|null $order
 * @property int|null $active
 */
class Question extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'question';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['section_id', 'order', 'active'], 'integer'],
            [['question'], 'string', 'max' => 255],
            [['keyword', 'type'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'section_id' => 'Section ID',
            'question' => 'Question',
            'keyword' => 'Keyword',
            'type' => 'Type',
            'order' => 'Order',
            'active' => 'Active',
        ];
    }
}
