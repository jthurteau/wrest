<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for starting up an application and preparing the framework.
 * Also provides autoloading.
 * #TODO classify Instances/Modes
 */

namespace Saf;

use Saf\Environment;
use Saf\Agent;

require_once(dirname(__FILE__) . '/Environment.php');
require_once(dirname(__FILE__) . '/Agent.php');

//#TODO #1.0.0 update function header docs
class Kickstart {

	public const OPTION_INSTANCES = Environment::OPTION_INSTANCES;
	public const OPTION_AUTOLOAD = 'autoload';
	public const OPTION_MANAGER = 'managerClass';
	public const OPTION_START_TIME = Environment::OPTION_START_TIME;
	public const OPTION_PUBLIC_PATH = Environment::OPTION_PUBLIC_PATH;
	public const OPTION_INSTALL_PATH = Environment::OPTION_INSTALL_PATH;
	public const PREBOOT_OPTION_LIBXML = 'libxml';
	public const PREBOOT_OPTION_TZ = 'timezone';
	public const PREP_OPTIONS = [
		'applicationEnv' => 'production',
		'applicationId' => '{$instanceName}',
	];

	protected const MEDITATION_LEVEL = Agent::MEDITATION_KICKSTART;

	/**
	 * indicates the mode for which the instance has been prepped
	 * for (e.g. autoload)
	 */
	protected static $laced = [];

	/**
	 * indicates what mode the instance has been kickstarted in
	 * @var array
	 */
	protected static $kicked = [];

	/**
	 * stores state of various preboot steps
	 * false indicates that step will not be auto-attempted
	 * null indicates the step has not been attempted yet
	 * an array of the instance idents that executed the step otherwise
	 */
	protected static $prebootStep = [
		'libxml' => null,
		'timezone' => null,
	];

	/**
	 * Begins the kickstart process, preparing the instance for a framework autowiring
	 * @param string $what instance identifier, [instance@]mode || instance (if no match on mode)
	 * @param array options to initiaize the instance, and environment if not already initilaized
	 * @return string full instance identifier for what was prepared: instance@mode
	 */
	public static function lace(
		?string $what = Agent::MODE_AUTODETECT, 
		array &$options = []
	){
		try {
			$mode = Agent::parseMode($what);
			$instance = Agent::parseInstance($what);
			self::instanceInitialize($instance);
			if (!self::$laced[$instance]) {
				Environment::init($options, $instance);
				if ($mode == Agent::MODE_AUTODETECT) {
					$mode = Agent::autoMode($instance, $options);
				}
				$mode = Agent::negotiate($instance, $mode, $options);
				self::goPreBoot($instance, $mode);
				self::$laced[$instance] = $mode;
			}
			return Agent::instanceIdent($instance, self::$laced[$instance]);
		} catch (\Exception $e) { #TODO handle redirects and forwards
			Agent::meditate($e, self::MEDITATION_LEVEL);
			if (array_key_exists('throwMeditations', $options) && $options['throwMeditations']) {
				throw new \Exception("Failed to prepare {$instance}@{$mode} for kickstart", 0, Agent::getMeditation());
			}
		}
		return Agent::instanceIdent($instance, self::$laced[$instance]);
	}

	/**
	 * perform kickstart based on the provided ident
	 * @param string $instanceIdent (instance@mode)
	 * @return mixed application result (optional)
	 */
	public static function kick(string $instanceIdent)
	{
		try {
			$mode = Agent::parseMode($instanceIdent);
			$instance = Agent::parseInstance($instanceIdent);
			self::instanceInitialize($instance);
			if (!self::$laced[$instance]) {
				throw new \Exception('Requested instance has not been initialized.');
			}
			$options = &Environment::options($instance);
			if ($mode != Agent::MODE_NONE) {
				$modeClass = Environment::instanceOption($instance, self::OPTION_MANAGER);
				if ($modeClass) {
					$requestedModeClass = Agent::getModeClass($mode);
					if ($modeClass != $requestedModeClass) {
						throw new \Exception('Instance has not been configured for the requested mode.');
					} elseif (!class_exists($modeClass, false)) {
						throw new \Exception('Requested mode is not loaded.');
					} else {
						return $modeClass::run($instance, $options);
					}
				}
			} else {
				$modeMain = Environment::instanceOption($instance, 'mainScript', 'main');
				$installPath = Environment::path('install') ?: '.';
				$mainScript = "{$installPath}/{$modeMain}.php";
				if (file_exists($mainScript)) {
					if (!is_readable($mainScript)){
						throw new \Exception('Unable to access main application script.');
					}
					return require_once($mainScript);
				} else {
					throw new \Exception('No application to run.');
				}
			}
		} catch (\Exception $e) { #TODO handle redirects and forwards
			Agent::meditate($e, self::MEDITATION_LEVEL);
			if (array_key_exists('throwMeditations', $options) && $options['throwMeditations']) {
				throw new \Exception("Failed to kickstart instance {$instance}@{$mode}", 0, Agent::getMeditation());
			}
		}
		return null;//isset($result) && is_callable($result) ? $result($options) : $result;
	}	

