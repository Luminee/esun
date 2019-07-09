<?php

namespace Luminee\Esun\Query;

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
    public $index;

    /**
     * @var string $type
     */
    public $type;

    /**
     * Connector constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->setHost($config['host']);
        $this->setPort($config['port']);
        $this->setIndex($config['index']);
        $this->setType($config['type']);
    }

    /**
     * @param array $config
     * @return Connector
     */
    public static function init(array $config)
    {
        return new self($config);
    }

    /**
     * @param $table
     * @return string
     */
    public function getUri($table)
    {
        $uri = $this->host . ':' . $this->port . '/';
        if (empty($this->index)) {
            return $uri . $table . '/' . $this->type;
        } else {
            return $uri . $this->index . '/' . $table;
        }
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

    protected function setType($type)
    {
        $this->type = trim($type, '/');
    }

}