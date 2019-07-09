<?php

return [

    'default' => env('ES_CONNECTION', 'default'),

    'connections' => [

        'default' => [

            'host' => env('ES_HOST', '127.0.0.1'),

            'port' => env('ES_PORT', 9200),

            'index' => env('ES_STORE'),

            'type' => env('ES_TYPE', '_doc'),
        ],

    ],

];