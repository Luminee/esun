<?php

namespace Luminee\Esun\Core;

class Connector
{
    /**
     * @var string $host
     */
    protected $host;

    /**
     * @var int $port
     */
    protected $port;

    /**
     * @var string $index
     */
    protected $index;

    /**
     * Connector constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->setHost($config['host']);
        $this->setPort($config['port']);
        $this->setIndex($config['index']);
    }

    public static function init(array $config)
    {
        return new self($config);
    }

    public function getUri()
    {
        return $this->host . ':' . $this->port . '/' . $this->index;
    }

    protected function setHost($host)
    {
        $this->host = rtrim($host, '/');
    }

    protected function setPort($port)
    {
        $this->port = trim($port);
    }

    protected function setIndex($index)
    {
        $this->index = trim($index, '/');
    }

}