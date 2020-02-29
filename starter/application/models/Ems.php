<?php //#SCOPE_NCSU_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Ems
{
	const EMS_CURRENTBOOKINGS = 1;
	const EMS_PASTBOOKINGS = 2;
	const EMS_ALLBOOKINGS = 0;
	
	const EMS_ORDER_NEWEST = 'desc';
	const EMS_ORDER_OLDEST = 'asc';
	
	const EMS_DATE_TIME_FORMAT = 'Y-m-d\TH:i:s';
	const EMS_DATE_FORMAT = 'Y-m-d';
	const EMS_TIME_FORMAT = 'H:i:s';
	const EMS_USER_TIME_FORMAT = 'g:i A';
	const EMS_USER_DATECALENDAR_FORMAT = 'l, M d';
	const EMS_USER_MOBILE_DATECALENDAR_FORMAT = 'D, M d';
	
	const EMS_MINIMUM_TIME_BLOCK = 1800; // half an hour
	const EMS_START_TIME_BLOCK_SAF_TIME_MODIFIER = Saf_Time::MODIFIER_START_HALFHOUR;
	
	const EMS_MAX_SCAN_BLOCKS = 48;
	
	const EMS_DEFAULT_CONFIRMED_EVENT_STATUS = 1;
	const EMS_DEFAULT_NEW_EVENT_STATUS = self::EMS_DEFAULT_CONFIRMED_EVENT_STATUS;
	const EMS_DEFAULT_CANCELED_EVENT_STATUS = 2;
	const EMS_DEFAULT_DECLINED_EVENT_STATUS = 3;
	const EMS_DEFAULT_APPROVED_EVENT_STATUS = 4;
	const EMS_DEFAULT_PENDING_EVENT_STATUS = 5;
	const EMS_DEFAULT_NEW_EVENT_TYPE = 1;
	
	const EMS_STATUS_TYPE_INFOONLY = -11; //? -15 #TDB
	const EMS_STATUS_TYPE_CANCELED = -12;
	const EMS_STATUS_TYPE_PENDING = -13;
	const EMS_STATUS_TYPE_BOOKED = -14;
	
	const EMS_DEFAULT_GROUP_TYPE = 9;	
	const EMS_DEFAULT_ROOM_TYPE = 1;
	
	const EMS_DEFAULT_MAX_TIME_BLOCKS = 4;
	const EMS_DEFAULT_MAX_DAYS_OUT = 14;
	const EMS_DEFAULT_MAX_BOOKINGS = 1;

	const EMS_API_CALL_THRESHHOLD = 3;
	
	const EMS_SECURITY_TEMPLATE_ADMIN = 1;

	/*
	 * @var Ems_Api
	 */
	protected static $_api = NULL;
	
	protected static $_roomTypes = NULL;

	protected static $_roomTypeMap = array(
		'Group Study' => 1,
		'Group Study Lounge' => 1,
		'Presentation Practice' => 4,
		'Media Production' => 2,
		'High Tech Space' => 3
	);
	
	protected static $_roomTypeLabels = array(
		1 => 'Group Study Room',
		2 => 'Digital Media Production Space',
		3 => 'High Tech/Visualization Space',
		4 => 'Presentation/Seminar Space'
	);
	protected static $_unallowedRoomTypes = array(
		'Lynda.com Kiosks',
		'Music Room',
		'Lab',
		'Fishbowl',
		'Graduate Student Commons',
		'Faculty Research Commons',
		'Theater'
		
	);
	
	//#TODO #1.5.0 cleanup static-ness

	public function __construct($options = array())
	{
		if (is_null(self::$_api)) {
			$apiConfig = Saf_Kickstart::getConfigResource('emsapi');
			self::$_api = new Ems_Api($apiConfig);
		}
	}

	public static function getUserEventTypeLabel($id)
	{
		$eventTypeData = self::$_api->getEventTypes((int)$id);
		return Saf_Array::extractIfArray('label', $eventTypeData, 'UNKNOWN');
	}
	
	public static function getUserStatusLabel($id)
	{
		$statusData = self::$_api->getStatuses((int)$id);
		return Saf_Array::extractIfArray('label', $statusData, 'UNKNOWN');
	}
	
	public static function getUserRoomLabel($id)
	{
		$roomData = self::$_api->getRooms((int)$id);
		return Saf_Array::extractIfArray('label', $roomData, 'UNKNOWN');
	}
	
	public static function getRoomNumber($id) //#TODO move this into the API only 
	{
		$roomData = self::$_api->getRooms((int)$id);
		$roomCode = Saf_Array::extractIfArray('code', $roomData, 'UNKNOWN');
		return str_replace(array('Hunt','DHL'),'', $roomCode);
	}
		
	public static function getUserLocationLabel($roomId)
	{
		$roomData = self::$_api->getRooms((int)$roomId);
		return Saf_Array::extractIfArray('location', $roomData, 'UNKNOWN');
	}
	public static function getUserBuildingLabel($id)
	{
		$buildingData = self::$_api->getBuildings((int)$id);
		return Saf_Array::extractIfArray('label', $buildingData, 'UNKNOWN');
	}
	
	public static function getFormatedDateTime($timestamp = NULL, $modifier = NULL)
	{
		if (is_null($timestamp)) {
			$timestamp = time();
		}
		return date(self::EMS_DATE_TIME_FORMAT, Saf_Time::modify($timestamp, $modifier));
	}
	
	public static  function getRestOfDayTimeBlocks($skipBlocks = 0, $maxDiff= NULL)
	{
		$skipBlocks = (int)$skipBlocks;
		$maxIndex = 100;
		$return = array();
		$now = time();
		$tomorrow = Saf_Time::modify($now, Saf_Time::MODIFIER_START_TOMORROW);
		$start = Saf_Time::modify($now, self::EMS_START_TIME_BLOCK_SAF_TIME_MODIFIER);
		$startToday = Saf_Time::modify($now, Saf_Time::MODIFIER_START_TODAY);
		$next = Saf_Time::modify($now, Saf_Time::MODIFIER_START_NEXT_HALFHOUR);
		if ($start != $next) {
			if (!$skipBlocks) {
				$return[$start - $startToday] = date(self::EMS_USER_TIME_FORMAT, $start);
			} else {
				$skipBlocks--;
			}
		}
		$index = 1;
		while ($next < $tomorrow && $index < $maxIndex) {
			if ($index > $skipBlocks) {
				$return[$next - $startToday] = date(self::EMS_USER_TIME_FORMAT, $next);
			}
			$next = Saf_Time::modify($next, Saf_Time::MODIFIER_START_NEXT_HALFHOUR);
			$index++;
		}
		return $return;
	}
	
	public static  function getDayTimeBlocks($skipBlocks = 0, $overflowBlocks = 0)
	{
		$skipBlocks = (int)$skipBlocks;
		$overflowBlocks = (int)$overflowBlocks;
		$maxIndex = 100;
		$return = array();
		$current = 0;
		$increment = Ems::EMS_MINIMUM_TIME_BLOCK;
		$max = (60 * 60 * 24) + (Ems::EMS_MINIMUM_TIME_BLOCK * $overflowBlocks);
		$hour = 0;
		$minute = 0;
		$meridian = 0;
		$index = 1;
		while ($current < $max && $index < $maxIndex) {
			if ($index > $skipBlocks) {
				$formatHour = (
					0 != $hour
					? str_pad($hour,2,' ',STR_PAD_LEFT)
					: 12		
				);
				$formatMinute = str_pad($minute,2,'0',STR_PAD_LEFT);
				$formatMeridian = (
					$meridian != 1
					? 'AM'
					: 'PM'		
				);
				
				$extra =
					$meridian == 2
					? ' (the next day)'
					: '';
				$return[$current] = "{$formatHour}:{$formatMinute} {$formatMeridian}{$extra}";
			}
			$minute += ($increment / 60);
//print_r(array($minute,$current));
			if ($minute > 59) {
				$hour++;
				$minute = $minute - 60;
				if ($hour > 11) {
					$meridian++;
					$hour = $hour - 12;
					if ($meridian > 1) {
						if (!$overflowBlocks) {
							return $return;
						} else {
							$overflowBlocks--;
						}
					}
				}
			}
			$index++;
			$current += $increment;
		}
// if ($overflowBlocks){
// 	print_r(array('timeBlocks' => $skipBlocks, $overflowBlocks,$return));die;
// }
		return $return;
	}
	
	public static function getNextDays($number)
	{
		$number = (int)$number;
		$start = Saf_Time::modify(NULL, Saf_Time::MODIFIER_START_TODAY);
		$return = array();
		while ($number-- > 0) {
			$return[$start] = date(self::EMS_USER_DATECALENDAR_FORMAT, $start);
			$start = Saf_Time::modify($start, Saf_Time::MODIFIER_ADD_DAY);
		}
		return $return;
	}

	public static function getRoomList($username = NULL)
	{
		$rooms = self::$_api->getAllRooms();
		if (!is_null($username)) {
			foreach(self::userAllowedRoomIds($username) as $roomId) {
				if (!array_key_exists($roomId, $rooms)) {
					unset($rooms[$roomId]);
				}
			}
		}
		return $rooms;
	}
	
	public static function getAllRoomNames($withBuilding = TRUE)
	{
		$details = self::$_api->getAllRooms();
		$roomIdList = array();
		foreach($details as $index=>$room) {
			$roomIdList[$index] = $room[$withBuilding ? 'location' : 'label'];
		}
		return $roomIdList;
	}
	
	public static function getRoomDetails($roomId, $what = NULL)
	{
		$details = self::$_api->getAllRooms();
		if (!array_key_exists($roomId, $details)) {
			throw new Ems_Exception_Room_404('Requested a room that does not exist');
		}
		return 
			!is_null($what)
			? (
				array_key_exists($what, $details[$roomId])		
				? $details[$roomId][$what]
				: NULL
			)
			: $details[$roomId];
	}
	
	public static function getRoomTypes()
	{
		if (!is_null(self::$_roomTypes)) {
			return self::$_roomTypes;
		}
		self::$_roomTypes = array();
		$details = self::$_api->getAllRooms();
		foreach($details as $room) {
			if (
				'' != trim($room['type'])
			) {
				if (array_key_exists($room['type'], self::$_roomTypeMap)) {
					$index = self::$_roomTypeMap[$room['type']];
					if (!array_key_exists($index, self::$_roomTypes)) {
						self::$_roomTypes[$index] = array();
					}
					if (!in_array($room['type'], self::$_roomTypes[$index])) {
						self::$_roomTypes[$index][] = $room['type'];
					}
				} else if (!in_array($room['type'], self::$_unallowedRoomTypes)) {
					Saf_Debug::out("Unmapped Room Type [{$room['type']}]");
				}	
			}
		}
		return self::$_roomTypes;
	}
	
	public static function getRoomTypeLabels()
	{
		return self::$_roomTypeLabels;
	}
	
	public static function getAllBuildingNames($includeAnyOption = FALSE)
	{
		$details = self::$_api->getAllBuildings();
		$buildingIdList = 
			$includeAnyOption
			? array('Any Building')
			: array();
		foreach($details as $index => $buildingData) {
			$buildingIdList[$index] = $buildingData['label'];
		}
		return $buildingIdList;
	}
	
	public static function getUserInfo($userIdentifier, $allowDirectLookup = FALSE){
		if (!is_numeric($userIdentifier) && strpos($userIdentifier, '@') === FALSE) {
			$userIdentifier .= '@ncsu.edu';
		}
		if (!is_numeric($userIdentifier)) {
			$userFilter = array('email' => $userIdentifier);
		} else {
			if (
				$allowDirectLookup
				&& ( 
					strpos($userIdentifier,'0') !== 0 
					|| !(is_string($userIdentifier) && strlen($userIdentifier) == 9)
				)
			) {
				//#TODO pull directly with internalID
				throw new Exception('Not Implemented');
			}
			$userFilter = array('external' => $userIdentifier);
		}
		return self::$_api->getUserInfo($userFilter);
	}
	
	public static function getUserId($userIdentifier){
		$userInfo = self::getUserInfo($userIdentifier);
		if ($userInfo && array_key_exists('id', $userInfo)) {
			return $userInfo['id'];
		}
	}
	
	public static function getUserGroupIds($userIdentifier){
		$userInfo = self::getUserInfo($userIdentifier);
		$email = $userInfo['email'];
		$groups = self::$_api->getGroupsByEmail($email);
		return array_keys($groups);
	}
	
	public static function createDefaultUserGroup($userIdentifier){
		if (!self::getUserGroupIds($userIdentifier)) {
			$userInfo = self::getUserInfo($userIdentifier);
			$email = $userInfo['email'];
			if (trim($email == '')) {
				throw new Saf_Exception_NotImplemented('Scheudling system account has not email associated.');
			}
			$newId = self::$_api->createGroupByEmail($email);
			return $newId;
		}
		return FALSE;
	}

	public static function getUserBookings($username, $flag = self::EMS_CURRENTBOOKINGS, $order = self::EMS_ORDER_OLDEST){
		$today = Saf_Time::time();
		$userId = self::getUserId($username);
		if (!$userId) { //#TODO #1.1.0 this is actually a 404 condition
			throw new Saf_Exception_AccountDisorder("No scheduling system account for {$username}");
		}
		switch($flag){
			case self::EMS_PASTBOOKINGS:
				$bookings = self::$_api->getWebUserBookings($userId, 0, $today); //#TODO #1.1.0 support timestamp+/-range format for start/end for archive view pagination.
				break;
			case self::EMS_ALLBOOKINGS:
				$bookings = self::$_api->getWebUserBookings($userId);
				break;
			case self::EMS_CURRENTBOOKINGS:
			default: //#TODO #1.1.0 support timestamp+/-range format for start/end for archive view pagination.
				$bookings = self::$_api->getWebUserBookings($userId, $today, NULL);
				break;			
		}
		Saf_Debug::outData(array('pre filter bookings',$bookings));
		return self::tidyBookings($bookings, $order, TRUE);
	}

	public static function getUserMaxBookings($username, $roomId = NULL)
	{
		if (self::userCanIgnoreRules($username)) {
			return -1;
		}
		$uid = self::getUserId($username);
		if ($roomId) {
			$roomTemplateIds = self::getWebProcessTemplateIdMatch($roomId, $uid);
		}
		$userTemplates =
			$roomId
			? self::$_api->getWebTemplates($roomTemplateIds)
			: self::$_api->getUserWebTemplates($uid);
		$maxBookings = self::EMS_DEFAULT_MAX_BOOKINGS;
		if ($userTemplates) {
			foreach($userTemplates as $templateDetails) {
				$templateBookings = $templateDetails['maxBook'];
				if ($templateBookings > $maxBookings) {
					$maxBookings = $templateBookings;
				}
			}
		} else {
			Saf_Debug::outData(array('no matched templates for bookings rule', $username, $roomId, $userTemplates));
			return 0;
		}
		return $maxBookings;
	}
	
	public static function getUserMaxDaysOut($username, $roomId = NULL)
	{
		if (self::userCanIgnoreRules($username)) {
			return 1830;
		}
		$uid = self::getUserId($username);
		if ($roomId) {
			$roomTemplateIds = self::getWebProcessTemplateIdMatch($roomId, $uid);
		}
		$userTemplates = 
			$roomId
			? self::$_api->getWebTemplates($roomTemplateIds)
			: self::$_api->getUserWebTemplates($uid);
		$maxDays = self::EMS_DEFAULT_MAX_DAYS_OUT;
		if ($userTemplates) {
			foreach($userTemplates as $templateDetails) {
				$templateDays = $templateDetails['maxDays'];
				if ($templateDays > $maxDays) {
					$maxDays = $templateDays;
				}
			}
		} else {
			Saf_Debug::outData(array('no matched templates for days rule', $username, $roomId, $userTemplates));
			return 0;
		}

		return $maxDays;
	}
	
	public static function getUserMaxTimeBlocks($username, $roomId = NULL)
	{
		if (self::userCanIgnoreRules($username)) {
			return self::EMS_MAX_SCAN_BLOCKS;
		}
		$uid = self::getUserId($username);
		if ($roomId) {
			$roomTemplateIds = self::getWebProcessTemplateIdMatch($roomId, $uid);
		}
		$userTemplates = 
			$roomId
			? self::$_api->getWebTemplates($roomTemplateIds)
			: self::$_api->getUserWebTemplates($uid);
		$maxBlocks = self::EMS_DEFAULT_MAX_TIME_BLOCKS;
		if ($userTemplates) {
			foreach($userTemplates as $templateDetails) {
				$templateBlocks = ceil((float)$templateDetails['maxMin'] / 30);
				if ($templateBlocks > $maxBlocks) {
					$maxBlocks = $templateBlocks;
				}
			}
		} else {
			Saf_Debug::outData(array('no matched templates for timeblocks rule', $username, $roomId, $userTemplates));
			return 0;
		}
		return $maxBlocks;
	}
		
	public static function getRooms($buildingId, $roomType = NULL, $sortBy = 'size')
	{
		if ($buildingId == 0) {
			$buildingId = NULL;
		}
		self::getRoomTypes();
		if (!is_null($roomType) && !array_key_exists($roomType, self::$_roomTypes)) {
			throw new Exception('Invalid Room Type'); //#TODO #1.1.0 make it a proper exception
		}
		$roomTypes = 
			is_null($roomType)
			? NULL
			: self::$_roomTypes[$roomType];
		$roomList = self::$_api->getAllRooms($roomTypes, $buildingId);
		//$TODO #1.1.0 sort on size
		return $roomList;
	}
	
	public static function getRoomSchedules($roomIds, $startTime, $endTime)
	{
		$schedules = array();
		if (count($roomIds > self::EMS_API_CALL_THRESHHOLD)) { 
			//#NOTE preload the building schedule so we don't choke on a million calls.
			$buildingIds = array();
			$roomDetails = self::$_api->getRooms($roomIds);
			foreach($roomDetails as $roomId => $room) {
				if (!in_array($room['buildingId'], $buildingIds)) {
					$buildingIds[] = $room['buildingId'];
				}
			}
			self::$_api->getBuildingBookings($buildingIds, $startTime, $endTime);
		}
		foreach ($roomIds as $roomId) {
			$schedules[$roomId] = self::$_api->getRoomBookings($roomId, $startTime, $endTime);
		}
		return $schedules;
	}
	
	public static function getRoomAvailability($roomIds, $startTime, $endTime = NULL, $schedule = NULL)
	{
		if (!is_array($startTime)) {
			$startTime = array($startTime);
		}
		if (is_null($endTime)) {
			$endTime = $startTime[0] + (self::EMS_MAX_SCAN_BLOCKS * self::EMS_MINIMUM_TIME_BLOCK);
		}
		if (is_null($schedule)) {
			$schedule = self::getRoomSchedules($roomIds, $startTime[0], $endTime);
		}
		$availability = array();
		foreach($startTime as $currentStartTime) {
			$startDate = Saf_Time::modify($currentStartTime, Saf_Time::MODIFIER_START_DAY);
			$startTimeStamp = $currentStartTime - $startDate;
			foreach($roomIds as $roomId) {
				if (!array_key_exists($roomId, $availability)) {
					$availability[$roomId] = array();
				}
				$availableBlocks = 0;
				$current = $startTimeStamp;
				for($i = 0; $i < self::EMS_MAX_SCAN_BLOCKS; $i++){ //#TODO #1.2.0 pull availability from view instead
					if (
							array_key_exists($roomId, $schedule)
							&& array_key_exists($startDate, $schedule[$roomId])
					) {
						foreach($schedule[$roomId][$startDate] as $bookingCurrent => $bookings) {
							if ($bookingCurrent == $current) {
								foreach($bookings as $bookingId => $bookingData) {
									if(
											$bookingData['status'] == Ems::EMS_DEFAULT_CONFIRMED_EVENT_STATUS
											|| $bookingData['status'] == Ems::EMS_DEFAULT_APPROVED_EVENT_STATUS
									) {
										break 3;
									}
								}
							}
						}
					}
					$availableBlocks++;
					$current = $current + self::EMS_MINIMUM_TIME_BLOCK;
				}
				$availability[$roomId][$currentStartTime] = $availableBlocks;
			}	
		}
		return $availability;
	}
	
	public static function tidyBookings($bookings, $sortOrder = NULL){
		foreach($bookings as $bookingId => $bookingData) {
			if (!array_key_exists('code', $bookings[$bookingId])) {//#NOTE try to avoid filtering twice
				$bookings[$bookingId]['code'] = "{$bookingData['reservationId']}/{$bookingId}";
				$bookings[$bookingId]['date'] = 
					Saf_Time::modify(
						strtotime($bookings[$bookingId]['date']),
						Saf_Time::MODIFIER_START_DAY
					);
				$bookings[$bookingId]['originalStart'] = $bookings[$bookingId]['start'];
				$bookings[$bookingId]['originalEnd'] = $bookings[$bookingId]['end'];
				$bookings[$bookingId]['dateString'] = 
					date(Ems::EMS_DATE_FORMAT, $bookings[$bookingId]['date']);
				$bookings[$bookingId]['userDate'] =
					date(Ems::EMS_USER_DATECALENDAR_FORMAT, $bookings[$bookingId]['date']);
				$bookings[$bookingId]['userYear'] =
					date('Y', $bookings[$bookingId]['date']);
				$bookings[$bookingId]['userMonth'] =
					date('M', $bookings[$bookingId]['date']);
				$bookings[$bookingId]['userDay'] =
					date('j', $bookings[$bookingId]['date']);
				$bookings[$bookingId]['startString'] =
					substr(
						$bookings[$bookingId]['start'], 
						strpos($bookings[$bookingId]['start'], 'T') + 1
					);
				$bookings[$bookingId]['fullStart'] =
					strtotime($bookings[$bookingId]['start']);
				$bookings[$bookingId]['userStart'] = date(
					Ems::EMS_USER_TIME_FORMAT, 
					$bookings[$bookingId]['fullStart']
				);
				$bookings[$bookingId]['start'] =
					Saf_Time::hourStringToStamp($bookings[$bookingId]['start']);
				$bookings[$bookingId]['endString'] =
					substr(
						$bookings[$bookingId]['end'], 
						strpos($bookings[$bookingId]['end'], 'T') + 1
					);
				$bookings[$bookingId]['fullEnd'] =
					strtotime($bookings[$bookingId]['end']);
				$bookings[$bookingId]['userEnd'] = date(
					Ems::EMS_USER_TIME_FORMAT, 
					$bookings[$bookingId]['fullEnd'] + 1
				);
				$bookings[$bookingId]['truncatedEnd'] =
				Saf_Time::hourStringToStamp($bookings[$bookingId]['end']);
				$bookings[$bookingId]['crossesMidnight'] =
					$bookings[$bookingId]['fullEnd'] - $bookings[$bookingId]['date']
						> Saf_Time::MAX_HOUR_STAMP;
				$bookings[$bookingId]['end'] =
					$bookings[$bookingId]['crossesMidnight']
					? $bookings[$bookingId]['truncatedEnd'] + Saf_Time::MAX_HOUR_STAMP + 1
					: $bookings[$bookingId]['truncatedEnd'];
				$bookings[$bookingId]['userEventType'] =
					self::getUserEventTypeLabel($bookings[$bookingId]['eventType']);
				$bookings[$bookingId]['userStatus'] =
					self::getUserStatusLabel($bookings[$bookingId]['status']);
				$bookings[$bookingId]['userRoom'] =
					self::getUserRoomLabel($bookings[$bookingId]['roomId']);
				$bookings[$bookingId]['userLocation'] =
					self::getUserLocationLabel($bookings[$bookingId]['roomId']);
				$bookings[$bookingId]['userBuilding'] =
					self::getUserBuildingLabel($bookings[$bookingId]['buildingId']);
				$bookings[$bookingId]['roomNumber'] =
					self::getRoomNumber($bookings[$bookingId]['roomId']);
				$bookings[$bookingId]['roomType'] =
					self::getRoomDetails($bookings[$bookingId]['roomId'], 'type');
				$bookings[$bookingId]['roomCapacity'] =
					self::getRoomDetails($bookings[$bookingId]['roomId'], 'capacity');
			}
		}
		return
			is_null($sortOrder)
			? $bookings
			: self::chronoSortBookings($bookings, $sortOrder);
	}
	
	public static function filterBookingsByStatus($bookings, $status)
	{
		if (!is_array($status)) {
			$status = array($status);
		}
		$return = array();
		foreach ($bookings as $bookingId => $booking) {
			if (in_array($booking['status'], $status)) {
				$return[$bookingId] = $booking;
			}
		}
		return $return;
	}

	public static function chronoSortBookings($bookings, $order = self::EMS_ORDER_OLDEST)
	{
		$sort =
			$order == self::EMS_ORDER_OLDEST
			? SORT_ASC
			: SORT_DESC;
		$sortedBookings = array();
		$bookingStart = array();
		$bookingRoom = array();
		foreach ($bookings as $bookingId => $bookingData) {
			$bookings[$bookingId]['id'] = $bookingId;
			$bookingStart[(string)$bookingId] = $bookingData['fullStart'];
		}
		array_multisort($bookingStart, $sort, $bookings); //#TODO #2.0.0 is there a better way to do this while preserving the key?
		foreach($bookings as $index => $bookingData) {
			$sortedBookings[$bookingData['id']] = $bookingData;
			unset($sortedBookings[$bookingData['id']]['id']);
		}
		return $sortedBookings;
	}
	
	public function getBookingDates(){
		$dates = array();
		$numberOfDaysOut = 14;
		$dateTimeStamp = Saf_Time::modify(Saf_Time::MODIFIER_START_TODAY);
		$dateString = date(Ems::EMS_DATE_FORMAT, $dateTimeStamp) . ' (Today)';
		for($i = 0; $i < $numberOfDaysOut; $i++) {
			$dates[$dateTimeStamp] = $dateString;
			$dateTimeStamp = Saf_Time::modify($dateTimeStamp, Saf_Time::MODIFIER_ADD_DAY);
			$dateString = date(Ems::EMS_DATE_FORMAT .' (l)', $dateTimeStamp);
		}
		return $dates;
	}
	
	public static function createReservation($data)
	{
		$username = $data['username'];
		$data['uid'] = self::getUserId($username);
		if (!$data['uid']) { //#TODO #1.1.0 this is actually a 403
			throw new Ems_Exception_User_401("No Web User Account for {$username}");
		}
		//#TODO #1.1.0 check max reservations
		$data['group'] = self::$_api->getDefaultGroupId($username . '@ncsu.edu');
		$data['status'] = self::EMS_DEFAULT_NEW_EVENT_STATUS;
		$data['eventType'] = self::EMS_DEFAULT_NEW_EVENT_TYPE;
		$matchingTemplate = self::_findAllowableWebProcessTemplate($data);
		if ($matchingTemplate) {
			$data['template'] = $matchingTemplate;	
			$reservationCode =  self::$_api->addWebUserReservation($data);
		} else {
			throw new Ems_Exception_Room_403('Not allowed to book this room');
		}
		if (!$reservationCode) {
			throw new Ems_Exception_Room_403('Unable to book this room');
		}
		return $reservationCode;
	}
	
	public static function modifyReservation($data)
	{
		$username = $data['username'];
		$data['uid'] = self::getUserId($username); //#TODO #1.2.0 allow staff to create reservations for other users
		if (!$data['uid']) { //#TODO #1.2.0 this is actually a 403
			throw new Ems_Exception_User_401("No Web User Account for {$username}");
		}
		$data['group'] = self::$_api->getDefaultGroupId($username . '@ncsu.edu');
		if (!array_key_exists('status',$data)) {
			$data['status'] = self::EMS_DEFAULT_NEW_EVENT_STATUS;
		}
		if (!array_key_exists('eventType',$data)) {
			$data['eventType'] = self::EMS_DEFAULT_NEW_EVENT_TYPE;
		}
		$codeParts = explode('/', $data['code'], 2);
		$bookingParts = explode('-', $codeParts[1]); //#TODO #1.1.0 test this and throw if somehow the bookingid is missing
		$data['bookingId'] = $bookingParts[0];
		$reservationCode = $codeParts[0];
		Saf_Debug::outData(array('Modifying Booking', $data));
		$bookingId = self::$_api->updateBooking($data);
		if (!$bookingId) {
			throw new Ems_Exception_Room_403('Unable to modify this reservation');
		}
		$reservationCode .= "/{$bookingId}";
		return $reservationCode;
	}
	
	public static function cancelReservation($data)
	{
		$data['status'] = Ems::EMS_DEFAULT_CANCELED_EVENT_STATUS;
		Saf_Debug::outData(array('canceling booking', $data));
		return self::modifyReservation($data);
	}

	public static function getBookingInfo($bookingId){
		$booking = self::$_api->getBooking($bookingId);
		if ($booking) {
			Saf_Debug::outData(array('prefiltered booking', $bookingId, $booking));
			$filteredBooking = self::tidyBookings(array($bookingId => $booking), NULL);
			return $filteredBooking[$bookingId];
		} else {
			return NULL;
		}
	}
	
	protected static function _findAllowableWebProcessTemplate($data)
	{ //#TODO #1.1.0 implement rule testing
		$matches = self::getWebProcessTemplateIdMatch($data['roomId'], $data['uid']);
		if (count($matches) > 0 ){
			reset($matches);
			return current($matches);
		}
		return NULL;
	}
	
	public static function getWebProcessTemplateIdMatch($roomId, $uid)
	{
		$roomTemplates = array_keys(self::$_api->getRoomWebTemplates($roomId));
		$userTemplates = array_keys(self::$_api->getUserWebTemplates($uid));
		return array_intersect($roomTemplates, $userTemplates);
	}
	
	public static function getWebProcessTemplateRoomIds($uid)
	{
		$roomTemplates = self::$_api->getRoomWebTemplates();
		$userTemplates = array_keys(self::$_api->getUserWebTemplates($uid));
		$roomIds = array();
		foreach($userTemplates as $templateId){
			foreach($roomTemplates as $roomId => $roomTemplateList){
				if (
					array_key_exists($templateId, $roomTemplateList)
					&& !in_array($roomId, $roomIds)
				){
					$roomIds[] = $roomId;
				}
			}
		}
		return $roomIds;
	}
	
	public static function userAllowedRoomIds($username)
	{
		$uid = self::getUserId($username);
		return self::getWebProcessTemplateRoomIds($uid);
	}
	
	public static function userCanViewAll($username)
	{
		if (is_null($username) || '' == trim($username)) {
			return FALSE;
		}
		$userData = self::getUserInfo($username);
		return 
			is_array($userData)
			&& array_key_exists('securityTemplateId', $userData)
			&& $userData['securityTemplateId'] == self::EMS_SECURITY_TEMPLATE_ADMIN;
	}
	
	public static function userCanEditAll($username)
	{
		if (is_null($username) || '' == trim($username)) {
			return FALSE;
		}
		$userData = self::getUserInfo($username);
		return 
			is_array($userData)
			&& array_key_exists('securityTemplateId', $userData)
			&& $userData['securityTemplateId'] == self::EMS_SECURITY_TEMPLATE_ADMIN;
	}
	public static function userCanProxyFor($username, $forUsername = NULL)
	{
		if (is_null($username) || '' == trim($username)) {
			return FALSE;
		}
		//#TODO #2.0.0 if user can proxy for $forUsername return TRUE
		$userData = self::getUserInfo($username);
		return 
			is_array($userData)
			&& array_key_exists('securityTemplateId', $userData)
			&& $userData['securityTemplateId'] == self::EMS_SECURITY_TEMPLATE_ADMIN;
	}
	
	public static function userCanIgnoreRules($username)
	{
		if (is_null($username) || '' == trim($username)) {
			return FALSE;
		}
		$userData = self::getUserInfo($username);
		return 
			is_array($userData)
			&& array_key_exists('securityTemplateId', $userData)
			&& $userData['securityTemplateId'] == self::EMS_SECURITY_TEMPLATE_ADMIN;
	}
	
	public static function userCanAdminSystem($username)
	{
		$admins = Zend_Registry::get('config')->get('sysAdmins');
		if ($admins && $admins->admin) {
			$admins = 
				is_object($admins->admin)
				? $admins->admin->toArray()
				: is_array($admins->admin)
					? $admins->admin
					: array($admins->admin);
			foreach($admins as $admin) {
				if (trim($admin) == $username) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}
	
	public static function userCanCreateReservations($username)
	{
		if (is_null($username) || '' == trim($username)) {
			return FALSE;
		}
		return TRUE;
	}
}