<?php

namespace App\Services;

class HashService
{
    public function __construct()
    {
    }

    public static function hashMultidimensionalArray($array, $hashFunction = 'md5')
    {
        return $hashFunction(self::flattenArray($array));
    }

    private static function flattenArray($array, $prefix = '')
    {
        $result = '';

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Recursively process the sub-array
                $result .= self::flattenArray($value, $prefix . $key . '.');
            } else {
                // Concatenate the key and value
                $result .= $prefix . $key . $value;
            }
        }

        return $result;
    }
}
