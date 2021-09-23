<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Internal Error/Exception Handler Utility for Saf\Debug
 */

namespace Saf\Utils\Debug;

use Saf\Debug;
use Saf\Kickstart;
use Saf\Utils\Status;
//use Saf\Utils\Debug\Ui; #in namespace

class Handler 
{
	protected static $inControl = false;
    protected static $plugin = null;
    protected static $oldErrorHandler = null;
	protected static $oldExceptionHandler = null;
	protected static $shutdownRegistered = false;
	protected static $shuttingDown = false;
	protected static $terminateOnShutdown = true;

	public static function takeover()
	{
		if (!self::$inControl) {
			self::$oldErrorHandler = set_error_handler('Debug::handle');
			self::$oldExceptionHandler = set_exception_handler('Debug::handleException');
			if (!self::$shutdownRegistered) {
				register_shutdown_function('Debug::shutdown');
				self::$shutdownRegistered = true;
			}
			ini_set('display_errors', self::$disabledDisplayMode);
			self::$inControl = TRUE;
		}
	}

    public static function install($handler)
    {
        self::$plugin = $handler;
    }

	public static function relenquish()
	{
		if (
			!is_null(self::$oldErrorHandler)
			&& self::$oldErrorHandler
		) {
			set_error_handler(self::$oldErrorHandler);
			self::$oldErrorHandler = null;
		} else {
			restore_exception_handler();
		}
		if (
			!is_null(self::$oldExceptionHandler)
			&& self::$oldExceptionHandler
		) {
			set_error_handler(self::$oldExceptionHandler);
			self::$oldExceptionHandler = null;
		} else {
			restore_exception_handler();
		}
		self::$inControl = false;
	}

	public static function shutdown()
	{
		$handledErrors = array(
			1 => 'E_ERROR',
			//2 => 'E_WARNING',
			4 => 'E_PARSE',
			//8 => 'E_NOTICE',
			16 => 'E_CORE_ERROR',
			//32 => 'E_CORE_WARNING',
			64 => 'E_COMPILE_ERROR',
			//128 => 'E_COMPILE_WARNING',
			256 => 'E_USER_ERROR',
			//512 => 'E_USER_WARNING',
			//1024 => 'E_USER_NOTICE',
			2048 => 'E_STRICT',
			4096 => 'E_RECOVERABLE_ERROR',
			8192 => 'E_DEPRECATED',
			16384 => 'E_USER_DEPRECATED'

		);
		self::$shuttingDown = true;
		if (self::$inControl) {
			$error = error_get_last();
			if (key_exists($error["type"], $handledErrors)) {
				self::handle($error["type"], $error["message"], $error["file"], $error["line"]);
			}
		}
	}

	public static function handleException($e)
	{
		$errorType = get_class($e);
		$errorString = $e->getMessage();
		$errorFile = $e->getFile();
		$errorLine = $e->getLine();
		self::outRaw('<span class="phpException">');
		self::handle($errorType, $errorString, $errorFile, $errorLine);
		self::outRaw('<pre class="phpErrorTrace">');
		self::outRawData($e->getTraceAsString());
		self::outRaw('</pre>');
		if ($e->getPrevious()) {
			self::handleException($e->getPrevious());
		}
		print('</span>');
	}

