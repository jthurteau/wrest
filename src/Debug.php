<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for debugging
 */

namespace Saf;

use Saf\Kickstart;
use Saf\Hash;
use Saf\Utils\Status;
use Saf\Layout;

// require_once(dirname(__FILE__) . '/Kickstart.php');

// require_once(dirname(__FILE__) . '/UtilsStatus.php');
// require_once(dirname(__FILE__) . '/Layout.php');


use Saf\Utils\Debug\Mute;

class Debug
{

	protected static $_mode = NULL;
	protected static $_locked = FALSE;
	protected static $_enabled = NULL;
	protected static $_verbose = FALSE;
	protected static $_buffered = TRUE;
	protected static $_sessionReady = FALSE;
	public static $buffer = '';
	protected static $_maxBufferSize = 1000000;
	protected static $_bufferOverflow = FALSE;

	protected static $_enabledDisplayMode = 1;
	protected static $_disabledDisplayMode = 0;
	protected static $_enabledErrorLevel = -1; // seems to work where E_ALL | E_STRICT should...
	protected static $_disabledErrorLevel = NULL;

	protected static $_notifyConsole = FALSE;
	protected static $_inControl = FALSE;
	protected static $_alreadyPrintedDebugExit = FALSE;
	protected static $_alreadyPrintedDebugEntry = FALSE;
	protected static $_alreadyPrintedDebugShutdown = FALSE;

	protected static $_oldErrorHandler = NULL;
	protected static $_oldExceptionHandler = NULL;
	protected static $_shutdownRegistered = FALSE;
	protected static $_shuttingDown = FALSE;
	protected static $_terminateOnShutdown = TRUE;

	const DEBUG_MODE_OFF = 'off';
	const DEBUG_MODE_SILENT = 'silent';
	const DEBUG_MODE_ON = 'on';
	const DEBUG_MODE_AUTO = 'auto';
	const DEBUG_MODE_FORCE = 'force';
	const LAYOUT_MORE_INFO_ICON = 'expand';
	const LAYOUT_PROFILE_INFO_ICON = 'tachometer-alt';

	const ERROR_MODE_INTERNAL = 'internal';
	const ERROR_MODE_DEFAULT = 'default';

	public static function init($mode = self::DEBUG_MODE_SILENT, $errorHandling = self::ERROR_MODE_DEFAULT, $sessionReady = FALSE)
	{
		if (self::$_locked) {
			return;
		}
		if (is_null(self::$_disabledErrorLevel)) {
			self::$_disabledErrorLevel = error_reporting();
		}
		self::$_sessionReady = self::$_sessionReady || $sessionReady;
		if ($errorHandling == self::ERROR_MODE_INTERNAL) {
			self::takeover();
		}
		self::switchMode(strtolower(trim($mode)));
// 		print_r(array('switch debug mode', self::$_mode,isset($_SESSION)?$_SESSION:array(),self::$_enabled,self::$_verbose));
	}

	public static function switchMode($mode)
	{
		if (self::$_locked || self::isForced()) {
			return;
		}
		$previousMode = self::$_mode;
		self::$_mode = strtolower(trim($mode));
		switch (self::$_mode) {
			case self::DEBUG_MODE_OFF:
				self::disable();
				break;
			case self::DEBUG_MODE_SILENT:
			case self::DEBUG_MODE_ON:
			case self::DEBUG_MODE_AUTO:
				self::auto();
				break;
			case self::DEBUG_MODE_FORCE:
				self::enable();
				break;
			default:
				$badMode = self::$_mode;
				self::$_mode = $previousMode;
				throw new \Exception("Unknown Debug Mode: {$badMode}");
		}
	}

	public static function sessionReadyListner()
	{
		if (
			isset($_SESSION)
			&& (
			!array_key_exists('debug', $_SESSION)
			)
		) {
			self::switchMode(self::$_mode);
		}
	}

	public static function lock()
	{
		self::$_locked = TRUE;
	}

	public static function disable()
	{
		if (self::$_locked) {
			return;
		}
		if (self::$_sessionReady && isset($_SESSION)) {
			$_SESSION['debug'] = FALSE;
		}
		self::hush();
		self::$_enabled = FALSE;
	}

	public static function hush()
	{
		if (self::$_locked) {
			return;
		}
		self::$_verbose = FALSE;
		ini_set('display_errors', self::$_disabledDisplayMode);
		error_reporting(self::$_disabledErrorLevel);
	}

