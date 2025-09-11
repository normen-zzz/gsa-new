<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Exception;

class DateHelper
{

   public static function formatDate(?string $dateString, string $timezone = 'Asia/Jakarta'): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            $date = new DateTime($dateString);
            $date->setTimezone(new DateTimeZone($timezone));
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }
}

