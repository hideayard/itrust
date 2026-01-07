<?php

namespace app\helpers;

use app\models\TelemetryData;
use Yii;

class TelemetryHelper
{
    /**
     * Get device status summary
     */
    public static function getDeviceStatus($device_id)
    {
        $latest = TelemetryData::getLatestByDevice($device_id);
        
        if (!$latest) {
            return [
                'status' => 'offline',
                'last_seen' => null,
                'message' => 'No telemetry data found'
            ];
        }
        
        $lastSeen = strtotime($latest->data_timestamp);
        $currentTime = time();
        $minutesDiff = ($currentTime - $lastSeen) / 60;
        
        if ($minutesDiff < 5) {
            $status = 'online';
        } elseif ($minutesDiff < 15) {
            $status = 'warning';
        } else {
            $status = 'offline';
        }
        
        return [
            'status' => $status,
            'last_seen' => $latest->data_timestamp,
            'minutes_ago' => round($minutesDiff, 1),
            'current_power' => $latest->ac_p,
            'relay_state' => $latest->getRelayStateBool(),
            'low_voltage' => $latest->getLowVoltageWarningBool(),
            'gps_fixed' => $latest->getGpsFixedBool(),
            'location' => $latest->getFormattedLocation(),
        ];
    }

    /**
     * Get energy consumption report
     */
    public static function getEnergyReport($device_id, $period = 'day')
    {
        $report = [];
        
        switch ($period) {
            case 'hour':
                $interval = '1 HOUR';
                $format = 'Y-m-d H:00:00';
                break;
            case 'day':
                $interval = '1 DAY';
                $format = 'Y-m-d';
                break;
            case 'week':
                $interval = '1 WEEK';
                $format = 'Y-W';
                break;
            case 'month':
                $interval = '1 MONTH';
                $format = 'Y-m';
                break;
            default:
                $interval = '1 DAY';
                $format = 'Y-m-d';
        }
        
        $query = Yii::$app->db->createCommand("
            SELECT 
                DATE_FORMAT(data_timestamp, :format) as period,
                MAX(energy) - MIN(energy) as energy_consumed,
                AVG(ac_p) as avg_power,
                MAX(ac_p) as max_power,
                COUNT(*) as readings
            FROM telemetry_data
            WHERE device_id = :device_id
            GROUP BY period
            ORDER BY period DESC
            LIMIT 30
        ");
        
        $query->bindValues([
            ':device_id' => $device_id,
            ':format' => $format
        ]);
        
        $result = $query->queryAll();
        
        return $result;
    }

    /**
     * Get alerts for device
     */
    public static function getDeviceAlerts($device_id, $hours = 24)
    {
        $startTime = date('Y-m-d H:i:s', strtotime("-$hours hours"));
        
        $alerts = [];
        
        // Check for low voltage alerts
        $lowVoltageData = TelemetryData::find()
            ->where(['device_id' => $device_id])
            ->andWhere(['>=', 'data_timestamp', $startTime])
            ->andWhere(['low_v' => 1])
            ->orderBy(['data_timestamp' => SORT_DESC])
            ->all();
        
        if ($lowVoltageData) {
            $alerts[] = [
                'type' => 'low_voltage',
                'count' => count($lowVoltageData),
                'last_occurrence' => $lowVoltageData[0]->data_timestamp,
                'severity' => 'warning'
            ];
        }
        
        // Check for GPS loss
        $gpsLossData = TelemetryData::find()
            ->where(['device_id' => $device_id])
            ->andWhere(['>=', 'data_timestamp', $startTime])
            ->andWhere(['gps_fixed' => 0])
            ->andWhere(['IS NOT', 'lat', null])
            ->orderBy(['data_timestamp' => SORT_DESC])
            ->all();
        
        if ($gpsLossData) {
            $alerts[] = [
                'type' => 'gps_loss',
                'count' => count($gpsLossData),
                'last_occurrence' => $gpsLossData[0]->data_timestamp,
                'severity' => 'info'
            ];
        }
        
        return $alerts;
    }
}


//============== examples ==============
// 1. Get latest telemetry for a device
// $latest = TelemetryData::getLatestByDevice('DEVICE001');

// // 2. Get telemetry within date range
// $telemetry = TelemetryData::getByTimeRange('DEVICE001', '2024-01-01', '2024-01-31');

// // 3. Get power statistics
// $stats = TelemetryData::getPowerStatistics('DEVICE001', '2024-01-01', '2024-01-31');

// // 4. Get daily energy summary
// $dailyEnergy = TelemetryData::getDailyEnergySummary('DEVICE001', 7);

// // 5. Check if device is assigned to user
// $isAssigned = $model->isAssignedToUser(Yii::$app->user->id);

// // 6. Get location history
// $locations = TelemetryData::getLocationHistory('DEVICE001', 50);

// // 7. Search telemetry
// $query = TelemetryData::search('DEVICE001', '2024-01-01', null, 'solar');
// $results = $query->all();

// // 8. Using helper functions
// $deviceStatus = TelemetryHelper::getDeviceStatus('DEVICE001');
// $energyReport = TelemetryHelper::getEnergyReport('DEVICE001', 'day');
// $alerts = TelemetryHelper::getDeviceAlerts('DEVICE001', 24);