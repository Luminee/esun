<?php

namespace Luminee\Esun;

use Luminee\Esun\Core\Config;
use Luminee\Esun\Core\Processor;
use Luminee\Esun\Query\Builder;
use Luminee\Esun\Query\Connector;

class Esun
{
    protected $processor;
    protected $connector;

    public function __construct()
    {
        $this->processor = Processor::init();
        $this->connector = Connector::init(Config::getConfig());
    }

    public function table($table)
    {
        return $this->query()->table($table);
    }

    protected function query()
    {
        return new Builder($this->processor, $this->connector);
    }


}