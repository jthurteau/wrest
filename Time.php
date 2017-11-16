<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility functions for Time, includes testing insulation intended to replace 
direct use of time() and microtime()

*******************************************************************************/

class Saf_Time {

	const MODIFIER_NOW = NULL;
	const MODIFIER_START_TODAY = 1; //#TODO #2.0.0 use native modifier string codes and refactor
	const MODIFIER_END_TODAY = 2;
	const MODIFIER_START_TOMORROW = 3;
	const MODIFIER_START_HOUR = 4;
	const MODIFIER_END_HOUR = 5;
	const MODIFIER_START_NEXT_HOUR = 6;
	const MODIFIER_END_NEXT_HOUR = 7;
	const MODIFIER_START_HALFHOUR = 8;
	const MODIFIER_END_HALFHOUR = 9;
	const MODIFIER_START_NEXT_HALFHOUR = 10;
	const MODIFIER_END_NEXT_HALFHOUR = 11;
	const MODIFIER_START_QTRHOUR = 12;
	const MODIFIER_END_QTRHOUR = 13;
	const MODIFIER_START_NEXT_QTRHOUR = 14;
	const MODIFIER_END_NEXT_QTRHOUR = 15;
	const MODIFIER_START_MINUTE = 16;
	const MODIFIER_END_MINUTE = 17;
	const MODIFIER_START_NEXT_MINUTE = 18;
	const MODIFIER_END_NEXT_MINUTE = 19;
	
	const MODIFIER_START_DAY = 20;
	const MODIFIER_END_DAY = 21;
	
	const MODIFIER_ADD_MINUTE = 100;
	const MODIFIER_ADD_QRTHOUR = 101;
	const MODIFIER_ADD_HALFHOUR = 102;
	const MODIFIER_ADD_HOUR = 103;
	const MODIFIER_ADD_DAY = 104;	
	const MODIFIER_ADD_WEEK = 105;	
	const MODIFIER_ADD_MONTH = 106;	
	const MODIFIER_ADD_YEAR = 107;
	
	const MODIFIER_SUB_MINUTE = 200;
	const MODIFIER_SUB_QRTHOUR = 201;
	const MODIFIER_SUB_HALFHOUR = 202;
	const MODIFIER_SUB_HOUR = 203;
	const MODIFIER_SUB_DAY = 204;
	const MODIFIER_SUB_WEEK = 205;
	const MODIFIER_SUB_MONTH = 206;
	const MODIFIER_SUB_YEAR = 207;
	
	const MAX_HOUR_STAMP = 86399;
	
	const QUANT_MINTUTE = 60;
	const QUANT_HALFHOUR = 1800;
	const QUANT_HOUR = 3600;
	const QUANT_DAY = 86400;
	
	const FORMAT_DATE_URL = 'Y-m-d';
	const FORMAT_TIME_URL = 'H-i';
	const FORMAT_DATETIME_URL = 'Y-m-d/H-i';
	const FORMAT_DATETIME_DB = 'Y-m-d H:i:s';
	const FORMAT_MONTH_SHORT = 'F';
	const FORMAT_MONTH_FULL = 'M';

	
	/**
	 * How many seconds off real time we are testing against
	 * @var int
	 */
	protected static $_diff = 0;
	
	/**
	 * How many microseconds off we are testing against
	 * @var int
	 */
	protected static $_microDiff = 0;

	public static function set($seconds, $micro = 0)
	{
		$now = microtime(TRUE);
		$new = $seconds + ($micro / 1000);
		$offset = $new - $now;
		self::$_diff = ceil($offset);
		self::$_microDiff = $offset * 1000 % 1000;
		Saf_Debug::outData(array('setting debug time', $seconds, $micro, 'effective' => array(time() + self::$_diff, date(self::FORMAT_DATETIME_URL . '-s', time() + self::$_diff))));
	}

	/**
	 * returns the timestamp, including any differential
	 * 
	 * @param int $now input differential, defualts to now (i.e. the native time())
	 * @return int the modified timestamp
	 */	
	public static function time($now = NULL)
	{
		if (is_null($now)) {
			$now = time();
		}
		return $now + self::$_diff;
	}
	
