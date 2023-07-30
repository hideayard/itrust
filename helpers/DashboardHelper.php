<?php

namespace app\helpers;

use Yii;
use app\helpers\CustomHelper;

class DashboardHelper
{

    public static function pressurePerDay(string $device, string $startDate, string $endDate): array
    {
        return Yii::$app->mongodb->createCommand()->aggregate('statistic', [
            [
                '$match' => [
                    'remark' => $device,
                    'key' => 'data_sensors',
                    'date' => [
                        '$gte' => CustomHelper::toISODate($startDate),
                        '$lt' => CustomHelper::toISODate($endDate)
                    ]
                ],

            ],
            [
                '$sort' => [
                    'date' => 1
                ],
            ],
            [
                '$project' => [
                    'value' => true,
                    'date' => [
                        '$dateToString' => ['format' => "%d/%m/%Y", 'date' => '$date']
                    ],
                ]
            ],
            [
                '$project' => [
                    'tgl' => '$date',
                    '_id' => false,
                    'volume' => '$value.volume'
                ]
            ],
        ]);
    }

    public static function totalMilkPerDay(string $startDate, string $endDate): array
    {
        return Yii::$app->mongodb->createCommand()->aggregate('statistic', [
            [
                '$match' => [
                    'key' => 'total_milk_production',
                    'date' => [
                        '$gte' => CustomHelper::toISODate($startDate),
                        '$lt' => CustomHelper::toISODate($endDate)
                    ]
                ],

            ],
            [
                '$sort' => [
                    'date' => 1
                ],
            ],
            [
                '$project' => [
                    'value' => true,
                    'date' => [
                        '$dateToString' => ['format' => "%d/%m/%Y", 'date' => '$date']
                    ],
                ]
            ],
            [
                '$project' => [
                    'tgl' => '$date',
                    '_id' => false,
                    'volume' => '$value.volume'
                ]
            ],
        ]);
    }

    public static function populationByGender(string $tenantId)
    {
        $rows = Yii::$app->mongodb->createCommand()->aggregate('statistic', [
            [
                '$match' => [
                    'tenant_id' => intval($tenantId),
                    'key' => 'population'
                ],

            ],
            [
                '$sort' => [
                    'date' => -1
                ],
            ],
            [
                '$project' => [
                    'male' => '$value.male',
                    'female' => '$value.female'
                ]
            ],
            [
                '$limit' => 1
            ]
        ]);

        return (!empty($rows)) ? reset($rows) : null;
    }

    public static function totalPopulationByGender()
    {
        $rows = Yii::$app->mongodb->createCommand()->aggregate('statistic', [
            [
                '$match' => [
                    'key' => 'total_population'
                ],

            ],
            [
                '$sort' => [
                    'date' => -1
                ],
            ],
            [
                '$project' => [
                    'male' => '$value.male',
                    'female' => '$value.female'
                ]
            ],
            [
                '$limit' => 1
            ]
        ]);

        return (!empty($rows)) ? reset($rows) : null;
    }

    public static function milkVolume(string $tenantId, string $startDate, string $endDate)
    {
        $data = Yii::$app->mongodb->createCommand()->aggregate('statistic', [
            [
                '$match' => [
                    'tenant_id' => intval($tenantId),
                    'key' => 'milk_production',
                    'date' => [
                        '$gte' => CustomHelper::toISODate($startDate),
                        '$lt' => CustomHelper::toISODate($endDate)
                    ]
                ],

            ],
            [
                '$sort' => [
                    'date' => 1
                ],
            ],
            [
                '$project' => [
                    'value' => true,
                    'date' => [
                        '$dateToString' => ['format' => "%d/%m/%Y", 'date' => '$date']
                    ],
                ]
            ],
            [
                '$project' => [
                    'tgl' => '$date',
                    '_id' => false,
                    'volume' => '$value.volume'
                ]
            ],
            [
                '$group' => [
                    '_id' => null,
                    'volume' => [
                        '$sum' => '$volume'
                    ]
                ]
            ]
        ]);

        return (!empty($data)) ? $data[0]['volume'] : 0;
    }

