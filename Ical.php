<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for generating Ical

*******************************************************************************/

require_once(LIBRARY_PATH . '/Saf/Array.php');
require_once(LIBRARY_PATH . '/Saf/Time.php');

class Saf_Ical {
	
	const METHOD_REQUEST = 'Request';
	const METHOD_PUBLISH = 'Publish';
	const METHOD_CANCEL = 'Cancel';
	const OUTPUT_COL_MAX = 70;
	const OUTPUT_ESCAPE_CHARS = ':;,\\';
	const OUTPUT_REMOVE_CHARS = "";
	const OUTPUT_DATESTAMP = 'Ymd\THis\Z';
	const MIME_TYPE = 'text/calendar';

	protected $_ical = '';
	protected $_attachment = '';
	protected $_baseTime = NULL;
	protected $_timeInterval = 60;
	protected $_method = self::METHOD_REQUEST;
	protected $_id = NULL;
	protected $_filename = 'event.ics';
	
	protected $_hostId = '';
	protected $_productId = '';
	protected $_lang = 'EN';
	protected $_version = '2.0';

	protected static $_systemBaseTime = 0;
	
	protected static $_itemProps = array(
		'VEVENT' => array(
			'UID' => 1,
			'ORGANIZER' => '?',
			'ATTENDEE[]' => '*',
			'DTSTART' => 1,
			'DTEND' => 1,
			'DTSTAMP' => 1,
			'LOCATION' => '?',
			'SEQUENCE' => '?',
			'LAST-MODIFIED' => '?',
			'SUMMARY' => 1,
			'DESCRIPTION' => '?',
			'URL' => '?',
			'STATUS' => '?',
			'CATEGORIES' => '?'
		)
	);
	
	protected static $_propHelperMap = array(
		'fullStart' => 'DTSTART',
		'start' => 'DTSTART',
		'fullEnd' => 'DTEND',
		'end' => 'DTEND',
		'now' => 'DTSTAMP',
		'modified' => 'LAST-MODIFIED',
		'title' => 'SUMMARY',
		'sequence' => 'SEQUENCE',
		'name' => 'SUMMARY',
		'description' => 'SUMMARY',
		'userEmail' => 'ATTENDEE',
		'attendeeEmail' => 'ATTENDEE',
		'ownerEmail' => 'ORGANIZER',
		'userLocation' => 'LOCATION',
	);
	
	public function __construct($config = array()){//#TODO unmuddle some of this...
		$this->_hostId = Saf_Array::extract('hostId', $config, APPLICATION_ENV . '.' . APPLICATION_HOST);
		$this->_productId = Saf_Array::extract('productId', $config, APPLICATION_ID . '.' . APPLICATION_INSTANCE);
		$this->_baseTime = (int)Saf_Array::extract('baseTime', $config, self::$_systemBaseTime);
		$this->_timeInterval = (int)Saf_Array::extract('timeInterval', $config, $this->_timeInterval);
		$this->_method = Saf_Array::extractOptional('method', $config);
		$this->_id = Saf_Array::extractOptional('id', $config);
		switch ($this->_method) {
			case self::METHOD_REQUEST:
				$this->setMethodRequest();
				break;
			case self::METHOD_PUBLISH:
				$this->setMethodPublish();
				break;
			case self::METHOD_CANCEL:
				$this->setMethodCancel();
				break;
		}
		if (!is_null($this->_id) && (!is_array($this->_id) || count($this->_id) > 0)) {
			$this->generate($config);
		}
	}
	
	public function __toString(){
		return $this->getIcal();
	}

	public function setMethodRequest()
	{
		$this->_method = self::METHOD_REQUEST;
	}
	
	public function setMethodPublish()
	{
		$this->_method = self::METHOD_PUBLISH;
	}
	
	public function setMethodCancel()
	{
		$this->_method = self::METHOD_PUBLISH;
	}
	
