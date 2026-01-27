<?php

namespace app\models;

use Yii;


/**
 * This is the model class for table "dual_source_scraped_data".
 *
 * @property int $id
 * @property string $pair
 * @property string $timeframe
 * @property string|null $investing_data
 * @property string|null $myfxbook_data
 * @property string|null $combined_data
 * @property string|null $scrape_timestamp
 * @property string|null $combined_at
 * @property string|null $investing_url
 * @property string|null $myfxbook_url
 * @property string $created_at
 */
class DualSourceScrapedData extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dual_source_scraped_data';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pair', 'timeframe'], 'required'],
            [['investing_data', 'myfxbook_data', 'combined_data'], 'string'],
            [['scrape_timestamp', 'combined_at', 'created_at'], 'safe'],
            [['pair'], 'string', 'max' => 10],
            [['timeframe'], 'string', 'max' => 5],
            [['investing_url', 'myfxbook_url'], 'string', 'max' => 500],
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
            'investing_data' => 'Investing Data',
            'myfxbook_data' => 'Myfxbook Data',
            'combined_data' => 'Combined Data',
            'scrape_timestamp' => 'Scrape Timestamp',
            'combined_at' => 'Combined At',
            'investing_url' => 'Investing Url',
            'myfxbook_url' => 'Myfxbook Url',
            'created_at' => 'Created At',
        ];
    }
}