<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for starting up applications and preparing frameworks.
 */

namespace Saf;

use Saf\Environment;
use Saf\Preboot;
use Saf\Agent;

require_once(dirname(__FILE__) . '/Environment.php');
require_once(dirname(__FILE__) . '/Preboot.php');
require_once(dirname(__FILE__) . '/Agent.php');

class Kickstart
{
	/**
	 * stores order and state of various preboot steps
	 * false indicates that step will not be auto-attempted
	 * null indicates the step has not been attempted yet
	 * otherwise each value is an array of the instance idents that executed the step
	 */
	protected static $prebootSteps = [
		self::PREBOOT_STEP_LIBXML => null,
		self::PREBOOT_STEP_TZ => null,
	];
	public const PREBOOT_STEP_LIBXML = 'LibXml';
	public const PREBOOT_STEP_TZ = 'Timezone';

	public const OPTION_NAME = Agent::OPTION_NAME;
	public const OPTION_MODE = Agent::OPTION_MODE;
	public const OPTION_START_TIME = Environment::OPTION_START_TIME;
	public const OPTION_PUBLIC_PATH = Environment::OPTION_PUBLIC_PATH;
	public const OPTION_INSTALL_PATH = Environment::OPTION_INSTALL_PATH;
	public const OPTION_INSTANCES = Environment::OPTION_INSTANCES;
	public const OPTION_AUTOLOAD = 'autoload';
	public const OPTION_MANAGER = 'managerClass';
	public const OPTION_THROW_MEDITATIONS = 'throwMeditations';
	public const OPTION_RETURN_EXCEPTIONS = 'returnExceptions';
	public const OPTION_RESOLVER = 'resolution';
	public const INSTANCE_DEFAULTS = [
		'applicationEnv' => 'production',
		'applicationId' => '{$instanceName}',
	];

	protected const MEDITATION_LEVEL = Agent::MEDITATION_KICKSTART;

	/**
	 * indicates what mode the instances have been prepared for
	 */
	protected static $laced = [];

	/**
	 * indicates what mode the instances has been kickstarted in
	 */
	protected static $kicked = [];

	/**
	 * Begins the kickstart process, preparing the instance for a framework autowiring
	 * @param null|string $instanceIdent instance identifier, instance@mode || mode || instance
	 * @param array options to initiaize the instance, and global environment if not already initilaized
	 * @return string full instance identifier for what was prepared: instance@mode
	 */
	public static function lace(
		?string $instanceIdentifier = Agent::MODE_AUTODETECT,
		array &$options = []
	){
		try {
			$mode = Agent::parseMode($instanceIdentifier, $options);
			$instance = Agent::parseInstance($instanceIdentifier, $options);
			self::instanceInitialize($instance);
			if (!self::$laced[$instance]) {
				Environment::init($options, $instance);
				if ($mode == Agent::MODE_AUTODETECT) {
					$mode = Agent::autoMode($instance, $options);
				}
				$mode = Agent::negotiate($instance, $mode, $options) ?: $mode;
				self::preboot($instance, $mode);
				self::$laced[$instance] = $mode;
			}
		} catch (\Exception $e) {
			Agent::meditate($e, self::MEDITATION_LEVEL, $instance); #TODO #2.0.0 staticMeditate
			if (Environment::instanceOption($options, self::OPTION_THROW_MEDITATIONS)) {
				#TODO #2.0.0 get the correct deilm
				throw new \Exception("Failed to prepare {$instance}@{$mode} for kickstart", 0, Agent::getMeditation());
			}
		}
		return Agent::instanceIdent($instance, self::$laced[$instance]);
	}

