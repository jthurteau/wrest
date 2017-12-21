<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for process queuing

*******************************************************************************/

class Saf_Queue {

	protected static $_mode = 'db';
	protected static $_path = NULL;
	protected static $_db = NULL;
	protected static $_statusModel = NULL;
	protected static $_critical = FALSE;

	public static function init($config)
	{
		if (array_key_exists('mode', $config)) {
			if ('db' !== $config['mode']) {
				self::$_mode = $config['mode'];
				throw new Saf_Exception_NotImplemented('Unsupported Queue Mode, ' . $config['mode']);
			}
		} else if (!array_key_exists('db', $config) || !$config['db']) {
			throw new Saf_Exception_NotImplemented('No Queue DB Provided');
		} else if (!array_key_exists('path', $config) || !$config['path']) {
			throw new Saf_Exception_NotImplemented('No Queue DB Tables Provided');
		} else {
			self::$_db = $config['db'];
			self::$_path = $config['path'];
			if (!self::$_db->isConnected()) {
				Saf_Debug::out('Queue DB Unavailable');
			} else {
				$pathString = '(';
				$first = TRUE;
				foreach(self::$_path as $method => $table) {
					$pathString .= ($first ? '' : ', ') . "{$method} : {$table}";
					$first = FALSE;
				}
				$pathString .= ')';
				Saf_Debug::out('Queueing to ' . self::$_db->getHostName() . '/' . self::$_db->getSchemaName() . '/' . $pathString);
			}
		}
	}
	
	public static function pushEmail($when, $payload, $user, $label)
	{
		
	}
	
	public static function pop($count = 1)
	{
		
	}
	
}