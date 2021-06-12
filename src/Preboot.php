<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing preboot (kickstart, framework agnostic) steps
 */

namespace Saf;

#use Saf\Auto;

use Saf\Resolver;

#require_once(__DIR__ . '/Auto.php');
require_once(__DIR__ . '/Resolver.php');

class Preboot
{
	
	public const STEP_LIBXML = 'LibXml';
	public const STEP_TZ = 'TimeZone';

    /**
	 * stores order and state of various preboot steps
	 * false indicates that step will not be auto-attempted
	 * null indicates the step has not been attempted yet
	 * otherwise each value is an array of the instance idents that executed the step
	 */
	protected static $steps = [
		self::STEP_LIBXML => null,
		self::STEP_TZ => null,
	];

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
		Environment::set($instance, self::OPTION_RESOLVER, Manager::resolve($instance, $options));
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
		if ($modeClass) { #TODO change autoload to a step for custom ordering
			Environment::autoload($modeClass);
			if (Environment::instanceOption($instance, self::OPTION_AUTOLOAD)){
				$modeClass::autoload($instance, $options);
			}
			$modeClass::preboot($instance, $options, self::$prebootSteps);
		}
	}

    /**
	 * Handling for various preboot steps, #TODO #2.0.0 move into separate Saf\Preboot class
	 * @param string $step matching PREBOOT_STEP_*
	 * @param string $instance 
	 * @param string $mode
	 */
	protected static function step($step, $instance = 'INTERNAL', $mode = 'INIT')
	{
		switch ($step) {
			case self::STEP_LIBXML :
				if (function_exists('libxml_use_internal_errors')) {
					libxml_use_internal_errors(true);
				} else {
					Agent::mediate('libxml_use_internal_errors() not supported.', 'NOTICE');
				}
				break;
			case self::STEP_TZ :
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
	 * returns a resolver to help other frameworks route cases not natively 
	 * supported (like multi-views)
	 */
	public static function resolve($instance, $options)
	{
		return Resolver::init($instance, $options);
	}

}