<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;
use kornrunner\Keccak;

class DateService
{
    public function __construct()
    {
    }

    // Some dates appear to be in seconds while others are in milliseconds.  Deal with it.
    public static function dateTimeConverter($datetime)
    {
        try {
            if (strlen($datetime) == 10) {
                return date('Y-m-d H:i:s.v', $datetime);
            } else {
                // slice the last 3 zeros off
                $datetime = substr($datetime, 0, -3);
                return date('Y-m-d H:i:s.v', $datetime);
            }
        } catch (\Throwable $th) {
            return null;
        }
    }
}
