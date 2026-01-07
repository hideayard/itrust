<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "myfxbook_technical_summary".
 *
 * @property int $id
 * @property int $scrape_data_id
 * @property string|null $technical_summary
 * @property int $total_patterns
 * @property int $buy_count
 * @property int $sell_count
 * @property int $neutral_count
 * @property int $header_buy_count
 * @property int $header_sell_count
 * @property string $created_at
 *
 * @property MyfxbookScrapedData $scrapeData
 */
class MyfxbookTechnicalSummary extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'myfxbook_technical_summary';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['scrape_data_id'], 'required'],
            [['scrape_data_id', 'total_patterns', 'buy_count', 'sell_count', 'neutral_count', 'header_buy_count', 'header_sell_count'], 'integer'],
            [['created_at'], 'safe'],
            [['technical_summary'], 'string', 'max' => 500],
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
            'technical_summary' => 'Technical Summary',
            'total_patterns' => 'Total Patterns',
            'buy_count' => 'Buy Count',
            'sell_count' => 'Sell Count',
            'neutral_count' => 'Neutral Count',
            'header_buy_count' => 'Header Buy Count',
            'header_sell_count' => 'Header Sell Count',
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
     * Calculate buy percentage
     *
     * @return float
     */
    public function getBuyPercentage()
    {
        if ($this->total_patterns > 0) {
            return round(($this->buy_count / $this->total_patterns) * 100, 2);
        }
        return 0;
    }

    /**
     * Calculate sell percentage
     *
     * @return float
     */
    public function getSellPercentage()
    {
        if ($this->total_patterns > 0) {
            return round(($this->sell_count / $this->total_patterns) * 100, 2);
        }
        return 0;
    }

    /**
     * Calculate neutral percentage
     *
     * @return float
     */
    public function getNeutralPercentage()
    {
        if ($this->total_patterns > 0) {
            return round(($this->neutral_count / $this->total_patterns) * 100, 2);
        }
        return 0;
    }
}