    public static function totalMilkVolume(string $startDate, string $endDate)
    {
        $data = Yii::$app->mongodb->createCommand()->aggregate('statistic', [
            [
                '$match' => [
                    'key' => 'total_milk_production',
                    'date' => [
                        '$gte' => CustomHelper::toISODate($startDate),
                        '$lt' => CustomHelper::toISODate($endDate)
                    ]
                ],

            ],
            [
                '$sort' => [
                    'date' => 1
                ],
            ],
            [
                '$project' => [
                    'value' => true,
                    'date' => [
                        '$dateToString' => ['format' => "%d/%m/%Y", 'date' => '$date']
                    ],
                ]
            ],
            [
                '$project' => [
                    'tgl' => '$date',
                    '_id' => false,
                    'volume' => '$value.volume'
                ]
            ],
            [
                '$group' => [
                    '_id' => null,
                    'volume' => [
                        '$sum' => '$volume'
                    ]
                ]
            ]
        ]);

        return (!empty($data)) ? $data[0]['volume'] : 0;
    }

    public static function totalActivities()
    {
        $data = Yii::$app->mongodb->createCommand()->aggregate('statistic', [
            [
                '$match' => [
                    'tenant_id' => null,
                    'key' => 'total_activities'
                ],

            ],
            [
                '$sort' => [
                    'date' => -1
                ],
            ],
            [
                '$limit' => 1
            ],
            [
                '$project' => [
                    'total' => '$value.total'
                ]
            ]
        ]);

        return (!empty($data)) ? $data[0]['total'] : 0;
    }

    public static function landingPageVisits(string $tenantId, string $startDate, string $endDate)
    {
        return Yii::$app->mongodb->createCommand()->count('page_view', [
            'tenant_id' => intval($tenantId),
            'date' => [
                '$gte' => CustomHelper::toISODate($startDate),
                '$lt' => CustomHelper::toISODate($endDate)
            ],

        ]);
    }

    public static function totalLandingPageVisits(string $startDate, string $endDate)
    {
        return Yii::$app->mongodb->createCommand()->count('page_view', [
            'date' => [
                '$gte' => CustomHelper::toISODate($startDate),
                '$lt' => CustomHelper::toISODate($endDate)
            ],

        ]);
    }

    public static function dashboardLogin(string $tenantId, string $startDate, string $endDate)
    {
        return Yii::$app->mongodb->createCommand()->count('user_login', [
            'type' => 'dashboard',
            'tenant_id' => intval($tenantId),
            'date' => [
                '$gte' => CustomHelper::toISODate($startDate),
                '$lt' => CustomHelper::toISODate($endDate)
            ],
        ]);
    }

    public static function totalDashboardLogin(string $startDate, string $endDate)
    {
        return Yii::$app->mongodb->createCommand()->count('user_login', [
            'type' => 'dashboard',
            'date' => [
                '$gte' => CustomHelper::toISODate($startDate),
                '$lt' => CustomHelper::toISODate($endDate)
            ],
        ]);
    }

    public static function appLogin(string $tenantId, string $startDate, string $endDate)
    {
        return Yii::$app->mongodb->createCommand()->count('user_login', [
            'type' => 'app',
            'tenant_id' => intval($tenantId),
            'date' => [
                '$gte' => CustomHelper::toISODate($startDate),
                '$lt' => CustomHelper::toISODate($endDate)
            ],
        ]);
    }

    public static function totalAppLogin(string $startDate, string $endDate)
    {
        return Yii::$app->mongodb->createCommand()->count('user_login', [
            'type' => 'app',
            'date' => [
                '$gte' => CustomHelper::toISODate($startDate),
                '$lt' => CustomHelper::toISODate($endDate)
            ],
        ]);
    }
}
