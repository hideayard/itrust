<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "myfxbook_scraped_data".
 *
 * @property int $id
 * @property string $pair
 * @property string $timeframe
 * @property string|null $url
 * @property string|null $economic_calendar
 * @property string|null $technical_analysis
 * @property string|null $interest_rates
 * @property string|null $price_info
 * @property string|null $technical_summary
 * @property string|null $raw_data
 * @property string $scrape_timestamp
 * @property string $created_at
 * @property string $updated_at
 * @property string $source
 * @property int $status
 */
class MyfxbookScrapedDataNew extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'myfxbook_scraped_data_new';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pair', 'timeframe', 'scrape_timestamp'], 'required'],
            [['economic_calendar', 'technical_analysis', 'interest_rates', 'raw_data'], 'safe'],
            [['price_info', 'technical_summary'], 'string'],
            [['scrape_timestamp', 'created_at', 'updated_at'], 'safe'],
            [['status'], 'integer'],
            [['pair'], 'string', 'max' => 20],
            [['timeframe'], 'string', 'max' => 10],
            [['url'], 'string', 'max' => 500],
            [['source'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pair' => 'Pair',
            'timeframe' => 'Timeframe',
            'url' => 'Url',
            'economic_calendar' => 'Economic Calendar',
            'technical_analysis' => 'Technical Analysis',
            'interest_rates' => 'Interest Rates',
            'price_info' => 'Price Info',
            'technical_summary' => 'Technical Summary',
            'raw_data' => 'Raw Data',
            'scrape_timestamp' => 'Scrape Timestamp',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'source' => 'Source',
            'status' => 'Status',
        ];
    }
    
    /**
     * Get economic calendar as array
     */
    public function getEconomicCalendarArray()
    {
        return $this->economic_calendar ? json_decode($this->economic_calendar, true) : [];
    }
    
    /**
     * Get technical analysis as array
     */
    public function getTechnicalAnalysisArray()
    {
        return $this->technical_analysis ? json_decode($this->technical_analysis, true) : [];
    }
    
    /**
     * Get interest rates as array
     */
    public function getInterestRatesArray()
    {
        return $this->interest_rates ? json_decode($this->interest_rates, true) : [];
    }
    
    /**
     * Get latest data for a pair and timeframe
     */
    public static function getLatestByPairAndTimeframe($pair, $timeframe, $limit = 10)
    {
        return self::find()
            ->where(['pair' => $pair, 'timeframe' => $timeframe, 'status' => 1])
            ->orderBy(['scrape_timestamp' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
    
    /**
     * Get economic events count
     */
    public function getEconomicEventsCount()
    {
        $calendar = $this->getEconomicCalendarArray();
        return $calendar['totalEvents'] ?? 0;
    }
    
    /**
     * Get technical patterns count
     */
    public function getTechnicalPatternsCount()
    {
        $analysis = $this->getTechnicalAnalysisArray();
        return $analysis['totalPatterns'] ?? 0;
    }
}