	public function generate($data)
	{
		if(is_array($this->_id)) {
			$this->_generateMulti($data);
		} else {
			$this->_generate($data);
		}
	}
	
	public function getIcal(){
		return $this->_ical;
	}
	
	public function getAttachment(){
		return $this->_attachment;
	}
	
	protected function _getHeader()
	{
		$method = strtoupper($this->_method);
		$product = self::escapeText("{$this->_productId}//{$this->_hostId}");
		$scale = ''; //#TODO #9.0.0 implement
		$iana = ''; //#TODO #2.0.0 implement
		$xprop = ''; //#TODO #2.0.0 implement
		$url = '';
		return "BEGIN:VCALENDAR\n"
			. "METHOD:{$method}\n"
			. "PRODID:-//{$product}//{$this->_lang}\n"
			. "VERSION:{$this->_version}\n"
			. $xprop . $iana . $url;
	}
	
	protected function _getFooter()
	{
		return "END:VCALENDAR";
	}

	protected function _generate($data, $id = NULL)
	{
		$type = 'VEVENT'; //#TODO #2.0.0 support other types
		$itemData = $data;
		$this->_ical =
			(is_null($id) ? $this->_getHeader() : '')
			. "BEGIN:{$type}\n"
			. $this->_renderItem($type, is_null($id) ? $this->_id : $id , $itemData)
			. "END:{$type}\n"
			. (is_null($id) ? $this->_getFooter() : '');
		$this->_generateAttachment();
	}
	
	protected function _generateMulti($data)
	{
		$items = array();
		foreach($this->_id as $id => $item) {
			$items[] = $this->_generate($item, $id);
		}
		$this->_ical =
		$this->_getHeader()
			. implode("\n", $items)
			. $this->_getFooter();
		$this->_generateAttachment();		
	}
	
	protected function _renderItem($type, $id, $data)
	{
		$out = '';
		$outType = strtolower(substr($type, 1));
		$outProp = "$prop:";
		$dateStamp = gmdate(self::OUTPUT_DATESTAMP, Saf_Time::time());
		$version = $this->getVersion($data);
		$atProps = 'CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE'; //or just ROLE=REQ-PARTICIPANT;
		$uid = "{$outType}{$id}@{$this->_hostId}";
		//#TODO #2.0.0 see what more advanced attendee properties we can suppor...
		foreach(self::$_itemProps[$type] as $prop => $card) {
			$value = NULL;
			$propKey = array_key_exists($prop, $data)
				? $prop
				: $this->_resolvePropKey($prop, $data);
			$outProp = "$prop:";
			switch ($prop){
				case 'ORGANIZER':
				case 'ATTENDEE': 
					$attendeeMail =
						$propKey
							&& array_key_exists($propKey, $data)
						? $data[$propKey]
						: NULL;
					$cnSource =
						array_key_exists('USER:CN', $data)
						? $data['USER:CN']
						: (
							array_key_exists('userFullname', $data)
							? $data['userFullname']
							: NULL
						);
					if (is_array($attendeeMail)) {
						foreach($attendeeMail as $email) {
							$outProp = array();
							if (
								is_array($cnSource) && array_key_exists($email, $cnSource)
							) {
								$cn = ";CN={$cnSource[$email]}";
							} else {
								$cn = '';
							}
							$outProp = 'ATTENDEE;';
							$value[] = "{$atProps}{$cn}:MAILTO:{$email}";
						}
					} else if (!is_null($attendeeMail)) {
						$attendeeCn =
							is_array($cnSource)
							? ''
							: ";CN={$cnSource}";
						$outProp = 'ATTENDEE;';
						$value = "{$atProps}{$attendeeCn}:MAILTO:{$attendeeMail}";
					}
					break;
				case 'DTSTAMP':
				case 'DTSTART':
				case 'DTEND':
				case 'LAST-MODIFIED':
					$value = 
						$propKey
							&& array_key_exists($propKey, $data)
						? $data[$propKey]
						: NULL;
					if (!is_null($value)) {
						if (Saf_Time::isTimeStamp($value)) {
							$value = gmdate(self::OUTPUT_DATESTAMP, $value);
						} else { //if {
							//#TODO #1.1.0 detect non-GMT and convert
						}
					} else if ($prop == 'DTSTAMP') {
						$value = gmdate(self::OUTPUT_DATESTAMP, Saf_Time::time());
					}
					break;
					
				case 'UID':
					$value = $uid;
					break;

				case 'SEQUENCE':
					if (
						$propKey
						&& array_key_exists($propKey, $data)
						&& $data[$propKey] == '*'
					) {
						$value = Saf_Time::time() - $this->_baseTime;
					} else if (
						$propKey
						&& array_key_exists($propKey, $data)
					) {
						$value = (int)$data[$propKey];
					}
					break;
					
				case 'STATUS':
					switch (strtoupper($this->_method)) {
						case 'CANCEL' :
							$value = 
								$propKey
									&& array_key_exists($propKey, $data)
								? strtoupper($data[$propKey])
								: NULL;
							break;
						case 'PUBLISH' :
						case 'REQUEST' :
						default:
							$value = 
								$propKey
									&& array_key_exists($propKey, $data)
								? strtoupper($data[$propKey])
								: 'CONFIRMED';
					}
					break;
				default:
					if ($propKey && array_key_exists($propKey, $data)) {
						$value = self::escapeText($data[$propKey]);
					}
			}
			if ($card === '+' || $card === 1) {
				if (is_null($value)) {
					throw new Exception("Required value {$prop} missing to generate iCal for {$uid}");
				}
			} else if ($card === '?' || $card === 1) {
				if (is_array($value)) {
					throw new Exception("Too many values provided for {$prop} to generate iCal for {$uid}");
				}
			}
			if (is_array($value) && count($value) > 0) {
				foreach($value as $subValue) {
					$out .= "{$outProp}{$subValue}\n";
				}
			} else if (!is_array($value) && !is_null($value)) {
				$out .= "{$outProp}{$value}\n";
			}
		}
		return $out;
	}
	