	/**
	 * perform kickstart based on the provided instance identifier
	 * matches on both only if both provided, otherwise the first match on any criteria provided
	 * @param string $instanceIdentifier (instance@mode)
	 * @return mixed application result (optional)
	 */
	public static function kick(string $instanceIdentifier)
	{
		try {
			$instance = Agent::parseInstance($instanceIdentifier);
			self::instanceInitialize($instance);
			$modeRequested = Agent::parseMode($instanceIdentifier);
			if (!self::$laced[$instance]) {
				throw new \Exception('Requested instance has not been configured.');
			} elseif (
				$modeRequested != self::$laced[$instance]
				&& $modeRequested != self::MODE_NONE 
			) {
				throw new \Exception('Instance has not been configured for the requested mode.');
			}
			return 
				$modeRequested == Agent::MODE_NONE 
				? self::main($instance) 
				: self::run($instance, $modeRequested);
	 		//#TODO #2.0.0 handle redirects and forwards, but maybe inside an agent method to simplify this class?
			// } catch (Saf\Exception\Assist $e){
			// } catch (Saf\Exception\Public $e){
			// } catch (Saf\Exception\Redirect $e){
			// } catch (Saf\Exception\Workflow $e){
		} catch (\Exception $e) { #TODO #2.0.0 handle redirects and forwards
			Agent::meditate($e, self::MEDITATION_LEVEL);
			if (Environment::instanceOption($instance, self::OPTION_THROW_MEDITATIONS)) {
				#TODO #2.0.0 get the correct deilm
				throw new \Exception("Failed to kickstart instance {$instance}@{$modeRequested}", 0, Agent::getMeditation());
			} elseif (Environment::instanceOption($instance, self::OPTION_RETURN_EXCEPTIONS)) {
				return $e;
			}
		}
		return null;
	}	

	/**
	 * Leverage preboot when not using the kickstart process.
	 * @param array $options
	 * @param string $instance
	 * @param string $mode
	 */
	public static function bypass(
		array $options = [],
		?string $instance = null,
		?string $mode = Agent::MODE_NONE
	){
		try {
			$mode = $mode == Agent::MODE_AUTODETECT ? Agent::MODE_NONE : $mode;
			
			#TODO #2.0.0 Environment::init($intance, $options)?
			self::preboot($instance, $mode);
		} catch (\Exception $e) { #TODO handle redirects and forwards
			Agent::meditate($e, self::MEDITATION_LEVEL);
		}
	}

	/**
	 * Convenience function, laces the default or option-set instance in AUTO_MODE and kick it
	 * @param array $options
	 * @return string instance ident
	 */
	public static function go(array $options = [])
	{
		self::kick(self::lace(null, $options));
	}

	/**
	 * returns the mode an instance was kickstarted in, if the instance identifier
	 * isn't specific it will return the mode for the default instance
	 * @param string $instance name or instance identifier
	 * @return string the mode the application was started in
	 */
	public static function getMode(?string $instance = null)
	{
		if (is_null($instance)) {
			$instance = Agent::defaultInstance();
		}
		$instance = Agent::getInstance($instance);
		return 
			array_key_exists($instance, self::$kicked) 
			? self::$kicked[$instance] 
			: null;
	}

	/**
	 * @return array lists the known instances
	 */
	public static function getInstances($kickedOnly = false)
	{
		return array_keys($kickedOnly ? self::$kicked : self::$laced);
	}

