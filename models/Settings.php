<?php

namespace app\models;

use Yii;

class Settings extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'settings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['settings_date'], 'safe'],
            [['settings_type','settings_name','settings_value','settings_status'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'settings_id' => 'Settings ID',
            'settings_type' => 'Settings Type',
            'settings_name' => 'Settings name',
            'settings_value' => 'Settings Value',
            'settings_status' => 'Settings Status',
            'settings_date' => 'Settings Date',
        ];
    }
}
