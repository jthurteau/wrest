<?php

/**
 * defines a simple extendable model object for Date Pickers
 */

declare(strict_types=1);

namespace Saf\Util\Model;

use Saf\Util\Time;

class CalendarModel
{

	public function getUserMonthYear(int $time = null): string
	{
		if (is_null($time)) {
			$time = Time::time();
		}
		return date('F Y', $time);
	}

	public function fullView(): bool
	{
		return true;
	}

	public function getMinDate(): string
	{
		return date('Y-m-d', PHP_INT_MIN);
	}

	public function getMaxDate(): string
	{
		return date('Y-m-d', PHP_INT_MAX);
	}

	public function getDefaultDate(): string
	{
		return date('Y-m-d');
	}

	public function allowedYear($year): bool
	{
		return true;
	}

	public function allowedMonth($month, $year = null): bool
	{
		return true;
	}

	public function allowedDate($day, $month = null, $year = null): bool
	{
		return true;
	}

}