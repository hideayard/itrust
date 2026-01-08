<?php

namespace app\models;

use app\helpers\TelegramHelper;
use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "myfxbook_scraped_data".
 *
 * @property int $id
 * @property string $scrape_timestamp
 * @property int $scrape_number
 * @property string $url
 * @property int|null $refresh_interval
 * @property string $created_at
 * @property string $updated_at
 *
 * @property MyfxbookEconomicEvent[] $economicEvents
 * @property MyfxbookTechnicalPattern[] $technicalPatterns
 * @property MyfxbookTechnicalSummary $technicalSummary
 * @property MyfxbookInterestRate[] $interestRates
 * @property MyfxbookMetadata[] $metadata
 */
class MyfxbookScrapedData extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'myfxbook_scraped_data';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['scrape_timestamp', 'scrape_number', 'url'], 'required'],
            [['scrape_timestamp'], 'safe'],
            [['scrape_number', 'refresh_interval'], 'integer'],
            [['url'], 'string', 'max' => 500],
            [['scrape_number'], 'unique', 'targetAttribute' => ['scrape_timestamp', 'scrape_number'], 'message' => 'The combination of Scrape Timestamp and Scrape Number has already been taken.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'scrape_timestamp' => 'Scrape Timestamp',
            'scrape_number' => 'Scrape Number',
            'url' => 'Url',
            'refresh_interval' => 'Refresh Interval',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[EconomicEvents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEconomicEvents()
    {
        return $this->hasMany(MyfxbookEconomicEvent::className(), ['scrape_data_id' => 'id']);
    }

    /**
     * Gets query for [[TechnicalPatterns]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTechnicalPatterns()
    {
        return $this->hasMany(MyfxbookTechnicalPattern::className(), ['scrape_data_id' => 'id']);
    }

    /**
     * Gets query for [[TechnicalSummary]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTechnicalSummary()
    {
        return $this->hasOne(MyfxbookTechnicalSummary::className(), ['scrape_data_id' => 'id']);
    }

    /**
     * Gets query for [[InterestRates]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInterestRates()
    {
        return $this->hasMany(MyfxbookInterestRate::className(), ['scrape_data_id' => 'id']);
    }

    /**
     * Gets query for [[Metadata]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMetadata()
    {
        return $this->hasMany(MyfxbookMetadata::className(), ['scrape_data_id' => 'id']);
    }

    /**
     * Save complete scraped data from JSON
     *
     * @param array $scrapeData
     * @return bool
     */
    public static function saveScrapedData(array $scrapeData)
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Extract metadata
            $metadata = $scrapeData['metadata'] ?? [];
            $data = $scrapeData['data'] ?? [];

            // Save main scraped data
            $scrapedData = new self();
            $scrapedData->scrape_timestamp = $metadata['scrapeTimestamp'] ?? date('Y-m-d H:i:s');
            $scrapedData->scrape_number = $metadata['scrapeCount'] ?? 0;
            $scrapedData->url = $metadata['url'] ?? '';
            $scrapedData->refresh_interval = $metadata['refreshInterval'] ?? null;

            if (!$scrapedData->save()) {
                Yii::error('Failed to save scraped data: ' . json_encode($scrapedData->errors));
                TelegramHelper::reportModelError(
                    $scrapedData,
                    'Failed to save economic event'
                );
                $transaction->rollBack();
                return false;
            }

            $scrapeId = $scrapedData->id;

            // Save economic events
            if (isset($data['economicCalendar']['events'])) {
                foreach ($data['economicCalendar']['events'] as $event) {
                    $economicEvent = new MyfxbookEconomicEvent();
                    $economicEvent->scrape_data_id = $scrapeId;
                    $economicEvent->event_time = $event['time'] ?? null;
                    $economicEvent->currency = $event['currency'] ?? null;
                    $economicEvent->event_name = $event['event'] ?? '';
                    $economicEvent->impact = $event['impact'] ?? null;
                    $economicEvent->impact_text = $event['impactText'] ?? null;
                    $economicEvent->previous_value = $event['previous'] ?? null;
                    $economicEvent->forecast_value = $event['forecast'] ?? null;
                    $economicEvent->country = $event['country'] ?? null;
                    $economicEvent->country_code = $event['countryCode'] ?? null;

                    if (!$economicEvent->save()) {
                        Yii::error('Failed to save economic event: ' . json_encode($economicEvent->errors));
                        TelegramHelper::reportModelError(
                            $economicEvent,
                            'Failed to save economic event'
                        );
                        $transaction->rollBack();
                        return false;
                    }
                }
            }

            // Save technical analysis
            if (isset($data['technicalAnalysis'])) {
                $taData = $data['technicalAnalysis'];

                // Save technical summary
                $technicalSummary = new MyfxbookTechnicalSummary();
                $technicalSummary->scrape_data_id = $scrapeId;
                $technicalSummary->technical_summary = $taData['technicalSummary'] ?? null;
                $technicalSummary->total_patterns = $taData['totalPatterns'] ?? 0;
                $technicalSummary->buy_count = $taData['counts']['buy'] ?? 0;
                $technicalSummary->sell_count = $taData['counts']['sell'] ?? 0;
                $technicalSummary->neutral_count = $taData['counts']['neutral'] ?? 0;
                $technicalSummary->header_buy_count = $taData['headerCounts']['buy'] ?? 0;
                $technicalSummary->header_sell_count = $taData['headerCounts']['sell'] ?? 0;

                if (!$technicalSummary->save()) {
                    Yii::error('Failed to save technical summary: ' . json_encode($technicalSummary->errors));
                    TelegramHelper::reportModelError(
                        $economicEvent,
                        'Failed to save economic event'
                    );
                    $transaction->rollBack();
                    return false;
                }

                // Save technical patterns
                if (isset($taData['patterns'])) {
                    foreach ($taData['patterns'] as $pattern) {
                        $technicalPattern = new MyfxbookTechnicalPattern();
                        $technicalPattern->scrape_data_id = $scrapeId;
                        $technicalPattern->pattern_name = $pattern['name'] ?? '';
                        $technicalPattern->row_index = $pattern['rowIndex'] ?? null;
                        $technicalPattern->signal = $pattern['signal'] ?? null;
                        $technicalPattern->buy_value = $pattern['buy'] ?? null;
                        $technicalPattern->sell_value = $pattern['sell'] ?? null;
                        $technicalPattern->timeframes = $pattern['timeframes'] ?? null;

                        if (!$technicalPattern->save()) {
                            Yii::error('Failed to save technical pattern: ' . json_encode($technicalPattern->errors));
                            TelegramHelper::reportModelError(
                                $technicalPattern,
                                'Failed to save economic event'
                            );
                            $transaction->rollBack();
                            return false;
                        }
                    }
                }
            }

            // Save interest rates
            if (isset($data['interestRates'])) {
                foreach ($data['interestRates'] as $rate) {
                    $interestRate = new MyfxbookInterestRate();
                    $interestRate->scrape_data_id = $scrapeId;
                    $interestRate->country = $rate['country'] ?? null;
                    $interestRate->central_bank = $rate['centralBank'] ?? null;
                    $interestRate->current_rate = $rate['currentRate'] ?? null;
                    $interestRate->previous_rate = $rate['previousRate'] ?? null;
                    $interestRate->next_meeting = $rate['nextMeeting'] ?? null;
                    $interestRate->row_index = $rate['rowIndex'] ?? null;

                    if (!$interestRate->save()) {
                        Yii::error('Failed to save interest rate: ' . json_encode($interestRate->errors));
                        TelegramHelper::reportModelError(
                            $interestRate,
                            'Failed to save economic event'
                        );
                        $transaction->rollBack();
                        return false;
                    }
                }
            }

            // Save metadata
            foreach ($metadata as $key => $value) {
                $meta = new MyfxbookMetadata();
                $meta->scrape_data_id = $scrapeId;
                $meta->key_name = $key;
                $meta->key_value = is_array($value) ? json_encode($value) : $value;

                if (!$meta->save()) {
                    Yii::error('Failed to save metadata: ' . json_encode($meta->errors));
                    TelegramHelper::reportModelError(
                        $meta,
                        'Failed to save economic event'
                    );
                    $transaction->rollBack();
                    return false;
                }
            }

            // Update statistics
            self::updateStatistics($scrapeId);

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            TelegramHelper::reportModelError(
                $e->getMessage(),
                'Failed to save economic event'
            );
            Yii::error('Error saving scraped data: ' . $e->getMessage());

            $transaction->rollBack();
            return false;
        }
    }

    /**
     * Update scraping statistics
     *
     * @param int $scrapeId
     * @return bool
     */
    private static function updateStatistics($scrapeId)
    {
        $date = date('Y-m-d');

        // Get statistics for today
        $statistics = MyfxbookStatistics::findOne(['date' => $date]);

        if (!$statistics) {
            $statistics = new MyfxbookStatistics();
            $statistics->date = $date;
        }

        // Count totals from this scrape
        $scrapeData = self::findOne($scrapeId);

        if ($scrapeData) {
            $statistics->total_scrapes += 1;
            $statistics->total_events += count($scrapeData->economicEvents);
            $statistics->total_patterns += count($scrapeData->technicalPatterns);
            $statistics->total_rates += count($scrapeData->interestRates);

            // Calculate average signals
            if ($scrapeData->technicalSummary) {
                $totalPatterns = $scrapeData->technicalSummary->total_patterns;
                if ($totalPatterns > 0) {
                    $statistics->avg_buy_signal = ($statistics->avg_buy_signal + ($scrapeData->technicalSummary->buy_count / $totalPatterns)) / 2;
                    $statistics->avg_sell_signal = ($statistics->avg_sell_signal + ($scrapeData->technicalSummary->sell_count / $totalPatterns)) / 2;
                }
            }

            return $statistics->save();
        }

        return false;
    }

    /**
     * Get latest scraped data
     *
     * @param int $limit
     * @return array
     */
    public static function getLatestScrapes($limit = 10)
    {
        return self::find()
            ->with(['economicEvents', 'technicalPatterns', 'technicalSummary', 'interestRates'])
            ->orderBy(['scrape_timestamp' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Get scrapes by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getScrapesByDateRange($startDate, $endDate)
    {
        return self::find()
            ->with(['economicEvents', 'technicalPatterns', 'technicalSummary', 'interestRates'])
            ->where(['>=', 'scrape_timestamp', $startDate])
            ->andWhere(['<=', 'scrape_timestamp', $endDate])
            ->orderBy(['scrape_timestamp' => SORT_ASC])
            ->all();
    }

    /**
     * Get high impact events
     *
     * @param string|null $currency
     * @param int $limit
     * @return array
     */
    public static function getHighImpactEvents($currency = null, $limit = 20)
    {
        $query = MyfxbookEconomicEvent::find()
            ->joinWith('scrapeData')
            ->where(['impact' => 'high'])
            ->orderBy(['myfxbook_economic_events.created_at' => SORT_DESC]);

        if ($currency) {
            $query->andWhere(['currency' => $currency]);
        }

        return $query->limit($limit)->all();
    }
}
