<?php

namespace Luminee\Esun;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Luminee\Esun\Query\Builder;
use Luminee\Esun\Query\Grammar;

class Esun
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $connection;

    protected $grammar;

    public function __construct()
    {
        $this->config = config('esun');
        $this->connection = $this->config['connections'][$this->config['default']];
        $this->client = $this->clientBuilder();
        $this->grammar = new Grammar();
    }

    public function table($table)
    {
        return $this->query()->table($table);
    }

    public static function newBuilder()
    {
        return (new static())->query();
    }

    protected function query()
    {
        return new Builder($this->connection, $this->grammar, $this->client);
    }

    /**
     * @return Client
     */
    protected function clientBuilder(): Client
    {
        $clientBuilder = ClientBuilder::create();

        $clientBuilder
            ->setConnectionPool($this->config['connection_pool'])
            ->setSelector($this->config['selector'])
            ->setHosts(explode(',', $this->connection['hosts']));

        return $clientBuilder->build();
    }


}