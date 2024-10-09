<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for debugging
 */

namespace Saf;

// use Saf\Kickstart;
// use Saf\Hash;
use Saf\Cast;
// use Saf\Utils\Status;
//#TODO patch into Saf\Meditation;

use Saf\Utils\Debug\Handler;
use Saf\Utils\Debug\Analysis;

use Saf\Meditation\Configuration as ConfigurationMeditation;

class Debug
{
    /**
     * mode flag that forces debugging to be enabled and verbose
     */
    const MODE_FORCE = 'force';

    /**
     * mode flag that forces debugging to be disabled and silent
     */
    const MODE_DISABLE = 'disable';

    /**
     * mode flag that enables debugging, defaults to verbose (toggle-able) 
     */
    const MODE_ON = 'on';

    /**
     * mode flag that temporarily turns off debugging features
     */
    const MODE_OFF = 'off';

    /**
     * mode flag that leaves debugging enabled, but disables non-profiling output
     */
    const MODE_SILENT = 'silent';

    /**
     * debugging level that indicates an error or exception
     */
    const LEVEL_ERROR = 'ERROR';

    /**
     * debugging level that indicates a non-error to be profiled
     */
    const LEVEL_PROFILE = 'PROFILE';
    
    /**
     * debugging level that indicates a non-error (not profiled)
     */
    const LEVEL_STATUS = 'STATUS';
    
    /**
     * error mode that enables internal error/exception/shutdown handlers
     */
    const ERROR_MODE_INTERNAL = 'internal';
    
    /**
     * error mode that enables external plug-in driven handling
     */
    const ERROR_MODE_PLUGIN = 'plugin';
    
    /**
     * error mode that disables all internal handling features
     */
    const ERROR_MODE_EXTERNAL = 'default';

    /**
     * simplest trace output level that mimics Exception::traceAsString
     */
    const TRACE_SHALLOW = 'shallow';

    /**
     * trace output level with more detail than shallow;
     */
    const TRACE_DIGEST = 'digest';

    /**
     * trace output level that scans the depth of structures and generates prints
     */
    const TRACE_DEEP = 'deep';

    /**
     * trace output level, caches the full details of printed structures
     */
    const TRACE_VAULTED = 'vault';
    
    /**
     * current debugging mode
     */

    protected static ?string $mode = null;

    /**
     * flag indicating debugging settings have synced with session data
     */
    protected static $sessionReady = false;

    /**
     * the 'display_errors' setting to use when enabled
     */
    protected static $enabledDisplayMode = 1;

    /**
     * the 'display_errors' setting to use when disabled (detected from default setting at init)
     */
    protected static $disabledDisplayMode = 0;

    /**
     * the error_level to use for handling when enabled
     */
    protected static $enabledErrorLevel = -1;

    /**
     * the error_level to use for handling when disabled
     */
    protected static $disabledErrorLevel = null;

    /**
     * Initilizes debugging
     * @param string $mode indicates which MODE_ constant to use
     * @param mixed $errorHandler indicates an ERROR_MODE_ contant, or handling plug-in object
     */
    public static function init(
        string $mode = self::MODE_SILENT, 
        $errorHandler = self::ERROR_MODE_EXTERNAL
    ){
        if (is_null(self::$disabledErrorLevel)) {
            self::$disabledErrorLevel = error_reporting();
        }
        self::sessionCheck();
        if ($errorHandler == self::ERROR_MODE_INTERNAL) {
            Handler::takeover();
        } elseif (!is_null($errorHandler) && $errorHandler != self::ERROR_MODE_EXTERNAL) {
            Handler::install($errorHandler);
        }
        self::switchMode($mode);
        // print_r(['switch debug mode', __FILE__, __LINE__, self::$mode,isset($_SESSION)?$_SESSION:[],self::isEnabled(),self::isVerbose()]);
    }

    /**
     * returns the current mode
     * @return string MODE_ constant
     */
    public static function getMode() : ?string
    {
        return self::$mode;
    }

    /**
     * @return bool force enabled, verbose
     */
    public static function isForced()
    {
        return self::MODE_FORCE == self::$mode;
    }

    /**
     * @return bool is not force disabled
     */
    public static function isAvailable()
    {
        return self::MODE_DISABLE != self::$mode;
    }

    /**
     * @return bool is enabled (may be verbose or silenced)
     */
    public static function isEnabled()
    {
        return
            self::$mode
            && self::$mode != self::MODE_OFF
            && self::$mode != self::MODE_DISABLE;
    }

