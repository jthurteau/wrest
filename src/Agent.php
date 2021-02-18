<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for managing framework instances AKA Guru
 */

namespace Saf;

use Saf\Auto;
use Saf\Resolver;
use Saf\Agent\Meditation;
//use Saf\Framework\Manager;

require_once(dirname(__FILE__) . '/Auto.php');
require_once(dirname(__FILE__) . '/Resolver.php');
require_once(dirname(__FILE__) . '/Agent/Meditation.php');
//require_once(dirname(__FILE__) . '/Framework/Manager.php');

abstract class Agent{

    public const MODE_AUTODETECT = null;
	public const MODE_ZFMVC = 'zendmvc'; //NOTE deprecated in 2.0
	public const MODE_ZFNONE = 'zendbare'; //NOTE deprecated in 2.0
	public const MODE_SAF = 'saf';
	public const MODE_SAF_LEGACY = 'saf-legacy';
	public const MODE_MEZ = 'mezzio';
	public const MODE_LAMMVC = 'laminas-mvc'; //#TODO #2.1.0 support Laravel
	public const MODE_LF5 = 'laravel5'; //#TODO #2.1.0 support Laravel
	public const MODE_SLIM = 'slim'; //#TODO #2.1.0 support Slim
	public const MODE_NONE = 'none';

    protected const DEFAULT_INSTANCE = 'LOCAL_INSTANCE';

	protected static $_path = '.';

	public const MEDITATION_KICKSTART = 'KICKSTART_ERROR';
	public const MEDITATION_BOOTSTRAP = 'BOOTSTRAP_ERROR';
	public const MEDITATION_MIDDLEWARE = 'MIDDLEWARE_ERROR';
	public const MEDITATION_REMOTE = 'REMOTE_ERROR';
	public const MEDITATION_WARNING = 'WARNING';
	public const MEDITATION_NOTICE = 'NOTICE';
	public const MEDITATION_TIME = 'PROFILE_TIME';
	public const MEDITATION_MEMORY = 'PROFILE_MEMORY';


    /**
	 * specifies the path to the exception display view script
	 * defaults to (set to) APPLICATION_PATH . '/views/scripts/error/error.php'
	 * the first time exceptionDisplay() is called if not already set by
	 * setExceptionDisplayScript().
	 * @var string
	 */
	protected static $meditationView = null;

	/**
	 * stack of stored (non-critical meditations)
	 */
	protected static $meditations = [];

	/**
	 * 
	 */
	protected static $criticalMeditations = [
		self::MEDITATION_KICKSTART,
		self::MEDITATION_BOOTSTRAP,
		self::MEDITATION_MIDDLEWARE,
		//self::MEDITATION_REMOTE,
	];

	/**
	 * 
	 */
	protected static $profileMeditations = [
		self::MEDITATION_TIME,
		self::MEDITATION_MEMORY,
		//self::MEDITATION_REMOTE,
	];

	protected static $outputMeditations = false;

	protected static $idSeed = 0;

	/**
	 * Outputs in the case of complete and total failure during the kickstart process.
	 * @param mixed $e \Exception, error string, or dump array
	 * @param string $level error level
	 */
	public static function meditate($e, $level = self::MEDITATION_NOTICE) #TODO add interface for detailed exceptions, $additionalError = '')
	{

        if (!is_a($e, '\Exception')) {
            if (is_string($e)) {
                $meditationText = $e;
				$e = null;
            } else {
				$meditationText = 'Data Meditation';
                $e = new \Exception(Auto::meditate($e));
            }
        } else {
			$meditationText = 'Exception Meditation';
		}
		if (self::$outputMeditations && in_array($level, self::$criticalMeditations)) {
			$rootUrl = defined('\APPLICATION_BASE_URL') ? \APPLICATION_BASE_URL : ''; #TODO #2.0.0 cleanup
			$title = 'Configuration Error';
			if (is_null(self::$meditationView)) {
				self::$meditationView = self::findMeditation();
			}
			if (self::$meditationView) {
				include(self::$meditationView);
			} else {
				header('HTTP/1.0 500 Internal Server Error');
				$e->getMessage();
			}
			self::dieSafe();
		} else {
			$m = new Meditation($meditationText, self::idStrategy($e), $e);
			self::$meditations[$m->getCode()] = $m;
		}

	}

	/**
	 * sets the path to the php script used by exceptionDisplay()
	 * @param string $path
	 */
	public static function setMeditation($path)
	{
		self::$meditationView = realpath($path);
	}

