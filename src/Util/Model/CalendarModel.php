<?php

declare(strict_types=1);

namespace Saf\Util\Model;

use Saf\Utils\Time;

class CalendarModel
{
	public function __construct($config = array())
	{

	}

	public function getUserMonthYear($time = null)
	{
		if (is_null($time)) {
			$time = Time::time();
		}
		return date('F Y', $time);
	}

	public function fullView()
	{
		return true;
	}

	public function getMinDate()
	{
		return date('Y-m-d', PHP_INT_MIN);
	}

	public function getMaxDate()
	{
		return date('Y-m-d', PHP_INT_MAX);
	}

	public function getDefaultDate()
	{
		return date('Y-m-d');
	}

	public function allowedYear($year)
	{
		return true;
	}

	public function allowedMonth($month, $year = null)
	{
		return true;
	}

	public function allowedDate($day, $month = null, $year = null)
	{
		return true;
	}


}