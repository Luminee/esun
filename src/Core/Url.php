<?php

namespace Luminee\Esun\Core;

class Url
{
    /**
     * @var string $uri
     */
    protected $uri;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @param $uri
     * @param $id
     * @return string
     */
    public static function createUrl($uri, $id)
    {
        return self::init($uri)->append($id)->getUri();
    }

    /**
     * @param $uri
     * @return string
     */
    public static function insertUrl($uri)
    {
        return self::init($uri)->getUri();
    }

    /**
     * @param $uri
     * @param $id
     * @return string
     */
    public static function updateUrl($uri, $id)
    {
        return self::init($uri)->append($id)->append('_update')->getUri();
    }

    /**
     * @param $uri
     * @param $id
     * @return string
     */
    public static function deleteUrl($uri, $id)
    {
        return self::init($uri)->append($id)->getUri();
    }

    /**
     * @param $uri
     * @return string
     */
    public static function bulkUrl($uri)
    {
        return self::init($uri)->append('_bulk')->getUri();
    }

    /**
     * @param $uri
     * @return string
     */
    public static function searchUrl($uri)
    {
        return self::init($uri)->append('_search')->getUri();
    }

    // Protected

    protected static function init($uri)
    {
        return new self($uri);
    }


    protected function append($string)
    {
        $this->uri .= '/' . $string;
        return $this;
    }

    protected function getUri()
    {
        return $this->uri;
    }

}