	/**
	 * Leverage preboot when not using the kickstart process.
	 * @param string $instance
	 * @param string $mode
	 * @param array $options
	 */
	public static function bypass(
		?string $instance = null,
		array $options = [],
		?string $mode = Agent::MODE_NONE
	){
		try {
			$mode = $mode == Agent::MODE_AUTODETECT ? Agent::MODE_NONE : $mode;
			#TODO #2.0.0 Environment::init($intance, $options)?
			self::goPreBoot($instance, $mode);
		} catch (\Exception $e) { #TODO handle redirects and forwards
			Agent::meditate($e, self::MEDITATION_LEVEL);
		}
	}

	/**
	 * Convenience function, laces the default-instance and kicks it
	 */
	public static function go(?array $options)
	{
		self::kick(self::lace(null, $options));
	}

	/**
	 * @return string the mode the application was started in
	 */
	public static function getMode(?string $instance = null)
	{
		if (is_null($instance)) {
			$instance = Agent::DEFAULT_INSTANCE;
		}
		return 
			array_key_exists($instance, self::$kicked) 
			? self::$kicked[$instance] 
			: null;
	}

	/**
	 * @return array lists the known instances
	 */
	public static function getInstances()
	{
		return array_keys(self::$kicked);
	}

	
	/**
	 * steps that can't wait for a bootstrap to kick in, 
	 * @param string $instance
	 * @param string $mode
	 */
	protected static function goPreboot(?string $instance, ?string $mode)
	{
		$mode = $mode == Agent::MODE_AUTODETECT ? Agent::MODE_NONE : $mode;
		$options = &Environment::options($instance);
		Environment::prep($instance, self::PREP_OPTIONS); #TODO #2.0.0 expand Environment::parse so it can handle managerClass, autoload, etc.
		Environment::set($instance, 'resolution', Agent::resolve($instance, $options));
		if (
			$mode != Agent::MODE_NONE
			&& is_null(Environment::instanceOption($instance, self::OPTION_MANAGER))
		) {
			Environment::set($instance, self::OPTION_MANAGER, Agent::getModeClass($mode));
		}
		if (is_null(Environment::instanceOption($instance, self::OPTION_AUTOLOAD))) {
			Environment::set($instance, self::OPTION_AUTOLOAD, Environment::DEFAULT_AUTOLOAD);
		}
		foreach(self::$prebootStep as $prebootOption => $value) {
			if (is_null($value)) {
				self::prebootStep($prebootOption, $instance, $mode);
			}
		}
		$modeClass = Environment::instanceOption($instance, self::OPTION_MANAGER);
		if ($modeClass) {
			Environment::autoload($modeClass);
			if (Environment::instanceOption($instance, self::OPTION_AUTOLOAD)){
				$modeClass::autoload($instance, $options);
			}
			$modeClass::preboot($instance, $options, self::$prebootStep);
		}
	}

	/**
	 * Handling for various preboot steps, #TODO #2.0.0 candidate to move into Agent? Framework/$Mode
	 * @param string $step matching PREBOOT_OPTION_*
	 * @param string $instance 
	 * @param string $mode
	 */
	protected static function prebootStep($step, $instance = 'INTERNAL', $mode = 'INIT')
	{
		switch ($step) {
			case self::PREBOOT_OPTION_LIBXML :
				if (function_exists('libxml_use_internal_errors')) {
					libxml_use_internal_errors(true);
				} else {
					Agent::mediate('libxml_use_internal_errors() not supported.', 'NOTICE');
				}
				break;
			case self::PREBOOT_OPTION_TZ :
				if (defined('DEFAULT_TIMEZONE')) {
					date_default_timezone_set(DEFAULT_TIMEZONE);
				}				
				break;
			default:
			#TODO implement plugins?
		}
		if (!array_key_exists($step, self::$prebootStep) || !self::$prebootStep[$step]) {
			self::$prebootStep[$step] = [Agent::instanceIdent($instance, $mode)];
		} else {
			self::$prebootStep[$step][] = Agent::instanceIdent($instance, $mode);
		}
	}

	/**
	 * handles any internal initialization for referenced instances
	 * @param string instance to initialize
	 */
	protected static function instanceInitialize($instance)
	{
		if (!array_key_exists($instance, self::$kicked)) {
			self::$kicked[$instance] = false;
			self::$laced[$instance] = false; 
		}
	}
}