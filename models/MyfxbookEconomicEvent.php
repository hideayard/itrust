<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "myfxbook_economic_events".
 *
 * @property int $id
 * @property int $scrape_data_id
 * @property string|null $event_time
 * @property string|null $currency
 * @property string $event_name
 * @property string|null $impact
 * @property string|null $impact_text
 * @property string|null $previous_value
 * @property string|null $forecast_value
 * @property string|null $country
 * @property string|null $country_code
 * @property string $created_at
 *
 * @property MyfxbookScrapedData $scrapeData
 */
class MyfxbookEconomicEvent extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'myfxbook_economic_events';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['scrape_data_id', 'event_name'], 'required'],
            [['scrape_data_id'], 'integer'],
            [['event_name'], 'string'],
            [['created_at'], 'safe'],
            [['event_time', 'currency', 'impact', 'impact_text', 'previous_value', 'forecast_value', 'country', 'country_code'], 'string', 'max' => 255],
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
            'event_time' => 'Event Time',
            'currency' => 'Currency',
            'event_name' => 'Event Name',
            'impact' => 'Impact',
            'impact_text' => 'Impact Text',
            'previous_value' => 'Previous Value',
            'forecast_value' => 'Forecast Value',
            'country' => 'Country',
            'country_code' => 'Country Code',
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