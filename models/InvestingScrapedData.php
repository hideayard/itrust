<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "investing_scraped_data".
 *
 * @property int $id
 * @property string $pair
 * @property string|null $timeframe
 * @property string|null $url
 * @property string|null $overall_signal
 * @property string|null $technical_indicators
 * @property string|null $moving_averages
 * @property string|null $pivot_points
 * @property string|null $summary_data
 * @property string|null $other_sections
 * @property string|null $raw_data
 * @property string $scrape_timestamp
 * @property string $created_at
 * @property string $updated_at
 * @property string $source
 * @property int $status
 */
class InvestingScrapedData extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'investing_scraped_data';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pair', 'scrape_timestamp'], 'required'],
            [['technical_indicators', 'moving_averages', 'pivot_points', 'summary_data', 'other_sections', 'raw_data'], 'safe'],
            [['scrape_timestamp', 'created_at', 'updated_at'], 'safe'],
            [['status'], 'integer'],
            [['pair'], 'string', 'max' => 20],
            [['timeframe'], 'string', 'max' => 10],
            [['url'], 'string', 'max' => 500],
            [['overall_signal', 'source'], 'string', 'max' => 50],
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
            'overall_signal' => 'Overall Signal',
            'technical_indicators' => 'Technical Indicators',
            'moving_averages' => 'Moving Averages',
            'pivot_points' => 'Pivot Points',
            'summary_data' => 'Summary Data',
            'other_sections' => 'Other Sections',
            'raw_data' => 'Raw Data',
            'scrape_timestamp' => 'Scrape Timestamp',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'source' => 'Source',
            'status' => 'Status',
        ];
    }
    
    /**
     * Get technical indicators as array
     */
    public function getTechnicalIndicatorsArray()
    {
        return $this->technical_indicators ? json_decode($this->technical_indicators, true) : [];
    }
    
    /**
     * Get moving averages as array
     */
    public function getMovingAveragesArray()
    {
        return $this->moving_averages ? json_decode($this->moving_averages, true) : [];
    }
    
    /**
     * Get pivot points as array
     */
    public function getPivotPointsArray()
    {
        return $this->pivot_points ? json_decode($this->pivot_points, true) : [];
    }
    
    /**
     * Get latest data for a pair
     */
    public static function getLatestByPair($pair, $limit = 10)
    {
        return self::find()
            ->where(['pair' => $pair, 'status' => 1])
            ->orderBy(['scrape_timestamp' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
}