	protected function _resolvePropKey($key, $data)
	{//#TODO #9.0.0 this could be optimized by flipping the mapping relation on the fly
	//#NOTE #9.0.0. the relation is stored the way it is for readability.
		foreach(self::$_propHelperMap as $dataKey => $propKey){
			if (
				array_key_exists($dataKey, $data)
				&& self::$_propHelperMap[$dataKey] == $key
			) {
				return $dataKey;
			}
		}
		return NULL;
	}
	
	protected function _generateAttachment()
	{
		$this->_attachment = new Zend_Mime_Part($this->_ical); //#TODO #2.0.0 drop Zend dependencies
		$method = strtoupper($this->_method);
		$this->_attachment->type = "text/calendar; method={$method}";
		$this->_attachment->disposition = Zend_Mime::DISPOSITION_INLINE;
		$this->_attachment->encoding = Zend_Mime::ENCODING_8BIT;
		$this->_attachment->filename = $this->_filename;
	}
	
	public static function escapeText($string)
	{
		$removeChars = str_split(self::OUTPUT_REMOVE_CHARS);
		$filtered = addcslashes(
			str_replace($removeChars, '', $string),	
			self::OUTPUT_ESCAPE_CHARS
		);
		$lines = str_split($filtered, self::OUTPUT_COL_MAX);
		if (strlen($lines[count($lines) - 1]) == 0) { //#TODO #2.0.0 research if this is needed
			unset($lines[count($lines) - 1]);
		}
		return implode("\n ", $lines);
	}
	
	public function getVersion()
	{
		return $this->_version;
	}

	public function getTimedVersion()
	{
		$versionTime = time() - $this->_baseTime;
		return (
			$versionTime - ($versionTime % $this->_timeInterval)
		) / $this->_timeInterval;
	}

	public static function setBaseTime($timestamp)
	{
		self::$_systemBaseTime = $timestamp;
	}
}