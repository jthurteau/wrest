<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for managing framework instances AKA Guru
 */

namespace Saf;

use Saf\Agent\Guru;
use Saf\Agent\Identity;
use Saf\Environment\Access as Environment;
use Saf\Framework\Modes;

require_once(__DIR__ . '/Agent/Guru.php');
require_once(__DIR__ . '/Agent/Identity.php');
require_once(__DIR__ . '/Environment/Access.php');
require_once(__DIR__ . '/Framework/Modes.php');

class Agent implements \ArrayAccess {
	use Guru;
	use Identity;
	use Environment; #NOTE this satisfies the ArrayAccess interface
	use Modes;

	public const OPTION_NAME = 'instanceName';
	public const OPTION_MODE = 'kickstartMode';

	public const MODE_DELIM = '@';
    public const MODE_AUTODETECT = null;
	public const MODE_NONE = 'none';

	public const MEDITATION_KICKSTART = 'KICKSTART_ERROR';
	public const MEDITATION_BOOTSTRAP = 'BOOTSTRAP_ERROR';
	public const MEDITATION_MIDDLEWARE = 'MIDDLEWARE_ERROR';
	public const MEDITATION_REMOTE = 'REMOTE_ERROR';
	public const MEDITATION_SHUTDOWN = 'SHUTDOWN';
	public const MEDITATION_FATAL_EXCEPTION = 'FATAL_EXCEPTION';
	public const MEDITATION_WARNING = 'WARNING';
	public const MEDITATION_NOTICE = 'NOTICE';
	public const MEDITATION_DEBUG = 'DEBUG';
	public const MEDITATION_TIME = 'PROFILE_TIME';
	public const MEDITATION_MEMORY = 'PROFILE_MEMORY';

    protected const DEFAULT_INSTANCE = 'LOCAL_INSTANCE';

	/**
	 * list of potentionally fatal meditations
	 */
	protected static $criticalMeditations = [
		self::MEDITATION_KICKSTART,
		self::MEDITATION_BOOTSTRAP,
		self::MEDITATION_MIDDLEWARE,
		self::MEDITATION_SHUTDOWN,
		self::MEDITATION_FATAL_EXCEPTION,
		//self::MEDITATION_REMOTE,
	];

	/**
	 * list of meditations that should be tracked a certain way
	 */
	protected static $profileMeditations = [
		self::MEDITATION_TIME,
		self::MEDITATION_MEMORY,
		//self::MEDITATION_REMOTE,
	];

	/**
	 * 
	 */
	protected static $idSeed = 0;

	/**
	 * Map of instantiated Agents by $id
	 */
	protected static $references = [];

	/**
	 * Instantiated agents are bound to an enviroment
	 */
	protected $environment = [];

	/**
	* Instantiated agents are bound to an enviroment
	*/
    protected $id = null;

	public bool $active = false;


	public static function availableModes()
	{
		return array_merge(
			[
				self::MODE_AUTODETECT,
				self::MODE_ZFNONE,
			], self::scanFrameworkModes()
		);
	}

//-- instantiated methods

	public function __construct(string $instance, &$environment)
	{
		$this->instance = $instance;
		$this->id = self::agentIdStrategy($instance) . "+{$instance}";
		self::$references[$this->id] = &$this;
		$this->environment = &$environment;
	}

	public function run($manager = null)
	{
		$options = self::duplicate($this->environment);
		$options['agentId'] = $this->id;
		if (is_null($manager)) {
			$modeMain = $this['mainScript'] ?: 'main';
			$installPath = $this['installPath'] ?: '.';
			$mainScript = 
				file_exists("{$installPath}/src/{$modeMain}.php")
				? "{$installPath}/src/{$modeMain}.php"
				: "{$installPath}/{$modeMain}.php";
			#TODO #2.0.0 decide on a mechanism to send parameters to main
			if (!file_exists($mainScript)) {
				throw new \Exception('No main application script to run.');
			} elseif (!is_readable($mainScript)){
				throw new \Exception('Unable to access main application script.');
			}
			$main = require($mainScript);
			$params = key_exists('mainParams', $options) ? $options['mainParams'] : [];
			return is_callable($main) ? $main(...$params) : $main;
		} else {
			return $manager::run($this->id, $options);
		}
	}

//-- required Identity trait methods

