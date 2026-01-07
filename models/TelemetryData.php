<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "telemetry_data".
 *
 * @property int $id
 * @property string $device_id
 * @property string|null $device_name
 * @property string $data_timestamp Telemetry data timestamp
 * @property float|null $ac_v AC Voltage in volts
 * @property float|null $ac_i AC Current in amps
 * @property float|null $ac_p AC Power in watts
 * @property float|null $energy Energy in kWh
 * @property float|null $freq Frequency in Hz
 * @property float|null $pf Power Factor (0.000 to 1.000)
 * @property float|null $dc_v DC Voltage in volts
 * @property float|null $dc_i DC Current in amps
 * @property int|null $low_v Low Voltage Warning (0/1)
 * @property int|null $gps_fixed GPS Fix Status (0/1)
 * @property float|null $lat Latitude
 * @property float|null $lng Longitude
 * @property int|null $relay_state Relay State (0=off, 1=on)
 * @property int|null $sample_count Number of samples averaged
 * @property string|null $sync_type Sync type: iteration/time
 * @property string|null $data_type Data type: averaged_telemetry/manual
 * @property string|null $buffer_start_time Buffer start time (ISO format)
 * @property string|null $buffer_end_time Buffer end time (ISO format)
 * @property int|null $original_sample_count Original sample count before averaging
 * @property string|null $device_manufacturer
 * @property string|null $device_model
 * @property int|null $sync_interval Sync interval in seconds
 * @property int|null $sync_iteration_count Sync iteration count
 * @property string|null $client_ip Client IP address
 * @property string|null $user_agent HTTP User-Agent
 * @property string $created_at
 * @property string|null $updated_at
 */