    /**
     * @param string $mode MODE_ constant to switch to
     * @return string resulting MODE_
     */
    public static function switchMode(string $mode)
    {
        if (self::isForced() || !self::isAvailable()) {
            return;
        }
        $previousMode = self::$mode;
        self::$mode = strtolower(trim($mode));
        switch (self::$mode) {
            case self::MODE_DISABLE:
                self::off();
                break;
            case self::MODE_OFF:
            case self::MODE_SILENT:
            case self::MODE_ON:
                self::auto();
                break;
            case self::MODE_FORCE:
                self::on();
                break;
            default:
                $badMode = self::$mode;
                self::$mode = $previousMode;
                throw new ConfigurationMeditation("Unknown Debug Mode: {$badMode}");
        }
    }

    /**
     * @return bool session data detected
     */
    public static function sessionCheck()
    {
        self::$sessionReady = 
            self::$sessionReady 
            || (isset($_SESSION) && is_array($_SESSION));
        return self::$sessionReady;
    }

    /**
     * Checks for session data, if present re-applies mode.
     *   Frameworks and Apps should call this when session is initialized 
     *   after debug.
     */
    public static function sessionReadyListner()
    {
        if (isset($_SESSION) && !key_exists('debug', $_SESSION)) {
            self::switchMode(self::$mode);
        }
    }

    /**
     * Updates session data (when available) with current mode
     */
    public static function updateSession()
    {
        if (self::$sessionReady) {
            $_SESSION['debug'] = Cast::dmvl(self::$mode, self::MODE_ON, self::MODE_OFF);
        }
    }

    /**
     * turns off "debugging" features (internal and PHP native)
     */
    public static function off($native = true)
    {
        self::hush($native);
        Handler::off();
    }

    /**
     * mutes "debugging" features (internal and PHP native)
     */
    public static function hush($native = true)
    {
        if ($native) {
            ini_set('display_errors', self::$disabledDisplayMode);
            error_reporting(self::$disabledErrorLevel);
        }
        Handler::hush();
    }

    /**
     * turns on "debugging" features and makes output verbose (internal and PHP native)
     */
    public static function on($native = true)
    {
        self::broadcast($native);
        Handler::on();
    }

    /**
     * unmutes "debugging" features (internal and PHP native)
     */
    public static function broadcast($native = true)
    {
        if ($native) {
            $displayMode = 
                Handler::allowBroadcast() #TODO moved to Handler
                ? self::$disabledDisplayMode
                : self::$enabledDisplayMode;
            ini_set('display_errors', $displayMode);
            error_reporting(self::$enabledErrorLevel);
        }
        Handler::broadcast();
    }

    /**
     * updates Debugging behavior
     */
    public static function auto()
    {//#TODO allow bindind to PSR7 on init?
        $oldMode = self::$mode;
        if (key_exists('nodebug', $_GET)) {
            self::$mode = self::MODE_OFF;
        } elseif (key_exists('debug', $_GET)) {
            self::$mode = self::MODE_ON;
        } elseif (key_exists('silentdebug', $_GET)) {
            self::$mode = self::MODE_SILENT;
        } elseif (self::$sessionReady && key_exists('debug', $_SESSION)) {
            self::$mode = Cast::mvl($_SESSION['debug'], self::MODE_ON, self::MODE_OFF);
        }
        switch (self::$mode) {
            case self::MODE_OFF:
                self::off();
                break;
            case self::MODE_SILENT:
                //self::on();
                self::hush();
                break;
            case self::MODE_ON:
                self::on();
                break;
            default:
                $badMode = self::$mode;
                self::$mode = $oldMode;
                throw new ConfigurationMeditation("Unknown Debug Mode: {$badMode}");
        }
        if (self::$sessionReady) {
            self::updateSession();
        }
    }

    /**
     * @return bool is currently verbose
     */
    public static function isVerbose()
    {
        return 
            self::MODE_ON == self::$mode 
            || self::MODE_FORCE == self::$mode;
    }

    /**
     * @return bool defaults to on/enabled
     */
    public static function isDefault()
    {
        return 
            self::MODE_ON == self::$mode
            || self::MODE_SILENT == self::$mode
            || self::MODE_FORCE == self::$mode;
    }



    public static function setErrorLevel($level)
    {
        self::$enabledErrorLevel = $level;
        if (self::isVerbose()) {
            error_reporting($level);
        }
    }

    public static function out($message, $level = self::LEVEL_ERROR)
    {
        Handler::out($level, $message, self::getTrace());
    }

    public static function outRaw($message, $preformat = false)
    {
        Handler::outRaw($message, self::getTrace());
    }

    public static function outData($message, $level = self::LEVEL_ERROR)
    {
        Handler::outData($level, $message, self::getTrace());
    }

    public static function outRawData($message, $preformat = false)
    {
        Handler::outRawData($message, $preformat);
    }

    public static function introspectData($message) //#TODO consolidate with Hash:introspectData?
    {
        return Analysis::data($message);
    }

