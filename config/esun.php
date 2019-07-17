<?php
return [

    /**
     * ES Default
     */
    'default' => env('ES_CONNECTION', 'default'),

    /**
     * ES Connections
     */
    'connections' => [

        'default' => [

            'hosts' => env('ES_HOSTS', 'http://127.0.0.1:9200'),

            'port' => env('ES_PORT', 9200),

            'index' => env('ES_STORE'),

            'type' => env('ES_TYPE', '_doc'),

            'table_key' => 'index'
        ],

    ],

    /**
     * ES Connection Pool
     */
    'connection_pool' => Elasticsearch\ConnectionPool\StaticNoPingConnectionPool::class,

    /**
     * ES Selector
     */
    'selector' => Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector::class,


];