	public static function enable()
	{
		if (self::$_locked) {
			return;
		}
		if (self::$_sessionReady) {
			$_SESSION['debug'] = TRUE;
		}
		self::on();
		self::$_enabled = TRUE;
	}

	public static function on()
	{
		if (self::$_locked) {
			return;
		}
		self::$_verbose = TRUE;
		ini_set(
			'display_errors',
			self::$_inControl
				? self::$_disabledDisplayMode
				: self::$_enabledDisplayMode
		);
		error_reporting(self::$_enabledErrorLevel);
	}

	public static function auto()
	{
		if (self::$_locked) {
			return;
		}
		if (array_key_exists('nodebug', $_GET)) {
			self::disable();
		} else if (array_key_exists('debug', $_GET)) {
			self::enable();
		} else if (self::$_sessionReady) {
			if (
				isset($_SESSION)
				&& array_key_exists('debug', $_SESSION)
				&& !$_SESSION['debug']
			) {
				self::disable();
			} else if (
				isset($_SESSION)
				&& array_key_exists('debug', $_SESSION)
				&& $_SESSION['debug']
			) {
				self::enable();
			} else if (self::$_mode == self::DEBUG_MODE_AUTO) {
				self::enable();
			}
		}
		
// 		if (!self::$_sessionReady || $neitherDefined){
// 			if (self::$_mode == self::DEBUG_MODE_AUTO) {
// 				self::on();
// 			} else if (self::$_mode == self::DEBUG_MODE_SILENT) {
// 				self::hush();
// 			}
// 		}
		if (is_null(self::$_enabled)) {
			self::$_enabled = self::$_mode != self::DEBUG_MODE_OFF;
		}
	}

	public static function isAvailable()
	{
		return self::DEBUG_MODE_OFF != self::$_mode;
	}

	public static function isVerbose()
	{
		return self::$_verbose;
	}

	public static function isDefault()
	{
		return self::DEBUG_MODE_AUTO == self::$_mode
		|| self::DEBUG_MODE_FORCE == self::$_mode;
	}

	public static function isEnabled()
	{
		return self::$_enabled;
	}

	public static function isForced()
	{
		return self::DEBUG_MODE_FORCE == self::$_mode;
	}

	public static function isLocked()
	{
		return self::$_locked;
	}

	public static function setErrorLevel($level)
	{
		self::$_enabledErrorLevel = $level;
		if (self::$_verbose) {
			error_reporting($level);
		}
	}

	public static function out($message, $level = 'ERROR')
	{
		$trace = self::getTrace();
		$level = htmlentities(ucfirst(strtolower($level)));
		$icon = $trace ? (' <span class="debugExpand"> ' . Layout::getIcon(self::LAYOUT_MORE_INFO_ICON) . '</span>') : '';
		$output = "<div class=\"debug{$level}\"><p>{$message}{$icon}</p>{$trace}</div>\n";
		self::_out($output, $level != 'Status' && $level != 'Other');
	}

	public static function outRaw($message, $level = 'ERROR')
	{
		$trace = self::getTrace();
		$level = htmlentities(ucfirst(strtolower($level)));
		$icon = $trace ? (' <span class="debugExpand"> ' . Layout::getIcon(self::LAYOUT_MORE_INFO_ICON) . '</span>') : '';
		$output = "<div class=\"debug{$level}\">{$message}{$icon}{$trace}</div>\n";
		self::_out($output);
	}

	public static function outData($message, $level = 'ERROR')
	{
		$trace = self::getTrace();
		$level = htmlentities(ucfirst(strtolower($level)));
		$icon = $trace ? (' <span class="debugExpand"> ' . Layout::getIcon(self::LAYOUT_MORE_INFO_ICON) . '</span>') : '';
		ob_start();
		print("\n<div class=\"debug{$level}\"><pre>Data:{$icon}<br/>\n");
		print($trace);
		print_r($message);
		print("\n</pre></div>\n");
		$output = ob_get_contents();
		ob_end_clean();
		self::_out($output, $level != 'Status' && $level != 'Other');
	}

	public static function outRawData($message, $preformat = FALSE)
	{
		ob_start();
		print_r($message);
		$output = ob_get_contents();
		ob_end_clean();
		self::_out(($preformat ? '<pre class="data">' : '') . $output . ($preformat ? '</pre>' : ''));
	}

