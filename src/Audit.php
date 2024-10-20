<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for auditing activity
 */

namespace Saf;

use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
use Saf\Debug;
use Saf\Cache;
use Saf\Utils\Time;
use Saf\Exception\NotImplemented;
use Saf\Pdo;
use Saf\Pdo\Db;

class Audit
{
	public const MODE_DB = 'db';
	public const MODE_FILE = 'file';
	public const MODE_BACKEND = 'back';

	public const DEFAULT_SEARCH_LIMIT = 1000;

	protected static $supportedModes = [self::MODE_DB];
	protected static $mode = self::MODE_DB;
	protected static $path = null;
	protected static $db = null;
	protected static $statusModel = null;
	protected static $critical = false;
	# protected static $insulated = false; //#TODO add a time insulation config option

    public function __invoke(ContainerInterface $container, string $name, callable $callback) : Object
    {
		$config = Container::getOptional($container, ['config','audit'], ['path' => 'audit']);
		if (
			!key_exists('mode', $config)
			|| $config['mode'] == self::MODE_DB
		) {
			$config['db'] = Container::getOptionalService($container, Db::class); //#TODO if key_exists and is string, use that class/service from the container
		}
        self::init($config);
        return $callback();
    }

	public static function init($config)
	{
		if (key_exists('mode', $config)) {
			if (!in_array($config['mode'], self::$supportedModes)) {
				self::$mode = $config['mode'];
				throw new NotImplemented("Unsupported Audit Mode, \"{$config['mode']}\"");
			}
		} elseif (!key_exists('db', $config) || !$config['db']) {
			throw new NotImplemented('No Audit DB Provided');
		} elseif (!key_exists('path', $config) || !$config['path']) {
			throw new NotImplemented('No Audit DB Table Provided');
		} else {
			self::$db = $config['db'];
			self::$path = $config['path'];
			if (
				!self::$db 
				|| !is_object(self::$db)
				|| !method_exists(self::$db, 'isConnected') #TODO implement a db/file/backend API interface
				|| !self::$db->isConnected()
			) {
				Debug::out('Audit DB Unavailable');
			} else {
				Debug::out('Auditing to ' 
					. self::$db->getHostName() 
					. '/' . self::$db->getSchemaName() 
					. '/' . self::$path
				);
			}
		}
	}

	public static function available()
	{
		return !is_null(self::$db);
	}

	protected static function auditFail($additional = '')
	{
		if (trim($additional) != '') {
			$additional = " {$additional}";
		}
		$count = Cache::get('auditFailCount', null);
		if (is_null($count)) {
			$count = 0;
		}
		Cache::store('auditFailCount', ++$count);
		Debug::outData(['failed to audit activity.' . $additional, self::$db?->getErrorMessage(), self::$db]);
	}

	public static function add($classification, $message = null, $request = null, $user = null)
	{
		try {
			if (!self::$db) {
				throw new \Exception('Audit not initialized');
			}
			$table = self::$path;
			if (is_null($classification) || '' == trim($classification)) {
				throw new \Exception('Invalid Audit Classification');
			}
			$cols = '`when`, `classification`';
			$values =
				Pdo::escapeString(date(Time::FORMAT_DATETIME_DB)) //#NOTE don't insulate the timestamp
				. ', ' . Pdo::escapeString(trim($classification));
			if (!is_null($message)) {
				$cols .= ', `message`';
				$values .= ', ' . Pdo::escapeString(trim($message));
			}
			$remote = [];
			if (key_exists('HTTP_USER_AGENT', $_SERVER)) { //#NOTE not currentl PSR7 insulated
				$remote['agent'] = $_SERVER['HTTP_USER_AGENT'];
			}
			if (key_exists('REMOTE_ADDR', $_SERVER)) {
				$remote['addr'] = $_SERVER['REMOTE_ADDR'];
			}
			if (key_exists('HTTP_REFERER', $_SERVER)) {
				$remote['ref'] = $_SERVER['HTTP_REFERER'];
			}
			if (!is_null($request)) {
				if (is_object($request)) {
					$remote['uri'] = $request->getRequestUri();
					$remote['method'] = $request->getMethod();
					$remote['post'] = $request->getPost();
				} else if (is_array($request)){
					$remote['raw'] = json_encode($request, JSON_FORCE_OBJECT); //Saf_Array::toString($request);
				} else {
					$remote['raw'] = trim($request);
				}
			} else {
				if (key_exists('REQUEST_URI', $_SERVER)) {
					$remote['uri'] = $_SERVER['REQUEST_URI'];
				}
				if (key_exists('REQUEST_METHOD', $_SERVER)) {
					$remote['method'] = $_SERVER['REQUEST_METHOD'];
				}
				if (is_array($_POST)) {
					$remote['post'] = $_POST;
				}
			}
			$cols .= ', `request`';
			$requestString = json_encode($remote);
			$values .= ', ' . Pdo::escapeString($requestString);
			if (!is_null($user)) {
				$cols .= ', `username`';
				$values .= ', ' . Pdo::escapeString(trim($user));
			}
            $insert = "INSERT INTO {$table} ({$cols}) VALUES ({$values});";
            $result = self::$db->query()->insert($insert);
			if (!$result) {
				self::auditFail('query fail.');
			}
			return $result;
		} catch (\Error | \Exception $e){
			if (self::$critical) {
				throw($e);
			} else {
				self::auditFail($e->getMessage());
			}
		}
		return null;
	}