	/**
	 * returns a modified timestamp
	 * @param unknown_type $timestamp
	 * @param unknown_type $modifier
	 */
	public static function modify($timestamp, $modifier = NULL)
	{ //#TODO #2.0.0 needs to factor in DST
		if (is_null($modifier)) {
			$modifier = $timestamp;
			$timestamp = self::time();
		} else if (
			is_null($timestamp) 
			|| (
				!is_int($timestamp) 
				&& trim($timestamp) == ''
			)
		) {
			$timestamp = self::time();
		}
		if (
			in_array($modifier, array(
				array(
					self::MODIFIER_END_TODAY,
					self::MODIFIER_START_TODAY,
					self::MODIFIER_START_TOMORROW //#TODO #2.0.0 others as needed		
				)		
			))
		) {
			$timestamp = self::time();
		}
		if (is_null($modifier)) {
			return $timestamp;
		}
		$timestamp = (int)$timestamp; //#TODO #2.0.0 debug option if 0
		$min = (int)date('i', $timestamp);
		$hour = (int)date('H', $timestamp);
		$day = (int)date('d', $timestamp);
		$month = (int)date('m', $timestamp);
		$year = (int)date('Y', $timestamp);		
		$nextMin = $min < 59 ? $min + 1 : 0;
		$nextHour = $hour < 23 ? $hour + 1 : 0;
		$nextDay = $day < (int)date('t') ? $day + 1 : 1;
		$nextMonth =  $month < 12 ? $month + 1 : 1;
		$nextYear = $year + 1;	
		$prevMin = $min > 0 ? $min - 1 : 59;
		$prevHour = $hour > 0 ? $hour -1 : 23;
		$prevDay =
			$day > 1
			? $day - 1
			: (int)date('t',
				strtotime(
					$year. '-'
					.  str_pad($month, 2, '0', STR_PAD_LEFT) . '-'
					. ( str_pad($day, 2, '0', STR_PAD_LEFT))
					. 'T00:00'
				) - 1
			);
		$prevMonth =  $month > 1 ? $month - 1 : 12;
		$prevYear = $year - 1;	
		$modSec = '00';
		$modMin = $min;
		$modHour = $hour;
		$modDay = $day;
		$modMonth = $month;
		$modYear = $year;
		switch($modifier){ //#TODO #2.0.0 build out in long form and then refactor once testing framework is in place
			case Saf_Time::MODIFIER_START_HALFHOUR :
				$modMin =
					$min < 30
					? 0
					: 30;
				break;
			case Saf_Time::MODIFIER_START_NEXT_HALFHOUR :
				$modMin = 
					$min < 30
					? 30
					: 0;
				$modHour =
					$min < 30
					? $hour
					: $nextHour;
				$modDay =
					$modHour >= $hour
					? $day
					: $nextDay;
				$modMonth =
					$modDay >= $day
					? $month
					: $nextMonth;
				$modYear =
					$modMonth >= $month
					? $year
					: $nextYear;
				break;
			case Saf_Time::MODIFIER_ADD_HALFHOUR :
				$modMin = 
					$min < 30
					? $min + 30
					: $min - 30;
				$modHour =
					$min < 30
					? $hour
					: $nextHour;
				$modDay =
					$modHour >= $hour
					? $day
					: $nextDay;
				$modMonth =
					$modDay >= $day
					? $month
					: $nextMonth;
				$modYear =
					$modMonth >= $month
					? $year
					: $nextYear;
				break;
			case Saf_Time::MODIFIER_START_TOMORROW :
				$modMin = 0;
				$modHour = 0;		
				$modDay = $nextDay;
				$modMonth =
					$modDay > $day
					? $month
					: $nextMonth;
				$modYear =
					$modMonth >= $month
					? $year
					: $nextYear;
				break;
			case Saf_Time::MODIFIER_ADD_DAY:
				$modDay = $nextDay;
				$modMonth =
					$modDay > $day
					? $month
					: $nextMonth;
				$modYear =
					$modMonth >= $month
					? $year
					: $nextYear;
				break;
			case Saf_Time::MODIFIER_SUB_DAY:
				$modDay = $prevDay;
				$modMonth =
					$modDay < $day
					? $month
					: $prevMonth;
				$modYear =
					$modMonth <= $month
					? $year
					: $prevYear;
				break;
			case Saf_Time::MODIFIER_START_DAY :
			case Saf_Time::MODIFIER_START_TODAY :
				$modMin = 0;
				$modHour = 0;
				break;
			case Saf_Time::MODIFIER_ADD_YEAR :
				$modYear = $nextYear;
				break;
			case NULL:
			default:
				return $timestamp;
		}
		$modMin = str_pad($modMin, 2, '0', STR_PAD_LEFT);
		$modHour = str_pad($modHour, 2, '0', STR_PAD_LEFT);
		$modDay = str_pad($modDay, 2, '0', STR_PAD_LEFT);
		$modMonth = str_pad($modMonth, 2, '0', STR_PAD_LEFT);
		//print_r(array('mod time', $timestamp, $modifier, $prevDay,"{$modYear}-{$modMonth}-{$modDay}T{$modHour}:{$modMin}:{$modSec}", strtotime("{$modYear}-{$modMonth}-{$modDay}T{$modHour}:{$modMin}:{$modSec}")));
		return strtotime("{$modYear}-{$modMonth}-{$modDay}T{$modHour}:{$modMin}:{$modSec}");
	}
	