class TelemetryData extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'telemetry_data';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => function() {
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['device_id', 'data_timestamp'], 'required'],
            [['data_timestamp', 'created_at', 'updated_at'], 'safe'],
            [['ac_v', 'ac_i', 'ac_p', 'energy', 'freq', 'pf', 'dc_v', 'dc_i', 'lat', 'lng'], 'number'],
            [['low_v', 'gps_fixed', 'relay_state', 'sample_count', 'original_sample_count', 'sync_interval', 'sync_iteration_count'], 'integer'],
            [['user_agent'], 'string'],
            [['device_id'], 'string', 'max' => 100],
            [['device_name', 'device_manufacturer', 'device_model'], 'string', 'max' => 255],
            [['sync_type', 'data_type'], 'string', 'max' => 50],
            [['buffer_start_time', 'buffer_end_time'], 'string', 'max' => 255],
            [['client_ip'], 'string', 'max' => 45],
            [['data_timestamp'], 'date', 'format' => 'php:Y-m-d H:i:s'],
            [['pf'], 'number', 'min' => 0, 'max' => 1],
            
            // Default values
            [['low_v', 'gps_fixed', 'relay_state'], 'default', 'value' => 0],
            [['sample_count', 'original_sample_count'], 'default', 'value' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'device_id' => 'Device ID',
            'device_name' => 'Device Name',
            'data_timestamp' => 'Data Timestamp',
            'ac_v' => 'AC Voltage (V)',
            'ac_i' => 'AC Current (A)',
            'ac_p' => 'AC Power (W)',
            'energy' => 'Energy (kWh)',
            'freq' => 'Frequency (Hz)',
            'pf' => 'Power Factor',
            'dc_v' => 'DC Voltage (V)',
            'dc_i' => 'DC Current (A)',
            'low_v' => 'Low Voltage Warning',
            'gps_fixed' => 'GPS Fixed',
            'lat' => 'Latitude',
            'lng' => 'Longitude',
            'relay_state' => 'Relay State',
            'sample_count' => 'Sample Count',
            'sync_type' => 'Sync Type',
            'data_type' => 'Data Type',
            'buffer_start_time' => 'Buffer Start Time',
            'buffer_end_time' => 'Buffer End Time',
            'original_sample_count' => 'Original Sample Count',
            'device_manufacturer' => 'Device Manufacturer',
            'device_model' => 'Device Model',
            'sync_interval' => 'Sync Interval (s)',
            'sync_iteration_count' => 'Sync Iteration Count',
            'client_ip' => 'Client IP',
            'user_agent' => 'User Agent',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return [
            'ac_v' => 'AC Voltage in volts',
            'ac_i' => 'AC Current in amps',
            'ac_p' => 'AC Power in watts',
            'energy' => 'Energy in kWh',
            'freq' => 'Frequency in Hz',
            'pf' => 'Power Factor (0.000 to 1.000)',
            'dc_v' => 'DC Voltage in volts',
            'dc_i' => 'DC Current in amps',
            'low_v' => 'Low Voltage Warning (0=normal, 1=warning)',
            'gps_fixed' => 'GPS Fix Status (0=not fixed, 1=fixed)',
            'relay_state' => 'Relay State (0=off, 1=on)',
        ];
    }

    /**
     * Get user devices associated with this telemetry device
     */
    public function getUserDevices()
    {
        return $this->hasMany(UserDevices::className(), ['device_id' => 'device_id']);
    }

    /**
     * Get users associated with this device
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['user_id' => 'user_id'])
            ->via('userDevices');
    }

    /**
     * Check if device is assigned to a specific user
     */
    public function isAssignedToUser($user_id)
    {
        return UserDevices::find()
            ->where(['device_id' => $this->device_id, 'user_id' => $user_id, 'is_active' => 1])
            ->exists();
    }

    /**
     * Get the latest telemetry for a device
     */
    public static function getLatestByDevice($device_id)
    {
        return self::find()
            ->where(['device_id' => $device_id])
            ->orderBy(['data_timestamp' => SORT_DESC])
            ->one();
    }

    /**
     * Get telemetry data within a time range
     */
    public static function getByTimeRange($device_id, $start_date, $end_date, $limit = null)
    {
        $query = self::find()
            ->where(['device_id' => $device_id])
            ->andWhere(['>=', 'data_timestamp', $start_date])
            ->andWhere(['<=', 'data_timestamp', $end_date])
            ->orderBy(['data_timestamp' => SORT_ASC]);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->all();
    }

    /**
     * Get daily energy summary
     */
    public static function getDailyEnergySummary($device_id, $days = 30)
    {
        $start_date = date('Y-m-d 00:00:00', strtotime("-$days days"));
        
        return self::find()
            ->select([
                'DATE(data_timestamp) as date',
                'MAX(energy) as max_energy',
                'MIN(energy) as min_energy',
                'AVG(ac_p) as avg_power',
                'COUNT(*) as record_count'
            ])
            ->where(['device_id' => $device_id])
            ->andWhere(['>=', 'data_timestamp', $start_date])
            ->groupBy('DATE(data_timestamp)')
            ->orderBy(['date' => SORT_DESC])
            ->asArray()
            ->all();
    }

    /**
     * Get power statistics
     */
    public static function getPowerStatistics($device_id, $start_date = null, $end_date = null)
    {
        $query = self::find()
            ->where(['device_id' => $device_id]);
        
        if ($start_date) {
            $query->andWhere(['>=', 'data_timestamp', $start_date]);
        }
        
        if ($end_date) {
            $query->andWhere(['<=', 'data_timestamp', $end_date]);
        }
        
        return $query
            ->select([
                'AVG(ac_p) as avg_power',
                'MAX(ac_p) as max_power',
                'MIN(ac_p) as min_power',
                'AVG(ac_v) as avg_voltage',
                'AVG(ac_i) as avg_current',
                'AVG(pf) as avg_power_factor',
                'SUM(energy) as total_energy',
                'COUNT(*) as record_count'
            ])
            ->asArray()
            ->one();
    }

    /**
     * Get location history
     */
    public static function getLocationHistory($device_id, $limit = 100)
    {
        return self::find()
            ->select(['lat', 'lng', 'data_timestamp', 'gps_fixed'])
            ->where(['device_id' => $device_id])
            ->andWhere(['IS NOT', 'lat', null])
            ->andWhere(['IS NOT', 'lng', null])
            ->orderBy(['data_timestamp' => SORT_DESC])
            ->limit($limit)
            ->asArray()
            ->all();
    }

    /**
     * Get device list with latest telemetry
     */
    public static function getDeviceListWithLatest($user_id = null)
    {
        $subQuery = self::find()
            ->select(['device_id', 'MAX(data_timestamp) as latest_timestamp'])
            ->groupBy('device_id');
        
        $query = self::find()
            ->select(['td.*'])
            ->from(['td' => self::tableName()])
            ->innerJoin(['sq' => $subQuery], 
                'td.device_id = sq.device_id AND td.data_timestamp = sq.latest_timestamp');
        
        if ($user_id) {
            $query->innerJoin(['ud' => UserDevices::tableName()], 
                'td.device_id = ud.device_id')
                ->where(['ud.user_id' => $user_id, 'ud.is_active' => 1]);
        }
        
        return $query->asArray()->all();
    }

    /**
     * Get telemetry count by device
     */
    public static function getCountByDevice($device_id)
    {
        return self::find()
            ->where(['device_id' => $device_id])
            ->count();
    }

    /**
     * Get first and last telemetry timestamps for a device
     */
    public static function getTimeRangeByDevice($device_id)
    {
        return self::find()
            ->select([
                'MIN(data_timestamp) as first_timestamp',
                'MAX(data_timestamp) as last_timestamp',
                'COUNT(*) as total_records'
            ])
            ->where(['device_id' => $device_id])
            ->asArray()
            ->one();
    }

    /**
     * Get devices with telemetry data (for admin)
     */
    public static function getAllDevices($limit = null)
    {
        $query = self::find()
            ->select(['device_id', 'device_name', 'device_manufacturer', 'device_model'])
            ->distinct()
            ->orderBy(['device_id' => SORT_ASC]);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->asArray()->all();
    }

    /**
     * Search telemetry data
     */
    public static function search($device_id = null, $start_date = null, $end_date = null, $keyword = null)
    {
        $query = self::find();
        
        if ($device_id) {
            $query->andWhere(['device_id' => $device_id]);
        }
        
        if ($start_date) {
            $query->andWhere(['>=', 'data_timestamp', $start_date]);
        }
        
        if ($end_date) {
            $query->andWhere(['<=', 'data_timestamp', $end_date]);
        }
        
        if ($keyword) {
            $query->andWhere(['or',
                ['like', 'device_id', $keyword],
                ['like', 'device_name', $keyword],
                ['like', 'device_manufacturer', $keyword],
                ['like', 'device_model', $keyword],
                ['like', 'sync_type', $keyword],
                ['like', 'data_type', $keyword],
            ]);
        }
        
        return $query;
    }

    /**
     * Get boolean value for relay state
     */
    public function getRelayStateBool()
    {
        return (bool)$this->relay_state;
    }

    /**
     * Get boolean value for low voltage warning
     */
    public function getLowVoltageWarningBool()
    {
        return (bool)$this->low_v;
    }

    /**
     * Get boolean value for GPS fixed status
     */
    public function getGpsFixedBool()
    {
        return (bool)$this->gps_fixed;
    }

    /**
     * Get formatted location string
     */
    public function getFormattedLocation()
    {
        if ($this->lat && $this->lng) {
            return sprintf('%.6f, %.6f', $this->lat, $this->lng);
        }
        return null;
    }

    /**
     * Get Google Maps link
     */
    public function getGoogleMapsLink()
    {
        if ($this->lat && $this->lng) {
            return sprintf('https://maps.google.com/?q=%s,%s', $this->lat, $this->lng);
        }
        return null;
    }

    /**
     * Calculate power from voltage and current if not provided
     */
    public function calculatePower()
    {
        if ($this->ac_v !== null && $this->ac_i !== null) {
            return $this->ac_v * $this->ac_i * ($this->pf ?: 1);
        }
        return null;
    }

    /**
     * Before save validation
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Ensure timestamps are in correct format
            if ($this->data_timestamp instanceof \DateTime) {
                $this->data_timestamp = $this->data_timestamp->format('Y-m-d H:i:s');
            }
            
            // Calculate power if not set but voltage and current are available
            if ($this->ac_p === null && $this->ac_v !== null && $this->ac_i !== null) {
                $this->ac_p = $this->calculatePower();
            }
            
            return true;
        }
        return false;
    }
}