	public static function handle($errorNo, $errorString, $errorFile = null, $errorLine = null, $errorContext = array())
	{
		$lookupTable = array(
			1 => 'E_ERROR',
			2 => 'E_WARNING',
			4 => 'E_PARSE',
			8 => 'E_NOTICE',
			16 => 'E_CORE_ERROR',
			32 => 'E_CORE_WARNING',
			64 => 'E_COMPILE_ERROR',
			128 => 'E_COMPILE_WARNING',
			256 => 'E_USER_ERROR',
			512 => 'E_USER_WARNING',
			1024 => 'E_USER_NOTICE',
			2048 => 'E_STRICT',
			4096 => 'E_RECOVERABLE_ERROR',
			8192 => 'E_DEPRECATED',
			16384 => 'E_USER_DEPRECATED'
		);
		$simplifyTable = array(
			'E_ERROR' => 'error',
			'E_WARNING' => 'warning',
			'E_PARSE' => 'error',
			'E_NOTICE' => 'notice',
			'E_CORE_ERROR' => 'error',
			'E_CORE_WARNING' => 'warning',
			'E_COMPILE_ERROR' => 'error',
			'E_COMPILE_WARNING' => 'notice',
			'E_USER_ERROR' => 'error',
			'E_USER_WARNING' => 'warning',
			'E_USER_NOTICE' => 'notice',
			'E_STRICT' => 'warning',
			'E_RECOVERABLE_ERROR' => 'error',
			'E_DEPRECATED' => 'warning',
			'E_USER_DEPRECATED' => 'warning'
		);
		$fatalErrorList = array(1, 4, 16, 64, 256);
		$fatal = in_array($errorNo, $fatalErrorList);
		$description =
			array_key_exists($errorNo, $lookupTable)
				? $lookupTable[$errorNo]
				: (is_numeric($errorNo) ? 'ERROR_NO_' . $errorNo : $errorNo);
		$at = $errorLine ? " on line {$errorLine}" : '';
		$in = $errorFile ? " in file {$errorFile}" . $at : $at;
		if ($fatal) {
			$caughtBy = self::$_shuttingDown ? 'SHUTDOWN' : 'DEBUG';
			Status::set(Status::STATUS_500_ERROR);
			$e = new \Exception("{$description} {$in}");
			Kickstart::exceptionDisplay($e, $caughtBy, $errorString);
		} else {
			$show = Debug::enabledErrorLevel() === -1 || $errorNo & Debug::enabledErrorLevel();
			if ($show && !Mute::active()) {
				$message = "<span class=\"phpErrorWhat\">{$description} - </span>"
					. "<span class=\"phpErrorMessage\">{$errorString}</span>"
					. "<span slass=\"phpErrorWhere\">{$in}</span> ";
				$trace = self::getTrace();
				$level =
					key_exists($description, $simplifyTable)
						? $simplifyTable[$description]
						: 'error';
				$level = htmlentities(ucfirst(strtolower($level)));
				//$icon = $trace ? (' <span class="debugExpand"> ' . Layout::getIcon(self::LAYOUT_MORE_INFO_ICON) . '</span>') : '';
				$icon = '<span class="debugExpand">[B]</span>';
				$output = "{$message}{$icon}{$trace}\n";
				self::goOut(
					"<div class=\"debug{$level}\">"
					. '<div class="phpError">' . $output . '</div>'
					. '</div>'
				);
			}
		}
		return FALSE;
	}

    public static function terminates($set = null)
    {
        if (!is_null($set)) {
            self::$terminateOnShutdown = $set;
        }
        return self::$terminateOnShutdown;
    }

    public static function allowBroadcast()
    { //#TODO check plugin
        return self::$inControl;
    }

	public static function off()
	{

	}

	public static function on()
	{
		
	}

	public static function hush()
	{
		
	}

	public static function broadcast()
	{
		
	}

	public static function out($level, $message, $trace)
	{
		if (self::$plugin && method_exists(self::$plugin, 'out')){
			self::$plugin->out($level,$message, $trace);
		}
		Ui::out($level, $message, $trace);
	}

	public static function outRaw($message, $preformat)
	{
		if (self::$plugin && method_exists(self::$plugin, 'outRaw')){
			self::$plugin->outRaw($message, $preformat);
		}
		Ui::outRaw($message, $preformat);
	}

	public static function outData($level, $message, $trace)
	{
		if (self::$plugin && method_exists(self::$plugin, 'outData')){
			self::$plugin->outData($level,$message, $trace);
		}
		Ui::outData($level, $message, $trace);
	}

	public static function outRawData($message, $preformat)
	{
		if (self::$plugin && method_exists(self::$plugin, 'outRawData')){
			self::$plugin->outRawData($message, $preformat);
		}
		Ui::outRawData($message, $preformat);
	}

	public static function dieSafe($message = '')
	{
		if (self::isEnabled()) {
			if (false) {
			//if (self::$_notifyConsole && Layout::formatIsHtml()) {
				print('<script type="text/javascript">throw new Error("' . APPLICATION_DEBUG_NOTIFICATION . '");</script>');
			}
			Ui::printDebugShutdown();
			Ui::printDebugExit();
		} else if (self::isVerbose()) {
			Ui::printDebugEntry();
		}
		if (self::terminates()) {
			die($message);
		} elseif ($message) {
			print($message);
		}
	}

}