    public static function getTrace($wrapped = true): array
    {
        try {
            throw new \Exception('debug');
        } catch (\Exception $e) {
            $trace = $e->getTrace();
            array_shift($trace); //#NOTE removes this(debug) object/method from the stack
            return $trace;
        }
    }

    protected static function getTraceString(): string
    {
        return self::renderTrace(array_slice((new \Exception('debug'))->getTrace(),1));
    }

    public static function renderTrace(array $trace): string
    {
        $standardEol = "\n";
        $out = '';
        $count = count($trace);
        foreach($trace as $index => $point) {
            if (
                array_keys($point) != ['file', 'line', 'function', 'class', 'type', 'args']
                && array_keys($point) != ['file', 'line', 'args', 'function']
                && array_keys($point) != ['file', 'line', 'function', 'args']
            ) {
                print_r([$index, array_keys($point)]); die;
            }
            $line =
                key_exists('file', $point)
                ? "{$point['file']}({$point['line']})"
                : '';
            $context = key_exists('function', $point)
                ? (
                    ": "
                    . (
                        key_exists('class', $point)
                        ? "{$point['class']}{$point['type']}"
                        : ''
                    ) . "{$point['function']}"
                ) : '';
            $env = key_exists('args', $point) ? self::renderArgList($point['args']) : '';
            $out .= "#{$index} {$line}{$context}{$env}{$standardEol}";
        }
        $out .= "#{$count} {main} {$standardEol}";
        return $out;
    }

    public static function renderArgList(array $args): string
    {
        $out = '(';
        $prefix = '';
        foreach($args as $argKey => $argValue) {
            $representation = self::renderArg($argValue);
            $out .= "{$prefix}{$representation}";
            $prefix = ', ';
        }
        return "{$out})";
    }

    public static function escapeTraceStrings(string $s, ?int $max = 512): string
    {
        return substr($s, 0, $max) . (strlen($s) > $max ? '[...]' : '');
    }

    public static function escapeTraceArray(array $a, ?int $max = 16): string
    {
        $out = '';
        $prefix = '';
        $count = 1;
        foreach($a as $index => $value) {
            if (is_string($index)) {
                $index = "'{$index}'";
            }
            $rendered = self::renderArg($value);
            $out .= "{$prefix}{$index} => {$rendered}";
            $prefix = ', ';
            $count++;
            if ($max >= 0 && $count > $max) {
                $out .= "{$prefix}...";
                break;
            }
        }
        return $out;
    }

    public static function renderArg(mixed $value, string $behavior = self::TRACE_DIGEST): string
    {
        $type = gettype($value);
        $representation = '';
        switch($type) {
            case 'integer':
            case 'boolean':
                $type = $type == 'integer' ? 'int' : 'bool';
                $representation = (string)$value;
                return "({$type}){$representation}";
            case 'float':
                $representation = (string)$value;
                return "({$type}){$representation}";
            case 'string':
                $type = "{$type}/" . strlen($value);
                $escValue = self::escapeTraceStrings($value);
                return "({$type})'$escValue'";
            case 'array':
                $type = "{$type}/" . count($value); //#TODO is numeric/subtype
                $escValue = $behavior = self::TRACE_SHALLOW ? '' : self::escapeTraceArray($value);
                return "($type)[{$escValue}]";
            case 'object':
                return $value::class;
            case 'resource':
            case 'callable':
               return "[{$type}]";
            case 'NULL':
                return 'null';
            default:
                return "({$type})[xxx]";
        }
    }

    public static function dieSafe($message = '')
    {
        Handler::dieSafe($message);
    }

    public static function halt()
    {
//        try{
        $standardEol = "\n";
        if (self::isEnabled()) {
            print("halting with trace:{$standardEol}");
            print(self::renderArgList(func_get_args()) . $standardEol);
            print(self::getTraceString());
            die;
        }
//        } catch (\Exception | \Error $e) {
//            print_r([__FILE__,__LINE__,$e->getMessage(), $e->getTraceAsString()]);
//        }
    }

    public static function vent()
    {
        if(self::isEnabled()) {
            $vent = require(__DIR__ . '/kickstart/debug.vent.php');
            print 
                is_callable($vent) 
                ? $vent(self::getTrace(), func_get_args()) 
                : $vent;
            die;
        }
    }

    public static function registerDieSafe()
    {
        Handler::terminates(false);
        register_shutdown_function('Debug::dieSafe');
    }

    public static function enabledDisplayMode()
    {
        return self::$enabledDisplayMode;
    }

    public static function disabledDisplayMode()
    {
        return self::$disabledDisplayMode;
    }

    public static function enabledErrorLevel()
    {
        return self::$enabledErrorLevel;
    }

    public static function disabledErrorLevel()
    {
        return self::$disabledErrorLevel;
    }

}
