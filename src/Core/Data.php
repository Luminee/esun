<?php

namespace Luminee\Esun\Core;

class Data
{
    public static function toJson(array $data, $append_doc = false)
    {
        if (empty($data)) return '';
        if ($append_doc) $data = ['doc' => $data];
        return json_encode($data);
    }

}