	public static function update($id, $request = null, $user = null)
	{
		try {
			if (!self::$db) {
				throw new \Exception('Audit not initialized');
			}
			$table = self::$path;
			if (is_null($id) || 0 == (int)$id) {
				throw new \Exception('Invalid Audit ID');
			}
			$id = (int)$id;
			$remote = [];
			if (key_exists('HTTP_USER_AGENT', $_SERVER)) {
				$remote['agent'] = $_SERVER['HTTP_USER_AGENT'];
			}
			if (key_exists('REMOTE_ADDR', $_SERVER)) {
				$remote['addr'] = $_SERVER['REMOTE_ADDR'];
			}
			if (key_exists('HTTP_REFERER', $_SERVER)) {
				$remote['ref'] = $_SERVER['HTTP_REFERER'];
			}
			if (!is_null($request)) {
				if (is_object($request)) {
					$remote['uri'] = $request->getRequestUri();
					$remote['method'] = $request->getMethod();
					$remote['post'] = $request->getPost();
				} else {
					$remote['raw'] = trim($request);
				}
			} else {
				if (key_exists('REQUEST_URI', $_SERVER)) {
					$remote['uri'] = $_SERVER['REQUEST_URI'];
				}
				if (key_exists('REQUEST_METHOD', $_SERVER)) {
					$remote['method'] = $_SERVER['REQUEST_METHOD'];
				}
				if (is_array($_POST)) {
					$remote['post'] = $_POST;
				}
			}
			$cols = ' `when` = '
				. Pdo::escapeString(date(Time::FORMAT_DATETIME_DB)); //#NOTE don't insulate the timestamp
			$requestString = json_encode($remote);
			$cols .= ', `request` = ' . Pdo::escapeString($requestString);
			if (!is_null($user)) {
				$cols .= ', `username` = ' . Pdo::escapeString(trim($user));
			}
			$update = "UPDATE {$table} SET {$cols} WHERE `id` = {$id};";
			$result = self::$db->query()->update($update);
			if (!$result) {
				$count = Cache::get('auditFailCount', null);
				if (is_null($count)) {
					$count = 0;
				}
				Cache::store('auditFailCount', ++$count);
				Debug::outData(['failed to audit activity', self::$db->getErrorMessage(),self::$db]);
			}
			return $result;
		} catch (\Exception $e){
			if (self::$critical) {
				throw($e);
			} else {
				Debug::outData(['failed to audit activity', self::$db->getErrorMessage(),self::$db]);
			}
		}
		return null;
	}

