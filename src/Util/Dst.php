<?php 

/**
 * Utility class for daylight-savings-time handling
 */

declare(strict_types=1);

namespace Saf\Util;

use Saf\Util\Time;

class Dst {

    public const TIME_BLOCK_SIZE = 1800;

    public static function correct($hourStamp, $dateStamp = null)
    {
        if (is_null($dateStamp)) {
            $dateStamp = Time::modify(Time::MODIFIER_START_TODAY);
        }
        if ($hourStamp >= $dateStamp) {
            $hourStamp -= $dateStamp; //#NOTE converts a full hourStamp to partial
        }
        $dateIsDaylight = date('I', $dateStamp);
        return (
            $dateIsDaylight
            ? (
                date('I', $dateStamp + $hourStamp)
                ? $hourStamp
                : $hourStamp + Time::QUANT_HOUR
            ) : (
                date('I', $dateStamp + $hourStamp)
                ? $hourStamp - Time::QUANT_HOUR
                : $hourStamp
            )
        );
    }

    public static function reverseCorrect($hourStamp, $dateStamp = null)
        //#TODO #1.14.0 this probably belongs in a locale model because it corrects DST in the correct direction for local and not EMS
    {
        if (is_null($dateStamp)) {
            $dateStamp = Time::modify(Time::MODIFIER_START_TODAY);
        }
        if ($hourStamp >= $dateStamp) {
            $hourStamp -= $dateStamp; //#NOTE converts a full hourStamp to partial
        }
        $dateIsDaylight = date('I', $dateStamp);
        return (
            $dateIsDaylight
            ? (
                date('I', $dateStamp + $hourStamp)
                ? $hourStamp
                : $hourStamp - Time::QUANT_HOUR
            ) : (
                date('I', $dateStamp + $hourStamp)
                ? $hourStamp + Time::QUANT_HOUR
                : $hourStamp
            )
        );
    }

    public static function detectSpringForward($date, $increment = self::TIME_BLOCK_SIZE)
    {
        $date = Time::modify($date, Time::MODIFIER_START_DAY);
        $dateIsDaylight = date('I', $date);
        for ($i = 0; !$dateIsDaylight && $i < Time::QUANT_DAY; $i += $increment) {
            if (date('I', $i + $date)) {
                return $i;
            }
        }
        return NULL;
    }


    public static function detectFallBack($date, $increment = self::TIME_BLOCK_SIZE)
    {
        $date = Time::modify($date, Time::MODIFIER_START_DAY);
        $dateIsDaylight = date('I', $date);
        for ($i = 0; $dateIsDaylight && $i < Time::QUANT_DAY; $i += $increment) {
            if (!date('I', $i + $date)) {
                return $i;
            }
        }
        return NULL;
    }

    public static function filterHours($hours, $date, $prevClose, $nextOpen, $increment = self::TIME_BLOCK_SIZE)
    {
        $dstHours = [];
        $springForward = false;
        $fallBack = false;
        $dateIsDaylight = date('I', $date);
        for($i = 0; $i < Time::QUANT_DAY; $i += $increment) {
            $fullStart = $i + $date;
            $startIsDaylight = date('I', $fullStart);
            $firstNonDaylightSavingsDay = !$dateIsDaylight && $startIsDaylight;
            $firstDaylightSavingsDay = $dateIsDaylight && !$startIsDaylight;
            if ($firstDaylightSavingsDay) {
                $fallBack = true;
                $dstHours[$i + Time::QUANT_HOUR] = $hours[$i];
            } else if($firstNonDaylightSavingsDay) {
                $springForward = true;
                $dstHours[$i - Time::QUANT_HOUR] = $hours[$i];
            } else {
                $dstHours[$i] = $hours[$i];
            }
        }
        if ($fallBack) {
            $dstHours[0] = $prevClose >= Time::QUANT_DAY - Time::QUANT_HOUR;
            $dstHours[Time::QUANT_HALFHOUR] = $prevClose >= Time::QUANT_DAY - Time::QUANT_HALFHOUR;
        }
        if ($springForward) {
            $dstHours[Time::QUANT_DAY - Time::QUANT_HOUR] = $nextOpen <= 0;
            $dstHours[Time::QUANT_DAY - Time::QUANT_HALFHOUR] = $nextOpen <= Time::QUANT_HALFHOUR;
        }
/*
Saf_Debug::outData(array(
    $hours, $dstHours, $date, date(Saf_Time::FORMAT_DATETIME_URL,$date),
    $prevClose, $nextOpen, $springForward, $fallBack,
    Time::QUANT_DAY - Time::QUANT_HOUR,
    Time::QUANT_DAY - Time::QUANT_HALFHOUR
));
*/
        return $dstHours;
    }

}