	public static function introspectData($message)
	{
		ob_start();
		print_r($message);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	protected static function _out($output, $notifyConsole = TRUE)
	{
		self::$_notifyConsole =
			self::$_notifyConsole
			|| $notifyConsole;
		if (self::$_buffered) {
			if (strlen(self::$buffer) + strlen($output) > self::$_maxBufferSize) {
				self::$_bufferOverflow = TRUE; //#TODO #2.0.0 do something with the overflow indicator at render time.
			} else {
				self::$buffer .= $output;
			}
		} else {
			print($output);
		}
	}

	public static function getTrace()
	{
		try {
			throw new \Exception('debug');
		} catch (\Exception $e) {
			$trace = $e->getTrace();
			$traceString = $e->getTraceAsString();
			array_shift($trace);
			//array_shift($trace);
			return
				$trace
				? ('<pre class="debugTrace" style="display:none;">' . self::formatTrace($trace) . '</pre>')
				: '';
		}
	}

	public static function formatTrace($trace)
	{
		$traceLines = array();
		foreach ($trace as $lineIndex => $line) {
			$traceLines[] = "#{$lineIndex} "
				. (array_key_exists('file', $line) ? $line['file'] : '')
				. (array_key_exists('line', $line) ? "({$line['line']}):" : '[internal function]:')
				. ($lineIndex && array_key_exists('class', $line) ? " {$line['class']}" : '')
				. ($lineIndex && array_key_exists('type', $line) ? "{$line['type']}" : '')
				. ($lineIndex && array_key_exists('function', $line) ? " {$line['function']}" : '')
				. ($lineIndex && array_key_exists('args', $line) ? self::paramSummary($line['args']) : '')
				. "";
			//{$line['line']} {$line['class']} {$line['function']} {$line['type']} {$line['args']}";
		}
		return htmlentities(implode("\r\n", $traceLines));
	}

	public static function paramSummary($args)
	{//#TODO #2.0.0
		return '(args...)';
	}

	public static function startBuffer()
	{
		self::$_buffered = TRUE;
	}

	public static function endBuffer()
	{
		self::$_buffered = FALSE;
	}

	public static function getBuffer()
	{
		return self::$buffer;
	}

	public static function cleanBuffer()
	{
		if (self::$_verbose && Layout::formatIsHtml()) {
			print('<!-- debug buffer cleared -->');
		}
		self::$buffer = '';
		self::$_bufferOverflow = FALSE;
	}

	public static function flushBuffer($force = FALSE)
	{
		$return = self::getBuffer();
		if ($force || self::$_verbose) {
			print('<!-- debug buffer contents ' . strlen($return) . ' -->');
			print($return);
		}
		self::cleanBuffer();
		return $return;
	}

	public static function getRequestString()
	{
		ob_start();
		$sanitizedRequest = $_REQUEST;
		if (array_key_exists('pwd', $sanitizedRequest)) {
			$sanitizedRequest['pwd'] = '***redacted***';
		}
		if (array_key_exists('password', $sanitizedRequest)) {
			$sanitizedRequest['password'] = '***redacted***';
		}
		print('<ul class="debugList">');
		foreach($sanitizedRequest as $key=>$item) {
			if (is_array($item)) {
				$item = Hash::toString($item);
			}
			print('<li class="noBullet">[' . htmlentities($key) . '] : ' . '<span class="literal">' . htmlentities($item) . '</span></li>');
		}
		print('</ul>');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	public static function outputRequest()
	{
		ob_start();
		print("\n<div class=\"debugStatus\">Request:\n");
		print_r(self::getRequestString());
		print("\n</div>\n");
		$output = ob_get_contents();
		ob_end_clean();
		self::_out($output, FALSE);
	}

	public static function printDebugExit($force = FALSE)
	{
		if (!self::$_alreadyPrintedDebugExit || $force) {
			if (Layout::formatIsHtml()) {
				if (!self::isForced()) {
					print("\n<p class=\"debugOther\"><a href=\"?nodebug=true\">Disable debugging for this session.</a></p>\n");
				} else {
					print("\n<p class=\"debugOther\">Debugging is forced to on.</p>\n");
				}
			}//#TODO #2.0.0 figure out what to do for other formats...
			self::$_alreadyPrintedDebugExit = TRUE;
		}
	}

	public static function printDebugEntry($force = FALSE)
	{
		if (self::isAvailable() && !self::isVerbose()) {
			if (!self::$_alreadyPrintedDebugEntry || $force) {
				$mode = self::$_mode;
				if (Layout::formatIsHtml()) {
					print("\n<p class=\"debugEntry\"><a href=\"?debug=true\">Enable debugging for this session. Debug mode: {$mode}</a></p>\n");
				} //#TODO #2.0.0 figure out what to do for other formats...
			}
			self::$_alreadyPrintedDebugEntry = TRUE;
		}
	}

	public static function printDebugShutdown()
	{
		if (!self::$_alreadyPrintedDebugShutdown && Layout::formatIsHtml()) {
			$loadTime = microtime(TRUE) - APPLICATION_START_TIME;
			if (self::isVerbose()) {
				if (Mute::active()) {
					foreach (Mute::list() as $trace) {
						$icon = ' <span class="debugExpand"> ' . Layout::getIcon(self::LAYOUT_MORE_INFO_ICON) . '</span>';
						print("\n<div class=\"debugStatus\"><pre>Data:{$icon}<br/>\n");
						print(htmlentities($trace));
						print('Unclosed Mute');
						print("\n</pre></div>\n");
					}
				}
			}
			self::$_buffered = FALSE;
			if (self::isVerbose() && strlen(self::$buffer) > 0) {
				print('<div class="debugStatus">Unsent Debug Buffer:<br/><pre>');
				print(self::$buffer);
				print('</pre></div>');
			}
			self::out("This page took {$loadTime} seconds to load.", 'STATUS');
			self::$_alreadyPrintedDebugShutdown = TRUE;
		}
	}

	public static function printDebugAnchor()
	{
		print('<span id="debugTop"></span>');
	}

	public static function printDebugReveal()
	{
		if (Layout::isReady()) {
			$icon = Layout::getIcon('bug');
			$accessible = ' class="accessibleHidden"';
		} else {
			$icon = '';
			$accessible = '';
		}
		print('<div id="showDebug"><a href="#">'
			. "{$icon}<span{$accessible}>Show Debug Information</span></a></div>"
		);
	}

	public static function printProfileReveal()
	{
		if (Layout::isReady()) {
			$icon = Layout::getIcon(self::LAYOUT_PROFILE_INFO_ICON);
			$accessible = ' class="accessibleHidden"';
		} else {
			$icon = '';
			$accessible = '';
		}
		print('<div id="showProfile"><a href="#">'
			. "{$icon}<span{$accessible}>Show Profiling Information</span></a></div>"
		);
	}

	public static function dieSafe($message = '')
	{
		if (self::isEnabled()) {
			if (self::$_notifyConsole && Layout::formatIsHtml()) {
				print('<script type="text/javascript">throw new Error("' . APPLICATION_DEBUG_NOTIFICATION . '");</script>');
			}
			self::printDebugShutdown();
			self::printDebugExit();
		} else if (self::isVerbose()) {
			self::printDebugEntry();
		}
		if (self::$_terminateOnShutdown) {
			die($message);
		} else if ($message) {
			print($message);
		}
	}

	public static function registerDieSafe()
	{
		self::$_terminateOnShutdown = FALSE;
		register_shutdown_function('Debug::dieSafe');
	}

	public static function outDebugBlockStart($level = 'ERROR')
	{
		self::_out("<div class=\"debug{$level}\">");
	}

	public static function outDebugBlockEnd()
	{
		self::_out("</div>");
	}

	public static function takeover()
	{
		if (self::$_locked) {
			return;
		}
		if (!self::$_inControl) {
			self::$_oldErrorHandler = set_error_handler('Debug::handle');
			self::$_oldExceptionHandler = set_exception_handler('Debug::handleException');
			if (!self::$_shutdownRegistered) {
				register_shutdown_function('Debug::shutdown');
				self::$_shutdownRegistered = TRUE;
			}
			ini_set('display_errors', self::$_disabledDisplayMode);
			self::$_inControl = TRUE;
		}
	}

	public static function relenquish()
	{
		if (self::$_locked) {
			return;
		}
		if (
			!is_null(self::$_oldErrorHandler)
			&& self::$_oldErrorHandler
		) {
			set_error_handler(self::$_oldErrorHandler);
			self::$_oldErrorHandler = NULL;
		} else {
			restore_exception_handler();
		}
		if (
			!is_null(self::$_oldExceptionHandler)
			&& self::$_oldExceptionHandler
		) {
			set_error_handler(self::$_oldExceptionHandler);
			self::$_oldExceptionHandler = NULL;
		} else {
			restore_exception_handler();
		}
		self::$_inControl = FALSE;
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
		self::$_shuttingDown = TRUE;
		if (self::$_inControl) {
			$error = error_get_last();
			if (array_key_exists($error["type"], $handledErrors)) {
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

	public static function handle($errorNo, $errorString, $errorFile = NULL, $errorLine = NULL, $errorContext = array())
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
			$show = self::$_enabledErrorLevel === -1 || $errorNo & self::$_enabledErrorLevel;
			if ($show && !Mute::active()) {
				$message = "<span class=\"phpErrorWhat\">{$description} - </span>"
					. "<span class=\"phpErrorMessage\">{$errorString}</span>"
					. "<span slass=\"phpErrorWhere\">{$in}</span> ";
				$trace = self::getTrace();
				$level =
					array_key_exists($description, $simplifyTable)
						? $simplifyTable[$description]
						: 'error';
				$level = htmlentities(ucfirst(strtolower($level)));
				$icon = $trace ? (' <span class="debugExpand"> ' . Layout::getIcon(self::LAYOUT_MORE_INFO_ICON) . '</span>') : '';
				$output = "{$message}{$icon}{$trace}\n";
				self::_out(
					"<div class=\"debug{$level}\">"
					. '<div class="phpError">' . $output . '</div>'
					. '</div>'
				);
			}
		}
		return FALSE;
		/*	$errorLog = Rd_Registry::get('root:errorLogPath');
				try {
					$cmd = Rd_Registry::get('root:requestCommand');
				} catch (Exception $e) {
					$cmd = '';
				}
				try {
					$u = Rd_Registry::get('root:userInterface');
				} catch (Exception $e) {
					$u = NULL;
				}

				$dt = date('Y-m-d H:i:s (T)');
				$errortype = array (
					E_ERROR => "Error",
					E_WARNING => "Warning",
					E_PARSE => "Parsing Error",
					E_NOTICE => "Notice",
					E_CORE_ERROR => "Core Error",
					E_CORE_WARNING => "Core Warning",
					E_COMPILE_ERROR => "Compile Error",
					E_COMPILE_WARNING => "Compile Warning",
					E_USER_ERROR => "User Error",
					E_USER_WARNING => "User Warning",
					E_USER_NOTICE => "User Notice",
					E_STRICT => "Runtime Notice"
					);
				// set of errors for which a var trace will be saved
				$user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_USER_ERROR);
				$err = "<errorentry>\n"
					. "\t<datetime>" . $dt . "</datetime>\n"
					. "\t<errornum>" . $errno . "</errornum>\n"
					. "\t<errortype>" . (array_key_exists($errno,$errortype) ? $errortype[$errno] : '') . "</errortype>\n"
					. "\t<errormsg>" . $errmsg . "</errormsg>\n"
					. "\t<scriptname>" . $filename . "</scriptname>\n"
					. "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";
				if ($u instanceof user) {
					$err .= "\t<user><username>" . $u->getUsername() . "</username><userID>" . $u->getUserID() . "</userID></user>\n";
				}
				$err .= "\t<cmd>$cmd</cmd>\n";
				if (in_array($errno, $user_errors)) {
					//$err .= "\t<vartrace>" . wddx_serialize_value($vars, "Variables") . "</vartrace>\n";
				}
				$err .= "</errorentry>\n\n";

				if(self::isEnabled()) {
					print('<pre>' . htmlentities($err) . '</pre>');
				}
				if ($errno <> E_NOTICE && $errno <> E_STRICT && $errno <> E_WARNING) {
					// save to the error log, and e-mail me if there is a critical user error
					if('' != $errorLog) {
						error_log($err, 3, $errorLog);
					}
					include_once('error.php');
					die;
				}*/
	}

	//#TODO #2.1.0 clean up the below (used in J and RD

	protected static $_debugStack = array();

	public static function addMessage($message, $nameSpace = NULL)
	{
		if (is_null($nameSpace)) {
			self::$_debugStack[] = $message;
		} else if (array_key_exists($nameSpace, self::$_debugStack)) {
			self::$_debugStack[$nameSpace][] = $message;
		} else {
			self::$_debugStack[$nameSpace] = array($message);
		}
	}

	public static function getMessages()
	{
		return self::$_debugStack;
	}

	public static function clearMessages()
	{
		self::$_debugStack = array();
	}

}