	public static function addOnce($classification, $message = null, $request = null, $user = null)
	{
		try {
			if (!self::$db) {
				throw new \Exception('Audit not initialized');
			}
			$table = self::$path;
			if (is_null($classification) || '' == trim($classification)) {
				throw new \Exception('Invalid Audit Classification');
			}
			$where =
				'`classification` = ' . Pdo::escapeString(trim($classification));
			if (!is_null($message)) {
				$where .= ' AND `message` = ' . Pdo::escapeString(trim($message));
			}
			if (!is_null($user)) {
				$where .= ' AND `user` = ' . Pdo::escapeString(trim($user));
			}
			$query = "SELECT `id` FROM {$table} WHERE {$where}";
			$result = self::$db->query($query)->all();
			if (!count($result)){
				return self::add($classification, $message, $request, $user);
			} else {
				$id = $result[0]['id'];
				return self::update($id, $request, $user);
			}
			return $result;
		} catch (\Exception $e){
			if (self::$critical) {
				throw($e);
			} else {
				Debug::outData(['failed to audit activity', self::$db->getErrorMessage(),self::$db]);
			}
		}
		return null;
	}

	public static function exceptionMessage($exception, $withTrace = true)
	{
		if ($exception) {
			if (!is_object($exception)) {
				return 'Non-Object ' .gettype($exception);
			} else if (!is_a($exception,'Exception')) {
				return 'Non-Exception ' . get_class($exception);
			} else {
				$additional = '';
				if ($exception->getPrevious()) {
					$additional = ' (' . self::exceptionMessage($exception->getPrevious(), false) . ')';
				}
				if ($withTrace) {
					$additional .= "\n" . $exception->getTraceAsString();
				}
				return get_class($exception) . ' ' . $exception->getMessage() . $additional;
			}
		} else {
			return 'none';
		}
	}

