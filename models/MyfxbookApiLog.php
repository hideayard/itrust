<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "myfxbook_api_logs".
 *
 * @property int $id
 * @property string $endpoint
 * @property string $method
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $request_data
 * @property int|null $response_status
 * @property float|null $response_time
 * @property string $created_at
 */
class MyfxbookApiLog extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'myfxbook_api_logs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['endpoint', 'method'], 'required'],
            [['request_data', 'user_agent'], 'string'],
            [['response_status'], 'integer'],
            [['response_time'], 'number'],
            [['created_at'], 'safe'],
            [['endpoint'], 'string', 'max' => 100],
            [['method'], 'string', 'max' => 10],
            [['ip_address'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'endpoint' => 'Endpoint',
            'method' => 'Method',
            'ip_address' => 'Ip Address',
            'user_agent' => 'User Agent',
            'request_data' => 'Request Data',
            'response_status' => 'Response Status',
            'response_time' => 'Response Time',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Log API request
     *
     * @param string $endpoint
     * @param string $method
     * @param array $requestData
     * @param int $responseStatus
     * @param float $responseTime
     * @return bool
     */
    public static function logRequest($endpoint, $method, $requestData = [], $responseStatus = null, $responseTime = null)
    {
        $log = new self();
        $log->endpoint = $endpoint;
        $log->method = $method;
        $log->ip_address = Yii::$app->request->getUserIP();
        $log->user_agent = Yii::$app->request->getUserAgent();
        $log->request_data = !empty($requestData) ? json_encode($requestData) : null;
        $log->response_status = $responseStatus;
        $log->response_time = $responseTime;
        
        return $log->save();
    }
}