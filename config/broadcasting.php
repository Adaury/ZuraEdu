<?php

return [

    'default' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [

        'reverb' => [
            'driver'  => 'reverb',
            'key'     => env('REVERB_APP_KEY'),
            'secret'  => env('REVERB_APP_SECRET'),
            'app_id'  => env('REVERB_APP_ID'),
            'options' => [
                'host'   => env('REVERB_HOST', 'localhost'),
                'port'   => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key'    => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster'   => env('PUSHER_APP_CLUSTER'),
                'encrypted' => true,
                'useTLS'    => true,
            ],
        ],

        'redis' => [
            'driver'     => 'redis',
            'connection' => 'default',
        ],

        'log'  => ['driver' => 'log'],
        'null' => ['driver' => 'null'],
    ],

];
