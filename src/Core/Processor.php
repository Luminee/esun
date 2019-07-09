<?php

namespace Luminee\Esun\Core;

class Processor
{
    public static $instance;

    protected $curl;

    private function __construct()
    {
        $this->curl = Curl::init();
    }

    public static function init()
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    protected function handleCurl($method, $url, $data, $xnd = false)
    {
        try {
            return $this->curl->curl($method, $url . $data, $xnd);
        } catch (\Exception $e) {
            \Log::error($e);
            return response(500);
        }
    }

    public function get($url, $data)
    {
        return json_decode($this->handleCurl('GET', $url, $data));
    }

    public function post($url, $data, $xnd = false)
    {
        return json_decode($this->handleCurl('POST', $url, $data, $xnd));
    }

    public function put($url, $data)
    {
        return json_decode($this->handleCurl('POST|PUT', $url, $data));
    }

    public function delete($url, $data)
    {
        return json_decode($this->handleCurl('POST|DELETE', $url, $data));
    }
}