	/**
	 * detects valid timestamps
	 * @param string $string
	 */
	public static function isTimeStamp($string)
	{
		$numberPattern = '/^[-]?[0-9]+$/';
		return !is_null($string) && preg_match($numberPattern, $string);
		//#TODO #2.0.0 does not factor in max int size
	}
	
	/**
	 * detects valid hour stamps 
	 * (integer in seconds relative to start of the day)
	 * if $allowOverflow, allow it to be relative to that many  additional days 
	 * (TRUE or 1 == 2 days)
	 * @param string $string
	 * @param bool $allowOverflow
	 * @return boolean
	 */
	public static function isHourStamp($string, $allowOverflow = FALSE)
	{
		$allowOverflow =
			$allowOverflow
			? (((self::MAX_HOUR_STAMP + 1) * ($allowOverflow + 1)) - 1)
			: self::MAX_HOUR_STAMP;
		$numberPattern = 
			$allowOverflow > self::MAX_HOUR_STAMP 
			? '/^[0-9]{1,11}$/' 
			: '/^[0-9]{1,5}$/';
		$match = !is_null($string) && preg_match($numberPattern, $string);
		return $match && (int)$string <= $allowOverflow;
	}
	
	/**
	 * converts H:i and H-i strings into hour stamps
	 * skips to after the T in typical WDDX timestamp
	 * this is a 24 hour format, it will not look for am/pm
	 * @param string $string
	 */
	public static function hourStringToStamp($string, $roundUpSeconds = TRUE)
	{
		$tPos = strpos($string, 'T');
		if ($tPos !== FALSE) {
			$string = substr($string, $tPos + 1);
		}
		$parts = explode(':', str_replace('-',':', $string), 3);
		if (!array_key_exists(1, $parts)) {
			$parts[1] = 0;
		} else {
			$parts[1] = min(59, $parts[1]);
		}
		if (array_key_exists(2, $parts)) {
			$parts[2] = substr($parts[2], 0, 2);
			if ((int)$parts[2] == 59) {
				$parts[1] += 1;
			}
		}
		$parts[0] = min(23, $parts[0]);
		$calc = min(
			self::MAX_HOUR_STAMP,
			((int)$parts[0] * 3600) 
				+ (array_key_exists(1, $parts) ? ((int)$parts[1] * 60) : 0)
		);
		//Saf_Debug::outData(array('hourStamp',$string,$parts[0],$parts[1],$calc)); //#DEBUG #1.5.0
		return $calc; 
	}

	public static function getOffset()
	{
		return self::$_diff;
	}

	public static function getMicroOffset()
	{
		return self::$_microDiff;
	}

	public static function lookupMonth($number, $format = self::FORMAT_MONTH_FULL)
	{
		$date = '2000-' . str_pad($number, 2, '0', STR_PAD_LEFT) . '-15';
		return date($format,strtotime($date));
	}

	public static function hourStampToString($hourStamp, $dateStamp = NULL, $format = 'g:i A')
	{
		if (is_null($dateStamp)) {
			$dateStamp = self::modify(Saf_Time::MODIFIER_START_TODAY);
		}
		return date($format, $hourStamp + $dateStamp);
	}

}
