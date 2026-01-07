<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "myfxbook_metadata".
 *
 * @property int $id
 * @property int $scrape_data_id
 * @property string $key_name
 * @property string|null $key_value
 * @property string $created_at
 *
 * @property MyfxbookScrapedData $scrapeData
 */
class MyfxbookMetadata extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'myfxbook_metadata';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['scrape_data_id', 'key_name'], 'required'],
            [['scrape_data_id'], 'integer'],
            [['key_value'], 'string'],
            [['created_at'], 'safe'],
            [['key_name'], 'string', 'max' => 100],
            [['scrape_data_id'], 'exist', 'skipOnError' => true, 'targetClass' => MyfxbookScrapedData::className(), 'targetAttribute' => ['scrape_data_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'scrape_data_id' => 'Scrape Data ID',
            'key_name' => 'Key Name',
            'key_value' => 'Key Value',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[ScrapeData]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getScrapeData()
    {
        return $this->hasOne(MyfxbookScrapedData::className(), ['id' => 'scrape_data_id']);
    }
}