    /**
     * returns the instance name option key
     */
    public static function instanceOption()
	{
		return self::OPTION_NAME;
	}

    /**
     * returns the kickstart mode option key
     */
    public static function modeOption()
	{
		return self::OPTION_MODE;
	}
    /**
     * returns the agent's signifier for running with no framework
     */
    public static function noMode()
	{
		return self::MODE_NONE;
	}

    /**
	 * Search all supported modes for the first match that supports $instance with $options
     * returns the agent's signifier for auto-detecting frameworks if no instance specified
	 * @param string $instance
     * @param array $options additional instance options to test compatability
	 * @return string matching mode or auto-detect mode signifier
     */
    public static function autoMode(?string $instance = null, array $options = [])
	{
		if (is_null($instance)) {
			return self::MODE_AUTODETECT;
		}
		foreach(self::detectableFrameworkModes() as $mode) {
			if(self::test($instance, $mode, $options)){ 
				return $mode;
			}
		}
		return self::MODE_NONE;
	}

    /**
     * returns the agent's chosen default instance
     */
    public static function defaultInstance()
	{
		return self::DEFAULT_INSTANCE;
	}

    /**
     * returns the agent mode delimiter
     */
    public static function modeDelim()
	{
		return self::MODE_DELIM;
	}

	/**
	 * generates a unique id for passed meditation
	 */
	protected static function agentIdStrategy(string $instance)
	{
		return ++self::$idSeed;
	}

//-- required Guru trait methods

    /**
     * perform shutdown after a fatal meditation
     */
	protected static function letGo()
	{
		#TODO #2.0.0 decide how to implement.
		die();
	}

	/**
	 * generates a unique id for passed meditation
	 */
	protected static function meditationIdStrategy($e)
	{
		return ++self::$idSeed;
	}

	/**
	 * registers a meditation level, marks it critical unless otherwise specified
	 */
	public static function regiterMeditation(string $level, bool $critical = true)
	{
		if ($critical && !in_array($level, self::$criticalMeditations)) {
			self::$criticalMeditations[] = $level;
		} else {
			self::$profileMeditations[] = $level;
		}
	}

	/*
    * returns the first matching existing script, 
    * scripts should handle Exception $e
    * @return string php script path (absolute or relative) 
    */
	protected static function initMeditation()
	{
		$installPath = defined('INSTALL_PATH') ? INSTALL_PATH : '.'; #TODO this seems old
		$applicationPath = 
			defined('APPLICATION_PATH') 
				? APPLICATION_PATH 
				: ($installPath . "/application");
		$possibilities = [

			"{$installPath}/error.php"
		];
		foreach($possibilities as $path){
			if (file_exists($path)) {
				return $path;
			}
		}
		return realpath("{$applicationPath}/views/scripts/exception.php");
	}

	/**
	 * returns the specified meditation, or the most recent one
	 * @param mixed meditation id
	 * @return array meditation
	 */
	public static function getMeditation($id = null)
	{
		if (!is_null($id) && array_key_exists($id, self::$meditations)) {
			return self::$meditations[$id];
		} elseif (is_null($id) && count(self::$meditations) > 0) {
			return self::$meditations[array_key_last(self::$meditations)];
		}
		return null;
	}

	/**
	 * returns the last reference id
	 */
	public static function last()
	{
		return 
			count(self::$references) > 0 
			? array_keys(self::$references)[count(self::$references) - 1] 
			: null;
	}

	/**
	 * returns instance by reference id
	 */
	public static function lookup($id = null)
	{
		if (is_null($id)) {
			$id = self::last();
		}
		return
			!is_null($id) && key_exists($id, self::$references)
			? self::$references[$id]
			: null;
	}

	/**
	 * 
	 */
	public function env()
	{
		return self::duplicate($this->environment);
	}

	/**
	 * method for applicaion to acknowledge receiving agency from framework
	 */
	public function activate()
	{
		$this->active = true;
	}

	/**
	 * returns is this agent's application has accepted agency
	 */
	public function isActive()
	{
		return $this->active;
	}
}