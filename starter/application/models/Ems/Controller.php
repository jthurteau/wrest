<?php //#SCOPE_NCSU_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

abstract class Ems_Controller extends Saf_Controller_Zend
{

	protected function _extractDate($source = 'date', $default = NULL, $min = NULL, $max = NULL, $request = NULL)
	{
		if (is_null($request)) {
			$request = $this->getRequest();
		}
		$postedDate = $this->_extractRequestParam($source, $default);
		if (!is_null($postedDate)) {
			$cleanDate =
				Saf_Time::isTimeStamp($postedDate)
				? (int)$postedDate
				: Saf_Time::modify(strtotime($postedDate), Saf_Time::MODIFIER_START_DAY);
		} else {
			$cleanDate = NULL;
		}
		if (!is_null($min)) {
			$cleanDate = max($min, $cleanDate);
		}
		if (!is_null($max) && !is_null($cleanDate)) {
			$cleanDate = min($max, $cleanDate);
		}
		return $cleanDate;
	}
	
	protected function _extractTime($source = 'time', $default = NULL, $min = NULL, $max = Saf_Time::MAX_HOUR_STAMP, $request = NULL)
	{
		if (is_null($request)) {
			$request = $this->getRequest();
		}
		$postedTime = $this->_extractRequestParam($source, $default);		
		if (!is_null($postedTime)) {
			$cleanTime = //#TODO #1.1.0 some other cases to catch here (e.g. full time string)
				Saf_Time::isHourStamp($postedTime, $max > Saf_Time::MAX_HOUR_STAMP)
				? (int) $postedTime
				: (
					Saf_Time::isTimeStamp($postedTime)
					? (int)$postedTime - Saf_Time::modify($postedTime, Saf_Time::MODIFIER_START_DAY)
					: (int)Saf_Time::hourStringToStamp($postedTime)
				);
		} else {
			$cleanTime = NULL;
		}
		if (!is_null($min)) {
			$cleanTime = max($min, $cleanTime);
		}
		if (!is_null($max) && !is_null($cleanTime)) {
			$cleanTime = min($max, $cleanTime);
		}
		return $cleanTime;
	}
	
	protected function _extractBuildingId($default = 0, $allowAny = TRUE)
	{
		$fallback = $allowAny ? 0 : 1;
		$ems = new Ems();
		$buildingMap = array(
				'any' => 0, 'hunt' => 1, 'hill' => 2
		);
		$buildingId = self::_extractRequestParam('buildingId', $default);
		if (array_key_exists($buildingId, $buildingMap)) {
			$buildingId = $buildingMap[$buildingId];
		}
		$buildingOptions = $ems->getAllBuildingNames($allowAny);
		return
		array_key_exists($buildingId, $buildingOptions)
		? $buildingId
		: $fallback;
	}
	
	protected function _extractRoomType($default = 1)
	{
		$fallback = Ems::EMS_DEFAULT_ROOM_TYPE;
		$ems = new Ems();
		$roomTypeMap = array( //#TODO #1.1.0 move this into the model since rooms/list also needs it
				'groupstudy' => 1, 'digitalmedia' => 2, 'hightech' => 3, 'presentation'	=> 4
		);
		$roomType = self::_extractRequestParam('roomType', $default);
		if (array_key_exists($roomType, $roomTypeMap)) {
			$roomType = $roomTypeMap[$roomType];
		}
		$roomTypeOptions = $ems->getRoomTypeLabels();
		return
		array_key_exists($roomType, $roomTypeOptions)
		? $roomType
		: $fallback;
	}

}