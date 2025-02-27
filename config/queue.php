<?php


return [
    'default' => 'redis',
    'connections' => [
        'sync' => [
            'type' => 'sync',
        ],
        'database' => [
            'type' => 'database',
            'queue' => 'default',
            'table' => 'jobs',
        ],
        'redis' => [
            'type'          => 'redis',
            'queue'         => env('QUEUE_LISTEN_NAME', 'CRMEB_PRO'),
            'batch_queue'   => env('QUEUE_BATCH_LISTEN_NAME', 'CRMEB_PRO_BATCH'),
            'host'          => env('REDIS_HOSTNAME', '127.0.0.1'),
            'port'          => env('REDIS_PORT', 6379),
            'password'      => env('REDIS_PASSWORD', ''),
            'select'        => env('REDIS_SELECT', 0),
            'timeout'       => 0,
            'persistent'    => true,
        ],
    ],
    'failed' => [
        'type' => 'none',
        'table' => 'failed_jobs',
    ],
];