    /**
	 * returns the first matching existing script, 
	 * scripts should handle Exception $e
	 * @return string php script path (absolute or relative) 
	 */
	protected static function findMeditation()
	{
		$installPath = defined('INSTALL_PATH') ? INSTALL_PATH : '.';
		$applicationPath = 
			defined('APPLICATION_PATH') 
				? APPLICATION_PATH 
				: (INSTALL_PATH . "/application");
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
	 * registers a meditation level, marks it critical unless otherwise specified
	 */
	public static function regiterMeditation(string $level, bool $critical = true)
	{
		if ($critical && !in_array($level, self::$criticalMeditations)) {
			self::$criticalMeditations[] = $level;
		}
	}

	/**
	 * Search all supported modes for the first match that supports $instance
	 * @param string $instance
     * @param array $options additional instance options to test compatability
	 * @return string matching mode
	 */
	public static function autoMode($instance, $options = [])
	{
		foreach(self::detectableModes() as $mode) {
			if(self::test($instance, $mode, $options)){ 
				return $mode;
			}
		}
		return self::MODE_NONE;
	}

    /**
	 * @param string mode name of framework kickstarting mode
	 * @return string class for the mode manager
	 */
	public static function getModeClass($mode)
	{
		$classString = str_replace(' ', '', ucwords(str_replace('-', ' ', $mode))); #TODO externalize this to Auto
		$namespaceString = __NAMESPACE__;
		return "{$namespaceString}\\Framework\\{$classString}";
	}

    /**
     * Tests $mode for compatability with $instance
	 * @param string $instance 
	 * @param string $mode mode name of framework kickstarting mode
     * @param array $options additional instance options to test for compatability with $mode
	 * @return bool true if $mode seems applicable to $instance
	 */
	public static function test($instance, $mode, $options = [])
	{
        #TODO look for cached answers to this question since it's intensive
		$modeClass = self::getModeClass($mode);
		if (!class_exists($modeClass ,false)) {
			$modeFile = Auto::classPathLookup($modeClass);
			if (file_exists($modeFile) && is_readable($modeFile)) {
				require_once($modeFile);
				return $modeClass::detect($instance, $options);
			} //#TODO #.2.0.0 else non-critical meditation
		}
		#TODO cacheMode($mode, $instance);
		return false;
	}

    public static function instanceIdent(string $instance, $mode)
    {
		if ($mode === false) {
			$mode = '';
		}
        return "{$instance}@{$mode}";
    }

    /**
	 * returns the mode name for a given instance specification
	 * @param string $mode to parse for mode name
	 * @return string
	 */
	public static function parseMode($mode)
	{
		if (is_string($mode)) {
			$modeDelimPosition = strpos($mode, '@');
			if ($modeDelimPosition) {
				$mode = substr($mode, $modeDelimPosition + 1);
				return in_array($mode, self::availableModes()) ? $mode : self::MODE_NONE;
			}
			return in_array($mode, self::availableModes()) ? $mode : self::MODE_AUTODETECT;
		}
		return self::MODE_AUTODETECT;
	}

	/**
	 * returns the instance name for a given instance specification
	 * @param string $instance to parse for instance name
	 * @return string
	 */
	public static function parseInstance($instance)
	{
		if (is_string($instance)) {
			$modeDelimPosition = strpos($instance, '@');
			if ($modeDelimPosition) {
				$instance = substr($instance, 0, $modeDelimPosition);
				return $instance ? $instance : self::DEFAULT_INSTANCE;
			}
			return !in_array($instance, self::availableModes()) ? $instance : self::DEFAULT_INSTANCE;
		}
		return self::DEFAULT_INSTANCE;
	}

    public static function detectableModes()
	{
		return [
			self::MODE_SAF,
			self::MODE_SAF_LEGACY,
			self::MODE_MEZ,
			self::MODE_LAMMVC,
			self::MODE_LF5,
			self::MODE_SLIM,
            self::MODE_ZFMVC, #NOTE this is supported under MODE_SAF_LEGACY
		];
	}

	public static function availableModes()
	{
		return array_merge(
            [
                self::MODE_AUTODETECT,
                self::MODE_ZFNONE,
            ], self::detectableModes()
        );
	}

	/**
	 * assesses an instance and returns a different mode if it can be better supported
	 * may alter the passed instance $options to make transitions possible
	 * @param string $instance name
	 * @param string $mode requested
	 * @param array $options
	 * @return string supported alternative to $mode
	 */
	public static function negotiate($instance, $mode, &$options = [])
	{
		$modeClass = self::getModeClass($mode);
		return $modeClass::negotiate($instance, $mode, $options);
	}

	/**
	 * returns a resolver to help other frameworks route cases not natively 
	 * supported (like multi-views)
	 */
	public static function resolve($instance, $options)
	{
		return Resolver::init($instance, $options);
	}

	public static function getMeditation($id = null)
	{
		if (!is_null($id) && array_key_exists($id, self::$meditations)) {
			return self::$meditations[$id];
		} elseif (is_null($id)) {
			return self::$meditations[array_key_last(self::$meditations)];
		}
		return null;
	}

	protected static function dieSafe()
	{
		#TODO #2.0.0 decide how to implement.
		die();

	}

	protected static function idStrategy($e)
	{
		return ++self::$idSeed;
	}

}