<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for starting up applications and preparing frameworks.
 */

namespace Saf;

use Saf\Agent;
use Saf\Environment;
use Saf\Preboot;

require_once(__DIR__ . '/Agent.php');
require_once(__DIR__ . '/Environment.php');
require_once(__DIR__ . '/Preboot.php');

class Kickstart
{

	public const OPTION_NAME = Agent::OPTION_NAME;
	public const OPTION_MODE = Agent::OPTION_MODE;
	public const OPTION_START_TIME = Environment::OPTION_START_TIME;
	public const OPTION_PUBLIC_PATH = Environment::OPTION_PUBLIC_PATH;
	public const OPTION_INSTALL_PATH = Environment::OPTION_INSTALL_PATH;
	public const OPTION_INSTANCES = Environment::OPTION_INSTANCES;
	public const OPTION_AUTOLOAD = 'autoload';
	public const OPTION_MANAGER = 'managerClass';
	public const OPTION_THROW_MEDITATIONS = 'throwMeditations';
	public const OPTION_RETURN_MEDITATIONS = 'returnMeditations';
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
				Preboot::go($instance, $mode);
				self::$laced[$instance] = $mode;
			}
		} catch (\Error | \Exception $e) {
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
			$mode = Agent::parseMode($instanceIdentifier);
			if (!self::$laced[$instance]) {
				throw new \Exception('Requested instance has not been configured.');
			} elseif (
				$mode != self::$laced[$instance]
				&& $mode != Agent::MODE_NONE 
			) {
				throw new \Exception('Instance has not been configured for the requested mode.');
			}
			$options = &Environment::options($instance); #TODO the copy returned by E::o() needs to be re-bound
			$agent = new Agent($instance, $options);
			$modeClass = Environment::instanceOption($instance, self::OPTION_MANAGER);
			if ($mode != Agent::MODE_NONE && !class_exists($modeClass, false)) {
				$mode = array_key_exists($instance, self::$laced) ? self::$laced[$instance] : 'undefined';
				throw new \Exception("Requested kickstart mode ({$mode}:{$modeClass}) is not loaded.");
			}
			return $agent->run($modeClass);
	 		//#TODO #2.0.0 handle redirects and forwards, but maybe inside an agent method to simplify this class?
			// } catch (Saf\Exception\Assist $e){
			// } catch (Saf\Exception\Public $e){
			// } catch (Saf\Exception\Redirect $e){
			// } catch (Saf\Exception\Workflow $e){
		} catch (\Error | \Exception $e) { #TODO #2.0.0 handle redirects and forwards
			Agent::meditate($e, self::MEDITATION_LEVEL);
			if (Environment::instanceOption($instance, self::OPTION_THROW_MEDITATIONS)) {
				#TODO #2.0.0 get the correct deilm
				throw new \Exception("Failed to kickstart instance {$instance}@{$mode}", 0, Agent::getMeditation());
				#TODO properly detect handoff
				#throw new \Exception("Application instance {$instance}@{$mode} failed after kickstart", 0, Agent::getMeditation());
			} elseif (Environment::instanceOption($instance, self::OPTION_RETURN_MEDITATIONS)) {
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
			Preboot::go($instance, $mode);
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
		return self::kick(self::lace(null, $options));
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

}