	public static function search($params, $limit = self::DEFAULT_SEARCH_LIMIT, $page = 0)
	{
		if (!self::$db) {
			throw new \Exception('Audit not initialized');
		}
		$table = self::$path;
		$defaultStart = Time::modify(Time::time(), Time::MODIFIER_SUB_WEEK);
		$defaultEnd = Time::modify(Time::time(), Time::MODIFIER_END_DAY) - 1;
		$start =
			key_exists('when', $params)
			? (
				is_array($params['when'])
				? (
					key_exists(0, $params['when'])
					? $params['when'][0]
					: $defaultStart
				)
				: is_array($params['when'])
			)
			: $defaultStart;
		$end = 
			key_exists('when', $params)
			? (
				is_array($params['when'])
				? (
					key_exists(1, $params['when'])
					? $params['when'][1]
					: $defaultEnd
				)
				: is_array($params['when'])
			)
			: $defaultEnd;
		$startDate = date(Time::FORMAT_DATETIME_DB, $start);
		$endDate = date(Time::FORMAT_DATETIME_DB, $end);
		$classification = key_exists('classification', $params) ? $params['classification'] : null;
		$message = key_exists('message', $params) ? $params['message'] : null;
		$agent = key_exists('agent', $params) ? $params['agent'] : null;
		$uri = key_exists('uri', $params) ? $params['uri'] : null;
		$ip = key_exists('ip', $params) ? $params['ip'] : null;
		$payload = key_exists('payload', $params) ? $params['payload'] : null;
		$sort = key_exists('sort', $params) ? $params['sort'] : 'asc';
		$where = "`when` >= '{$startDate}' AND `when` <= '{$endDate}'";
		if (!is_null($classification)) {
			if (is_array($classification)) {
				$classWhere = ' AND `classification` IN (';
				$classCount = 0;
				$first = TRUE;
				foreach($classification as $class) {
					if ($class == '*') {
						$classCount = 0;
						break;
					} else if ($class != '') {
						$classWhere .= ($first ? '' : ',') . Pdo::escapeString($class);
						$first = FALSE;
						$classCount++;
					}
				}
				$classWhere .= ')';
				if ($classCount) {
					$where .= $classWhere;
				}
			} else if ($classification != '*' && $classification != '') {
				$where .= ' AND `classification` = ' . Pdo::escapeString(trim($classification));
			}
		}
		if (!is_null($message) && trim($message) != '') {
			if (strpos($message, '%') === FALSE) {
				$message = "%{$message}%";
			}
			$where .= ' AND `message` LIKE ' . Pdo::escapeString(trim($message));
		}
		if (!is_null($agent) && trim($agent) != '') {
			$agent = substr(json_encode($agent), 1, -1);
			$agent = "%\"agent\":\"{$agent}%";
			$where .= ' AND `request` LIKE ' . Pdo::escapeString(trim($agent));
		}
		if (!is_null($uri) && trim($uri) != '') {
			$uri = substr(json_encode($uri), 1, -1);
			$uri = "%\"uri\":\"{$uri}%";
			$where .= ' AND `request` LIKE ' . Pdo::escapeString(trim($uri));
		}
		if (!is_null($ip) && trim($ip) != '') {
			$ip = substr(json_encode($ip), 1, -1);
			$ip = "%\"addr\":\"{$ip}%";
			$where .= ' AND `request` LIKE ' . Pdo::escapeString(trim($ip));
		}
		if (!is_null($payload) && trim($payload) != '') {
			$escapedPayload = str_replace(['%', '"', '/'], ['\%', '\"', '\/'], $payload);
			$matchPayload = '%' . $escapedPayload . '%';
			//$where .= ' AND `request` LIKE ' . Saf_Pdo_Connection::escapeSpecialString(trim($payload));
			$where .= ' AND `request` LIKE ' . Pdo::escapeString(trim($matchPayload)); #TODO fix or deprecate escapeSpecialString()
			//$where2 = ' AND `request` LIKE ' . Saf_Pdo_Connection::escapeString(trim($matchPayload));
		}
		switch ($sort) {
			case 'asc':
				$sortString = '`when` ASC';
				break;
			case 'desc':
				$sortString = '`when` DESC';
				break;
			case 'user':
				$sortString = '`username` ASC';
				break;
			default:
				$sortString = '`id`';
		}
		$limit = Pdo::escapeInt($limit);
		$page = Pdo::escapeInt($page);
		$limitString = "LIMIT {$limit} OFFSET {$page}";
		$select = "SELECT * FROM {$table} WHERE {$where}";
		$countQuery = "SELECT COUNT(`id`) FROM {$table} WHERE {$where} {$limitString}";
//		$query2 = $query . $where2 . " ORDER BY {$sortString} {$limitString}";
        $select .= " ORDER BY {$sortString} {$limitString}";
		Debug::outData(['query', $select]);
		$result = self::$db->query($select)->all();
//		$result2 = self::$_db->all(self::$_db->query($query2));
		if (is_null($result)) {
			$return = ['success' => false];
			if (Debug::isEnabled()) {
				$return['query'] = $query;
			}
			return $return;
		}
		// if (!is_null($payload) && trim($payload) != '') {
		// 	print_r([__FILE__,__LINE__,$result, $result2, $where2, $payload, $escapedPayload, $matchPayload]); die;
		// }
		$countResult = self::$db->query($countQuery)->one();
		return [
			'success' => true, 
			'recordCount' => count($result), 
			'totalCount' => $countResult, 
			'records' => $result
		];
	}

	public static function getCurrentClassifications()
	{
		if (!self::$db) {
			throw new \Exception('Audit not initialized');
		}
		$values = [];
		$table = self::$path;
		$select = "SELECT DISTINCT classification FROM {$table};";
		$result = self::$db->query($select)->all();
		if (!$result) {
			Debug::outData(['failed to get audit classifications', self::$db->getErrorMessage(),self::$db]);
		}
		foreach($result as $record) {
			if ($record && key_exists('classification',$record)) {
				$values[] = $record['classification'];
			}
		}
		return $values;
	}

    public static function enterCriticalPath(): void
    {
        self::$critical = true;
    }

    public static function exitCriticalPath(): void
    {
        self::$critical = false;
    }

    public static function getThumbprint(): string
    {
        switch (self::$mode) {
            case self::MODE_DB:
                $host = '_';
                $schema = '_';
                $table = '_';
                if (self::$db) {
                    $host = self::$db->getHostName();
                    $schema = self::$db->getSchemaName();
                    $table = self::$path;
                }
                return "[db:{$host}/{$schema}/{$table}]";
            case null:
                return '[not configured]';
        }
    }

}