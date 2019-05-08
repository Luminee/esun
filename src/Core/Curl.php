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
        $response = $this->curl('GET', $url, $data);
        return json_decode($response);
    }

    public function curlPost($url, $data, $xnd = false)
    {
        $response = $this->curl('POST', $url, $data, $xnd);
        return json_decode($response);
    }

    public function curlPut($url, $data)
    {
        $response = $this->curl('POST|PUT', $url, $data);
        return json_decode($response);
    }

    public function curlDelete($url, $data)
    {
        $response = $this->curl('POST|DELETE', $url, $data);
        return json_decode($response);
    }

    /**
     * @param $method
     * @param $url
     * @param $data
     * @throws \Exception
     * @return bool|string
     */
    private function curl($method, $url, $data, $xnd = false)
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
        if ($method == 'GET' || $method == 'POST') return [$method, null];
        if (strstr($method, '|')) return explode('|', $method);
    }
}