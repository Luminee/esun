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

    public function __construct($response)
    {
        if (isset($response->result)) {
            $this->result = $response->result;
            $this->_id = $response->_id;
        }
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

    protected static function init($response)
    {
        return new self($response);
    }

    protected function judge($flag)
    {
        if (is_null($this->result)) return null;
        return $this->result == $flag ? true : false;
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