	/**
	 * get a (dereferenced) copy of the current options for $instance
	 * @param string $instance name or full instance ident
	 * @return array copy of current instance options
	 */
	public static function getOptions(string $instance)
	{
		$options = Environment::options($instance);
		return $options;
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

	/**
	 * steps that can't wait for a bootstrap to kick in, 
	 * @param string $instance
	 * @param string $mode
	 */
	protected static function preboot(?string $instance, ?string $mode)
	{
		$mode = $mode == Agent::MODE_AUTODETECT ? Agent::MODE_NONE : $mode;
		$options = &Environment::options($instance);
		Environment::expand($instance, self::INSTANCE_DEFAULTS); #TODO #2.0.0 expand Environment::parse so it can handle managerClass, autoload, etc.
		Environment::set($instance, self::OPTION_RESOLVER, Preboot::resolve($instance, $options));
		if (array_key_exists('meditationScript', $options)) {
			Agent::setMeditation($options['meditationScript']);
		}
		if (
			$mode != Agent::MODE_NONE
			&& is_null(Environment::instanceOption($instance, self::OPTION_MANAGER))
		) {
			Environment::set($instance, self::OPTION_MANAGER, Agent::getModeClass($mode));
		}
		if (is_null(Environment::instanceOption($instance, self::OPTION_AUTOLOAD))) {
			Environment::set($instance, self::OPTION_AUTOLOAD, Environment::DEFAULT_AUTOLOAD);
		}
		foreach(self::$prebootSteps as $prebootOption => $value) {
			if (is_null($value)) {
				self::prebootStep($prebootOption, $instance, $mode);
			}
		}
		$modeClass = Environment::instanceOption($instance, self::OPTION_MANAGER);
		if ($modeClass && class_exists($modeClass, false)) { #TODO #2.0.0 change autoload to a step for custom ordering
			Environment::autoload($modeClass);
			if (Environment::instanceOption($instance, self::OPTION_AUTOLOAD)){
				$modeClass::autoload($instance, $options);
			}
			$modeClass::preboot($instance, $options, self::$prebootSteps);
		} elseif ($modeClass) {
			//#TODO #2.0.0 warn about invalid mode class?
		}
	}

	/**
	 * Handling for various preboot steps, #TODO #2.0.0 move into separate Saf\Preboot class
	 * @param string $step matching PREBOOT_STEP_*
	 * @param string $instance 
	 * @param string $mode
	 */
	protected static function prebootStep($step, $instance = 'INTERNAL', $mode = 'INIT')
	{
		switch ($step) {
			case self::PREBOOT_STEP_LIBXML :
				if (function_exists('libxml_use_internal_errors')) {
					libxml_use_internal_errors(true);
				} else {
					Agent::mediate('libxml_use_internal_errors() not supported.', 'NOTICE');
				}
				break;
			case self::PREBOOT_STEP_TZ :
				if (defined('DEFAULT_TIMEZONE')) {
					date_default_timezone_set(DEFAULT_TIMEZONE);
				}				
				break;
			default:
			#TODO implement plugins?
		}
		if (!array_key_exists($step, self::$prebootSteps) || !self::$prebootSteps[$step]) {
			self::$prebootSteps[$step] = [Agent::instanceIdent($instance, $mode)];
		} else {
			self::$prebootSteps[$step][] = Agent::instanceIdent($instance, $mode);
		}
	}

	/**
	 * runs the specified main script for an instance with no framework bootstrap
	 * @param string instance to run
	 * @return mixed application result
	 */
	protected static function main(string $instance)
	{
		$modeMain = Environment::instanceOption($instance, 'mainScript', 'main');
		$installPath = Environment::path('install', $instance) ?: '.';
		$mainScript = "{$installPath}/{$modeMain}.php";
		#TODO provide a way to send parameters to main
		if (!file_exists($mainScript)) {
			throw new \Exception('No main application script to run.');
		} elseif (!is_readable($mainScript)){
			throw new \Exception('Unable to access main application script.');
		}
		return require_once($mainScript); //,$mainOptions);
	}

	/**
	 * runs the specified instance with its identified mode manager's framework bootstrap
	 * @param string instance to run
	 * @return mixed application result
	 */
	protected static function run(string $instance)
	{
		$modeClass = Environment::instanceOption($instance, self::OPTION_MANAGER);
		$options = &Environment::options($instance);
		if (!$modeClass || !class_exists($modeClass, false)) {
			$mode = array_key_exists($instance, self::$laced) ? self::$laced[$instance] : 'undefined';
			throw new \Exception("Requested kickstart mode ({$mode}:{$modeClass}) is not loaded.");
		}
		return $modeClass::run($instance, $options);
	}
}