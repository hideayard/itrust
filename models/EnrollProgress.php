<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "enroll_progress".
 *
 * @property int $ep_id
 * @property int|null $ep_enroll_id
 * @property int|null $ep_section_id
 * @property string|null $ep_remark
 * @property string $ep_created_at
 * @property int|null $ep_created_by
 * @property string $ep_modified_at
 * @property int|null $ep_modified_by
 * @property int $ep_status
 */
class EnrollProgress extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'enroll_progress';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ep_enroll_id', 'ep_section_id', 'ep_created_by', 'ep_modified_by', 'ep_status'], 'integer'],
            [['ep_remark'], 'string'],
            [['ep_created_at', 'ep_modified_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ep_id' => 'Ep ID',
            'ep_enroll_id' => 'Ep Enroll ID',
            'ep_section_id' => 'Ep Section ID',
            'ep_remark' => 'Ep Remark',
            'ep_created_at' => 'Ep Created At',
            'ep_created_by' => 'Ep Created By',
            'ep_modified_at' => 'Ep Modified At',
            'ep_modified_by' => 'Ep Modified By',
            'ep_status' => 'Ep Status',
        ];
    }
}
