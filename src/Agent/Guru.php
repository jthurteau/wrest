<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Root class for agent behavior
 */

namespace Saf\Agent;

use Saf\Auto;
use Saf\Agent\Meditation;

require_once(dirname(__DIR__) . '/Auto.php');
require_once(__DIR__ . '/Meditation.php');

trait Guru {
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
	 * squeltches (non-critical meditations)
	 */
	protected static $meditateSilently = true;

    /**
     * perform shutdown after a meditation
     */
    abstract public static function letGo();

    /**
    * registers a meditation level, marks it critical unless otherwise specified
    */
    abstract public static function regiterMeditation(string $level, bool $critical = true);

    /**
     * sets (initializes) the meditation view, automatically called 
     * before meditation if no meditation view is set.
     */
	abstract public static function initMeditation();

    /**
     * returns meditation(s)
     */
	abstract public static function getMeditation();

    /**
     * returns a unique id for meditations
     */
	abstract public static function meditationIdStrategy();

   	/**
	 * Outputs in the case of complete and total failure during the kickstart process.
	 * @param mixed $e \Exception, error string, or dump array
	 * @param string $level error level
	 * @param string|null $instance associated with the meditation (may be called before an Agent is instantiated)
	 */
	public static function meditate($e, $level = self::MEDITATION_NOTICE, $instance = null) #TODO add interface for detailed exceptions, $additionalError = '')
	{		
		if (is_null(self::$meditationView)) {
			self::$meditationView = self::initMeditation();
		}
        if (!self::isProtoMeditation($e)){
            if (is_string($e)) {
                $meditationText = $e;
				$e = null;
            } else {
				$meditationText = 'Data Meditation';
                $e = new \Exception(Auto::meditate($e));
            }
        } else {
			$type = get_class($e);
			$meditationText = "{$type} Meditation";
		}
		if (!self::$meditateSilently && in_array($level, self::$criticalMeditations)) {
			$rootUrl = defined('\APPLICATION_BASE_URL') ? \APPLICATION_BASE_URL : ''; #TODO #2.0.0 cleanup
			$title = 'Configuration Error';

			if (self::$meditationView) {
				#TODO #2.0.0 grab the metadataCanister?
				$canister = [
					'forceDebug' => true //#TODO #2.0.0 attach meditations to an instance?
				];
				include(self::$meditationView);
			} else {
				header('HTTP/1.0 500 Internal Server Error');
				print($e->getMessage());
			}
			self::letGo();
		} else {
			$m = new Meditation($meditationText, self::meditationIdStrategy($e), $e);
			self::$meditations[$m->getCode()] = $m;
		}

	}

    protected static function isProtoMeditation($e, $asString = false)
	{
		return (
			(is_object($e) || (is_string($e) && $asString))
			&& in_array('Throwable', class_implements($e, false))
		);
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
     * triggers the Guru to
     */
    public static function panic()
	{
		self::$outputMeditations = true;
	}

}
