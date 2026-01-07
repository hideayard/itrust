<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "myfxbook_technical_patterns".
 *
 * @property int $id
 * @property int $scrape_data_id
 * @property string $pattern_name
 * @property int|null $row_index
 * @property string|null $signal
 * @property string|null $buy_value
 * @property string|null $sell_value
 * @property string|null $timeframes
 * @property string $created_at
 *
 * @property MyfxbookScrapedData $scrapeData
 */
class MyfxbookTechnicalPattern extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'myfxbook_technical_patterns';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['scrape_data_id', 'pattern_name'], 'required'],
            [['scrape_data_id', 'row_index'], 'integer'],
            [['created_at'], 'safe'],
            [['pattern_name', 'buy_value', 'sell_value', 'timeframes'], 'string', 'max' => 255],
            [['signal'], 'string', 'max' => 20],
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
            'pattern_name' => 'Pattern Name',
            'row_index' => 'Row Index',
            'signal' => 'Signal',
            'buy_value' => 'Buy Value',
            'sell_value' => 'Sell Value',
            'timeframes' => 'Timeframes',
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