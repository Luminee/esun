<?php

namespace Luminee\Esun\Core;

class Response
{
    /**
     * @var null | string
     */
    protected $result = null;

    /**
     * @var null | string
     */
    protected $_id = null;

    public function __construct()
    {
    }

    public static function find($response, $flag = '_source')
    {
        return self::init($response, $flag)->getResult();
    }

    public static function get($response, $flag = 'hits')
    {
        return self::init($response, $flag)->getResult();
    }

    /**
     * @param $response
     * @return string|null
     */
    public static function create($response)
    {
        return self::init($response)->getResult();
    }

    /**
     * @param $response
     * @return string|null
     */
    public static function insert($response)
    {
        return self::init($response)->getId();
    }

    /**
     * @param $response
     * @return bool|null
     */
    public static function update($response)
    {
        return self::init($response)->judge('updated');
    }

    /**
     * @param $response
     * @return bool|null
     */
    public static function delete($response)
    {
        return self::init($response)->judge('deleted');
    }

    // Protected

    protected static function init($response, $flag = 'result')
    {
        $self = new self();
        switch ($flag) {
            case 'result':
                $self->result = $response->result;
                $self->_id = $response->_id;
                return $self;
            case '_source':
                $self->result = $response->_source;
                $self->result->_id = $response->_id;
                return $self;
            case 'hits':
                $self->result = $response->hits;
                return $self;
            default:
                $self->result = $response;
                return $self;
        }
    }

    protected function judge($flag)
    {
        if (is_null($this->result)) return null;
        return $this->result == $flag ? true : false;
    }

    protected function getHits($hits)
    {
        return $hits;
    }

    protected function getResult()
    {
        return $this->result;
    }

    protected function getId()
    {
        return $this->_id;
    }

}