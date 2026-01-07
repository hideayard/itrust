<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "myfxbook_interest_rates".
 *
 * @property int $id
 * @property int $scrape_data_id
 * @property string|null $country
 * @property string|null $central_bank
 * @property string|null $current_rate
 * @property string|null $previous_rate
 * @property string|null $next_meeting
 * @property int|null $row_index
 * @property string $created_at
 *
 * @property MyfxbookScrapedData $scrapeData
 */
class MyfxbookInterestRate extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'myfxbook_interest_rates';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['scrape_data_id'], 'required'],
            [['scrape_data_id', 'row_index'], 'integer'],
            [['created_at'], 'safe'],
            [['country', 'central_bank', 'current_rate', 'previous_rate', 'next_meeting'], 'string', 'max' => 255],
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
            'country' => 'Country',
            'central_bank' => 'Central Bank',
            'current_rate' => 'Current Rate',
            'previous_rate' => 'Previous Rate',
            'next_meeting' => 'Next Meeting',
            'row_index' => 'Row Index',
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

    /**
     * Extract numeric rate value
     *
     * @return float|null
     */
    public function getRateValue()
    {
        if (preg_match('/(\d+\.?\d*)/', $this->current_rate, $matches)) {
            return (float) $matches[1];
        }
        return null;
    }
}