<?php

namespace Luminee\Esun\Core;

class Config
{
    public static function getConfig()
    {
        if (function_exists('config')) {
            $conn = config('esun.default');
            return config('esun.connections.' . $conn);
        } else {
            return self::loadConfig();
        }

    }

    protected static function loadConfig()
    {
        $dir = explode('vendor', __DIR__)[0];
        $config = require realpath($dir . 'config/esun.php');
        $conn = $config['default'];
        return $config['connections'][$conn];
    }
}