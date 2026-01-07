<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\AccessControl;
use app\models\User;
use app\models\TelemetryData;
use app\models\UserDevices;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

class TelemetryDeviceController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Remove CSRF validation for API requests
        unset($behaviors['authenticator']);

        // Add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => ['X-Pagination-Total-Count', 'X-Pagination-Page-Count', 'X-Pagination-Current-Page', 'X-Pagination-Per-Page'],
            ],
        ];

        // Add JWT authentication for all actions
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'except' => ['options'],
        ];

        // Access control
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                ],
            ],
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // Set JSON response format for all API endpoints
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        return parent::beforeAction($action);
    }

    /**
     * Handle OPTIONS request for CORS preflight
     */
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return [];
    }

    /**
     * Get current authenticated user ID
     */
    private function getCurrentUserId()
    {
        return Yii::$app->user->identity->user_id;
    }

    /**
     * Get current user's role/type
     */
    private function getCurrentUserType()
    {
        return Yii::$app->user->identity->user_tipe;
    }

    /**
     * Check if user is admin
     */
    private function isAdmin()
    {
        return $this->getCurrentUserType() === 'ADMIN';
    }

    /**
     * Verify device ownership or admin access
     */
    private function verifyDeviceAccess($device_id, $checkOwnership = true)
    {
        $user_id = $this->getCurrentUserId();
        
        // Admin can access all devices
        if ($this->isAdmin()) {
            return true;
        }
        
        // For non-admin users, check ownership
        if ($checkOwnership) {
            $userDevice = UserDevices::findOne([
                'user_id' => $user_id,
                'device_id' => $device_id,
                'is_active' => 1
            ]);
            
            return $userDevice !== null;
        }
        
        return false;
    }

    /**
     * GET /telemetry-device - List all devices with pagination
     * Admin: all devices, User: only their devices
     */
    public function actionIndex()
    {
        $user_id = $this->getCurrentUserId();
        
        $query = UserDevices::find();
        
        // Non-admin users can only see their own devices
        if (!$this->isAdmin()) {
            $query->where(['user_id' => $user_id]);
        }
        
        // Apply filters from query parameters
        $request = Yii::$app->request;
        $filters = $request->get();
        
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->andWhere(['is_active' => (int)$filters['is_active']]);
        }
        
        if (isset($filters['device_id']) && !empty($filters['device_id'])) {
            $query->andWhere(['like', 'device_id', $filters['device_id']]);
        }
        
        if (isset($filters['device_alias']) && !empty($filters['device_alias'])) {
            $query->andWhere(['like', 'device_alias', $filters['device_alias']]);
        }
        
        if (isset($filters['user_id']) && !empty($filters['user_id']) && $this->isAdmin()) {
            $query->andWhere(['user_id' => $filters['user_id']]);
        }
        
        // Create data provider with pagination
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $request->get('per_page', 20),
                'page' => $request->get('page', 1) - 1,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes' => ['id', 'device_id', 'device_alias', 'created_at', 'updated_at'],
            ],
        ]);
        
        // Format response
        $devices = [];
        foreach ($dataProvider->getModels() as $device) {
            // Get user info for admin
            $userInfo = null;
            if ($this->isAdmin()) {
                $user = User::findOne($device->user_id);
                $userInfo = $user ? [
                    'user_id' => $user->user_id,
                    'user_name' => $user->user_name,
                    'user_nama' => $user->user_nama,
                    'user_email' => $user->user_email,
                ] : null;
            }
            
            // Get latest telemetry stats
            $latestTelemetry = TelemetryData::find()
                ->where(['device_id' => $device->device_id])
                ->orderBy(['data_timestamp' => SORT_DESC])
                ->one();
            
            $devices[] = [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_alias' => $device->device_alias,
                'device_description' => $device->device_description,
                'is_active' => (bool)$device->is_active,
                'created_at' => $device->created_at,
                'updated_at' => $device->updated_at,
                'user' => $userInfo,
                'latest_telemetry' => $latestTelemetry ? [
                    'timestamp' => $latestTelemetry->data_timestamp,
                    'ac_power' => $latestTelemetry->ac_p,
                    'energy' => $latestTelemetry->energy,
                    'relay_state' => (bool)$latestTelemetry->relay_state,
                    'has_gps' => !empty($latestTelemetry->lat) && !empty($latestTelemetry->lng),
                ] : null,
            ];
        }
        
        $pagination = $dataProvider->getPagination();
        
        // Add pagination headers
        Yii::$app->response->headers->set('X-Pagination-Total-Count', $pagination->totalCount);
        Yii::$app->response->headers->set('X-Pagination-Page-Count', $pagination->getPageCount());
        Yii::$app->response->headers->set('X-Pagination-Current-Page', $pagination->getPage() + 1);
        Yii::$app->response->headers->set('X-Pagination-Per-Page', $pagination->pageSize);
        
        return [
            'success' => true,
            'data' => $devices,
            'pagination' => [
                'total' => $pagination->totalCount,
                'page' => $pagination->getPage() + 1,
                'per_page' => $pagination->pageSize,
                'page_count' => $pagination->getPageCount(),
            ],
        ];
    }

    /**
     * GET /telemetry-device/{id} - Get device by ID
     */
    public function actionView($id)
    {
        $device = UserDevices::findOne($id);
        
        if (!$device) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Device not found'
            ];
        }
        
        // Check access
        if (!$this->verifyDeviceAccess($device->device_id)) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Get user info for admin
        $userInfo = null;
        if ($this->isAdmin()) {
            $user = User::findOne($device->user_id);
            $userInfo = $user ? [
                'user_id' => $user->user_id,
                'user_name' => $user->user_name,
                'user_nama' => $user->user_nama,
                'user_email' => $user->user_email,
                'user_tipe' => $user->user_tipe,
            ] : null;
        }
        
        // Get telemetry statistics
        $telemetryStats = TelemetryData::find()
            ->select([
                'COUNT(*) as total_records',
                'MAX(data_timestamp) as last_record',
                'MIN(data_timestamp) as first_record',
                'AVG(ac_p) as avg_power',
                'SUM(energy) as total_energy',
            ])
            ->where(['device_id' => $device->device_id])
            ->asArray()
            ->one();
        
        // Get latest telemetry
        $latestTelemetry = TelemetryData::find()
            ->where(['device_id' => $device->device_id])
            ->orderBy(['data_timestamp' => SORT_DESC])
            ->one();
        
        $deviceData = [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'device_alias' => $device->device_alias,
            'device_description' => $device->device_description,
            'is_active' => (bool)$device->is_active,
            'created_at' => $device->created_at,
            'updated_at' => $device->updated_at,
            'created_by' => $device->created_by,
            'modified_by' => $device->modified_by,
            'user' => $userInfo,
            'statistics' => $telemetryStats,
            'latest_telemetry' => $latestTelemetry ? [
                'id' => $latestTelemetry->id,
                'timestamp' => $latestTelemetry->data_timestamp,
                'ac_voltage' => $latestTelemetry->ac_v,
                'ac_current' => $latestTelemetry->ac_i,
                'ac_power' => $latestTelemetry->ac_p,
                'energy' => $latestTelemetry->energy,
                'frequency' => $latestTelemetry->freq,
                'power_factor' => $latestTelemetry->pf,
                'relay_state' => (bool)$latestTelemetry->relay_state,
                'latitude' => $latestTelemetry->lat,
                'longitude' => $latestTelemetry->lng,
                'gps_fixed' => (bool)$latestTelemetry->gps_fixed,
            ] : null,
        ];
        
        return [
            'success' => true,
            'data' => $deviceData
        ];
    }

    /**
     * POST /telemetry-device - Create new device
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $user_id = $this->getCurrentUserId();
        
        $device = new UserDevices();
        $device->load($request->post(), '');
        
        // Set user_id to current user if not admin or not specified
        if (!$this->isAdmin() || empty($device->user_id)) {
            $device->user_id = $user_id;
        }
        
        // Validate that user exists if admin is assigning to other user
        if ($this->isAdmin() && $device->user_id) {
            $user = User::findOne($device->user_id);
            if (!$user) {
                Yii::$app->response->statusCode = 400;
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
        }
        
        // Check if device already assigned to this user
        $existingDevice = UserDevices::findOne([
            'user_id' => $device->user_id,
            'device_id' => $device->device_id
        ]);
        
        if ($existingDevice) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'Device already assigned to this user'
            ];
        }
        
        // Set created_by
        $device->created_by = $user_id;
        
        if ($device->save()) {
            Yii::$app->response->statusCode = 201; // Created
            return [
                'success' => true,
                'message' => 'Device created successfully',
                'data' => [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'device_alias' => $device->device_alias,
                    'user_id' => $device->user_id,
                    'is_active' => (bool)$device->is_active,
                    'created_at' => $device->created_at,
                ]
            ];
        } else {
            Yii::$app->response->statusCode = 422; // Unprocessable Entity
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $device->errors
            ];
        }
    }

    /**
     * PUT /telemetry-device/{id} - Update device
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $user_id = $this->getCurrentUserId();
        
        $device = UserDevices::findOne($id);
        
        if (!$device) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Device not found'
            ];
        }
        
        // Check access
        if (!$this->verifyDeviceAccess($device->device_id)) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Non-admin users cannot change user_id
        $data = $request->post();
        if (!$this->isAdmin() && isset($data['user_id'])) {
            unset($data['user_id']);
        }
        
        $device->load($data, '');
        $device->modified_by = $user_id;
        
        if ($device->save()) {
            return [
                'success' => true,
                'message' => 'Device updated successfully',
                'data' => [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'device_alias' => $device->device_alias,
                    'device_description' => $device->device_description,
                    'is_active' => (bool)$device->is_active,
                    'updated_at' => $device->updated_at,
                ]
            ];
        } else {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'message' => 'Update failed',
                'errors' => $device->errors
            ];
        }
    }

    /**
     * DELETE /telemetry-device/{id} - Delete device (soft delete by setting is_active = 0)
     */
    public function actionDelete($id)
    {
        $device = UserDevices::findOne($id);
        
        if (!$device) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Device not found'
            ];
        }
        
        // Check access
        if (!$this->verifyDeviceAccess($device->device_id)) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Soft delete by setting is_active = 0
        $device->is_active = 0;
        $device->modified_by = $this->getCurrentUserId();
        
        if ($device->save()) {
            return [
                'success' => true,
                'message' => 'Device deactivated successfully'
            ];
        } else {
            Yii::$app->response->statusCode = 500;
            return [
                'success' => false,
                'message' => 'Failed to deactivate device'
            ];
        }
    }

    /**
     * PATCH /telemetry-device/{id}/activate - Activate device
     */
    public function actionActivate($id)
    {
        $device = UserDevices::findOne($id);
        
        if (!$device) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Device not found'
            ];
        }
        
        // Check access
        if (!$this->verifyDeviceAccess($device->device_id)) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        $device->is_active = 1;
        $device->modified_by = $this->getCurrentUserId();
        
        if ($device->save()) {
            return [
                'success' => true,
                'message' => 'Device activated successfully'
            ];
        } else {
            Yii::$app->response->statusCode = 500;
            return [
                'success' => false,
                'message' => 'Failed to activate device'
            ];
        }
    }

    /**
     * GET /telemetry-device/{id}/telemetry - Get telemetry data for specific device
     */
    public function actionTelemetry($id)
    {
        $device = UserDevices::findOne($id);
        
        if (!$device) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Device not found'
            ];
        }
        
        // Check access
        if (!$this->verifyDeviceAccess($device->device_id)) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        $request = Yii::$app->request;
        
        // Get query parameters for filtering
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $limit = $request->get('limit', 100);
        $offset = $request->get('offset', 0);
        $order = $request->get('order', 'DESC');
        
        $query = TelemetryData::find()
            ->where(['device_id' => $device->device_id]);
        
        // Apply date filters if provided
        if ($startDate) {
            $query->andWhere(['>=', 'data_timestamp', $startDate]);
        }
        
        if ($endDate) {
            $query->andWhere(['<=', 'data_timestamp', $endDate]);
        }
        
        // Apply pagination and ordering
        $totalCount = $query->count();
        $telemetryData = $query
            ->orderBy(['data_timestamp' => $order === 'ASC' ? SORT_ASC : SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();
        
        $formattedData = [];
        foreach ($telemetryData as $data) {
            $formattedData[] = [
                'id' => $data->id,
                'timestamp' => $data->data_timestamp,
                'ac_voltage' => $data->ac_v,
                'ac_current' => $data->ac_i,
                'ac_power' => $data->ac_p,
                'energy' => $data->energy,
                'frequency' => $data->freq,
                'power_factor' => $data->pf,
                'dc_voltage' => $data->dc_v,
                'dc_current' => $data->dc_i,
                'low_voltage_warning' => (bool)$data->low_v,
                'gps_fixed' => (bool)$data->gps_fixed,
                'latitude' => $data->lat,
                'longitude' => $data->lng,
                'relay_state' => (bool)$data->relay_state,
                'device_name' => $data->device_name,
                'sync_type' => $data->sync_type,
                'data_type' => $data->data_type,
                'created_at' => $data->created_at
            ];
        }
        
        return [
            'success' => true,
            'device' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'alias' => $device->device_alias,
            ],
            'data' => $formattedData,
            'pagination' => [
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ];
    }

    /**
     * GET /telemetry-device/{id}/stats - Get device statistics
     */
    public function actionStats($id)
    {
        $device = UserDevices::findOne($id);
        
        if (!$device) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Device not found'
            ];
        }
        
        // Check access
        if (!$this->verifyDeviceAccess($device->device_id)) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        $request = Yii::$app->request;
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $query = TelemetryData::find()
            ->where(['device_id' => $device->device_id]);
        
        // Apply date filters if provided
        if ($startDate) {
            $query->andWhere(['>=', 'data_timestamp', $startDate]);
        }
        
        if ($endDate) {
            $query->andWhere(['<=', 'data_timestamp', $endDate]);
        }
        
        // Get basic statistics
        $stats = $query
            ->select([
                'COUNT(*) as total_records',
                'MAX(data_timestamp) as last_record',
                'MIN(data_timestamp) as first_record',
                'AVG(ac_v) as avg_voltage',
                'AVG(ac_i) as avg_current',
                'AVG(ac_p) as avg_power',
                'MAX(ac_p) as max_power',
                'MIN(ac_p) as min_power',
                'SUM(energy) as total_energy',
                'AVG(freq) as avg_frequency',
                'AVG(pf) as avg_power_factor',
                'SUM(CASE WHEN low_v = 1 THEN 1 ELSE 0 END) as low_voltage_count',
                'SUM(CASE WHEN relay_state = 1 THEN 1 ELSE 0 END) as relay_on_count',
            ])
            ->asArray()
            ->one();
        
        // Get hourly averages for the last 24 hours
        $hourlyData = TelemetryData::find()
            ->select([
                "DATE_FORMAT(data_timestamp, '%Y-%m-%d %H:00:00') as hour",
                'AVG(ac_p) as avg_power',
                'AVG(ac_v) as avg_voltage',
                'AVG(energy) as avg_energy',
                'COUNT(*) as record_count'
            ])
            ->where(['device_id' => $device->device_id])
            ->andWhere(['>=', 'data_timestamp', date('Y-m-d H:i:s', strtotime('-24 hours'))])
            ->groupBy(["DATE_FORMAT(data_timestamp, '%Y-%m-%d %H:00:00')"])
            ->orderBy(['hour' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Get latest location
        $latestLocation = TelemetryData::find()
            ->select(['lat', 'lng', 'data_timestamp', 'gps_fixed'])
            ->where(['device_id' => $device->device_id])
            ->andWhere(['IS NOT', 'lat', null])
            ->andWhere(['IS NOT', 'lng', null])
            ->orderBy(['data_timestamp' => SORT_DESC])
            ->one();
        
        return [
            'success' => true,
            'device' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'alias' => $device->device_alias,
            ],
            'statistics' => $stats,
            'hourly_data' => $hourlyData,
            'latest_location' => $latestLocation ? [
                'latitude' => $latestLocation->lat,
                'longitude' => $latestLocation->lng,
                'timestamp' => $latestLocation->data_timestamp,
                'gps_fixed' => (bool)$latestLocation->gps_fixed,
            ] : null,
        ];
    }

    /**
     * GET /telemetry-device/search - Search devices
     */
    public function actionSearch()
    {
        $request = Yii::$app->request;
        $user_id = $this->getCurrentUserId();
        
        $searchTerm = $request->get('q');
        $limit = $request->get('limit', 10);
        
        if (empty($searchTerm)) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'Search term is required'
            ];
        }
        
        $query = UserDevices::find();
        
        // Non-admin users can only search their own devices
        if (!$this->isAdmin()) {
            $query->where(['user_id' => $user_id]);
        }
        
        $query->andWhere(['or',
            ['like', 'device_id', $searchTerm],
            ['like', 'device_alias', $searchTerm],
            ['like', 'device_description', $searchTerm],
        ]);
        
        $devices = $query
            ->limit($limit)
            ->all();
        
        $results = [];
        foreach ($devices as $device) {
            $results[] = [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_alias' => $device->device_alias,
                'device_description' => $device->device_description,
                'user_id' => $device->user_id,
            ];
        }
        
        return [
            'success' => true,
            'data' => $results,
            'count' => count($results)
        ];
    }

    /**
     * GET /telemetry-device/user/{user_id} - Get devices by user ID (Admin only)
     */
    public function actionByUser($user_id)
    {
        if (!$this->isAdmin()) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Admin access required'
            ];
        }
        
        $user = User::findOne($user_id);
        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        $devices = UserDevices::find()
            ->where(['user_id' => $user_id])
            ->all();
        
        $results = [];
        foreach ($devices as $device) {
            $results[] = [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_alias' => $device->device_alias,
                'device_description' => $device->device_description,
                'is_active' => (bool)$device->is_active,
                'created_at' => $device->created_at,
                'updated_at' => $device->updated_at,
            ];
        }
        
        return [
            'success' => true,
            'user' => [
                'id' => $user->user_id,
                'name' => $user->user_nama,
                'username' => $user->user_name,
                'email' => $user->user_email,
            ],
            'devices' => $results,
            'count' => count($results)
        ];
    }

    /**
     * POST /telemetry-device/bulk-assign - Bulk assign devices to user (Admin only)
     */
    public function actionBulkAssign()
    {
        if (!$this->isAdmin()) {
            Yii::$app->response->statusCode = 403;
            return [
                'success' => false,
                'message' => 'Admin access required'
            ];
        }
        
        $request = Yii::$app->request;
        $user_id = $request->post('user_id');
        $device_ids = $request->post('device_ids', []);
        
        if (empty($user_id) || empty($device_ids)) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'User ID and device IDs are required'
            ];
        }
        
        $user = User::findOne($user_id);
        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        $results = [
            'assigned' => 0,
            'failed' => 0,
            'already_assigned' => 0,
            'details' => []
        ];
        
        foreach ($device_ids as $device_id) {
            // Check if device already assigned to this user
            $existing = UserDevices::findOne([
                'user_id' => $user_id,
                'device_id' => $device_id
            ]);
            
            if ($existing) {
                $results['already_assigned']++;
                $results['details'][] = [
                    'device_id' => $device_id,
                    'status' => 'already_assigned',
                    'message' => 'Device already assigned to user'
                ];
                continue;
            }
            
            // Check if device exists in telemetry data
            $telemetryExists = TelemetryData::find()
                ->where(['device_id' => $device_id])
                ->exists();
            
            if (!$telemetryExists) {
                $results['details'][] = [
                    'device_id' => $device_id,
                    'status' => 'failed',
                    'message' => 'Device not found in telemetry data'
                ];
                $results['failed']++;
                continue;
            }
            
            // Create new assignment
            $device = new UserDevices();
            $device->user_id = $user_id;
            $device->device_id = $device_id;
            $device->device_alias = "Device " . $device_id;
            $device->created_by = $this->getCurrentUserId();
            
            if ($device->save()) {
                $results['assigned']++;
                $results['details'][] = [
                    'device_id' => $device_id,
                    'status' => 'success',
                    'message' => 'Device assigned successfully',
                    'assignment_id' => $device->id
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'device_id' => $device_id,
                    'status' => 'failed',
                    'message' => 'Failed to assign device',
                    'errors' => $device->errors
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => 'Bulk assignment completed',
            'results' => $results
        ];
    }
}