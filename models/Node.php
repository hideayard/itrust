<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "node".
 *
 * @property int $node_id
 * @property string $node_name
 * @property string|null $node_remark
 * @property string $node_created_at
 * @property int $node_created_by
 * @property string $node_modified_at
 * @property int $node_modified_by
 * @property int $node_status
 */
class Node extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'node';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['node_name', 'node_created_by', 'node_modified_by'], 'required'],
            [['node_created_at', 'node_modified_at'], 'safe'],
            [['node_created_by', 'node_modified_by', 'node_status'], 'integer'],
            [['node_name', 'node_remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'node_id' => 'Node ID',
            'node_name' => 'Node Name',
            'node_remark' => 'Node Remark',
            'node_created_at' => 'Node Created At',
            'node_created_by' => 'Node Created By',
            'node_modified_at' => 'Node Modified At',
            'node_modified_by' => 'Node Modified By',
            'node_status' => 'Node Status',
        ];
    }
}
