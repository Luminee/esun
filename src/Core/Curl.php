<?php

namespace Luminee\Esun\Core;

final class Curl
{
    public static $instance;

    private function __construct()
    {
    }

    public static function init()
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function curlGet($url, $data)
    {
        return $this->curl('GET', $url, $data);
    }

    public function curlPost($url, $data, $xnd = false)
    {
        return $this->curl('POST', $url, $data, $xnd);
    }

    /**
     * @param $method
     * @param $url
     * @param $data
     * @param $xnd = false
     * @throws \Exception
     * @return bool|string
     */
    public function curl($method, $url, $data, $xnd = false)
    {
        $ch = curl_init($url);
        list($method, $request) = $this->handleMethod($method);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!is_null($request)) curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);

        $type = $xnd ? 'application/x-ndjson' : 'application/json';
        $header = ['Content-Type: ' . $type, 'Content-Length: ' . strlen($data)];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        if ($result === FALSE) throw new \Exception(curl_error($ch));
        curl_close($ch);
        return $result;
    }

    private function handleMethod($method)
    {
        if (strstr($method, '|')) {
            return explode('|', $method);
        }
        return [$method, null];
    }
}