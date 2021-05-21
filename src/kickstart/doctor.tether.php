<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * verbose logging tool
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

return function ( #TODO #PHP8 allows throw as an expression
    array &$canister = []
){
    $storagePath = 
        key_exists('storageRoot', $canister) 
        ? $canister['storageRoot']
        : '/var/www/storage';
    $storageBin = 
        key_exists('applicationHandle', $canister) 
        ? $canister['applicationHandle']
        : 'temp';
    $snoopLogPath = 
        array_key_exists('snoopLog', $canister) 
        ? (is_string($canister['snoopLog']) ? $canister['snoopLog'] : "${storagePath}/{$storageBin}/snoop.log") 
        :  false;

    $precision = 6;
    $startPad = 17;
    $rando = str_pad(dechex(rand(0,255)), 2, '0', STR_PAD_LEFT);

    $canister['doctorOut'] = function($message = '', $indent = 0) use (&$canister, $snoopLogPath, &$precision, &$startPad, &$rando) {
        static $firstTime = null;
        static $lastTime = null;
        static $lastMemory = null;
        if (is_null($firstTime)) {
            $firstTime = 
                key_exists('startTime', $canister) 
                ? $canister['startTime'] 
                : microtime(true);
            $timeString = (string) number_format($firstTime, $precision, '.', '');
            $startPad = strlen($timeString);
            // $precision = 
            //     strpos($timeString, '.') !== 0 
            //     ? ((strlen($timeString) - strpos($timeString, '.')) - 1)  
            //     : 0;
        }
        $identString = str_repeat('  ', $indent);
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage();
        $outdent = str_repeat(' ', 51);
        $duration = number_format($currentTime - $firstTime, $precision); 
        if (!$message) {
            $message = "{$outdent} - {$duration} {$currentMemory}";
            if (!is_null($lastMemory)) {
                $timeDelta = number_format($currentTime - $lastTime, $precision); 
                $memoryDelta = $currentMemory - $lastMemory;
                $message .= "\n{$outdent} - {$timeDelta} {$memoryDelta}";
            } 
        }
        $currentTimeString = number_format($currentTime, $precision, '.', '');
        $firstTimeString = number_format($firstTime, $precision, '.', '');
        $message = str_replace(
            [
                '{$d}',
                '{$c}',
                '{$f}',
                "\n"
            ], [
                $duration,
                $currentTimeString,
                $firstTimeString,
                "\n{$identString}"
            ], $message
        );
        if ($snoopLogPath && is_writable(dirname($snoopLogPath))) {
            file_put_contents($snoopLogPath, "\n{$identString}{$message}", FILE_APPEND | LOCK_EX);
        } //#TODO else?
        $lastTime = $currentTime;
        $lastMemory = $currentMemory;
    };

    $startTime = key_exists('startTime', $canister) ? $canister['startTime'] : microtime(true);
    $currentTime = date('H:i:s d M Y');
    $canister['doctorOut']("\n{$rando}--- start of transaction --- {\$f} - ($currentTime) --- dr");
    $gatewayFile = 
        key_exists('SCRIPT_FILENAME', $_SERVER)
        ? (
            strpos($_SERVER['SCRIPT_FILENAME'],'./') === 0 
            ? substr($_SERVER['SCRIPT_FILENAME'], 1)
            : $_SERVER['SCRIPT_FILENAME']
        ) : '';
    $request = 
        key_exists('requestUri', $canister) 
        ? $canister['requestUri'] 
        : (
            key_exists('REQUEST_URI', $_SERVER)
            ? $_SERVER['REQUEST_URI']
            : (
                key_exists('PWD', $_SERVER)
                ? (
                    $_SERVER['PWD'] . $gatewayFile 
                ) : '???'
            )
        );
    $canister['doctorOut']("{$rando}- {$request}");

    $paramSummary = function($params)
    {
        $return = '(';
        foreach($params as $key => $value){
            $sep = $return == '(' ? '': ', ';
            $valueString = gettype($value); #TODO represent value as well
            $return .= "{$key} {$valueString}{$sep}"; 
        }
        return "$return)";
    };

    $formatter = function($trace) use ($paramSummary)
    {
        $traceLines = array();
        foreach ($trace as $lineIndex => $line) {
            $traceLines[] = "#{$lineIndex} "
                . (array_key_exists('file', $line) ? $line['file'] : '')
                . (array_key_exists('line', $line) ? "({$line['line']}):" : '[internal function]:')
                . ($lineIndex && array_key_exists('class', $line) ? " {$line['class']}" : '')
                . ($lineIndex && array_key_exists('type', $line) ? "{$line['type']}" : '')
                . ($lineIndex && array_key_exists('function', $line) ? " {$line['function']}" : '')
                . ($lineIndex && array_key_exists('args', $line) ? $paramSummary($line['args']) : '')
                . "";
            //{$line['line']} {$line['class']} {$line['function']} {$line['type']} {$line['args']}";
        }
        return htmlentities(implode("\r\n", $traceLines));
    };

    $errorLookup = function ($errorNo) {
        static $lookupTable = array(
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
		static $simplifyTable = array(
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
        $description =
            array_key_exists($errorNo, $lookupTable)
            ? $lookupTable[$errorNo]
            : (is_numeric($errorNo) ? 'ERROR_NO_' . $errorNo : $errorNo);
        return $description;
    };

    $errorHandler = function(int $errno , string $errstr , ?string $errfile, ?int $errline 
        //, ?array $errcontext
    ) use (&$canister, $formatter, $rando, $errorLookup) {
        //print_r([__FILE__,__LINE__,'dr error handler', $errno, $errstr, $errfile, $errline]);
        $canister['doctorOut']("{$rando}- error handler");
        $canister['doctorOut']();
        
        //$trace = debug_backtrace();
        $trace = '';
        $indent = str_repeat(' ', 8);
        try {
            throw new Exception('debug');
        } catch (Exception $d) {
            $traceArray = $d->getTrace();
            $trace =  str_replace("\n","\n{$indent}", "\n" . $formatter($traceArray));
        }

        //print_r([__FILE__,__LINE__,'dr error handler trace', $trace]);
        $canister['doctorOut'](var_export(
            [
                $errorLookup($errno), 
                $errstr, 
                $errfile, 
                $errline, 
                $trace
            ], true
        ));
        return true;
    };

    // Throwable $e 
    $exceptionHandler = function($e) use (&$canister, $formatter, $rando) {
        $traceIndentString = str_repeat(' ', 8);
        $canister['doctorOut']("{$rando}- exception handler");
        $canister['doctorOut']();
        $stack = [var_export([
            get_class($e),
            $e->getMessage(),
            str_replace("\n","\n{$traceIndentString}", "\n" . $formatter($e->getTrace()))
        ], true)];
        $previous = $e->getPrevious();
        while(!is_null($previous)) {
            $trace = str_replace("\n","\n{$traceIndentString}", "\n" . $formatter($previous->getTrace()));
            $stack[] = var_export([
                get_class($previous), 
                $previous->getMessage(),
                $trace
            ], true);
            $previous = $previous->getPrevious();
        }
        $indent = 0;
        foreach($stack as $exceptionData){
            $canister['doctorOut']($exceptionData, $indent++);
        }        
    };

    // no standard params, whatever is set in shutdownParams
    $shutdownHandler = function() use (&$canister, $startPad, $rando, $errorLookup) { /* $precision,*/
        $canister['doctorOut']("{$rando}- shutdown handler --- ");
        $e = error_get_last();
        if ($e) { //#NOTE traces here won't work since it's an "internal" function
            $canister['doctorOut'](var_export(
                [
                    $errorLookup($e['type']), 
                    $e['message'],
                    $e['file'],
                    $e['line']
                ], true
            ));
        }
        $endTime = microtime(true);
        $timeString = (string)$endTime;
        $pad = $startPad;
            // strpos($timeString, '.') !== 0 
            // ? ($precision - ((strlen($timeString) - strpos($timeString, '.')) - 1))
            // : $precision;
        $currentTime = date('H:i:s d M Y', $endTime);
        $endTimeString = str_pad($timeString, $pad, ' '); #TODO #2.0.0 this is hard coded to current int timestamp length
        $canister['doctorOut']("{$rando}--- end of transaction   --- {$endTimeString} - ({$currentTime}) --- duration {\$d}");
    };

    if (!key_exists('errorHandler', $canister) || !is_null($canister['errorHandler']) ) {
        //print_r([__FILE__,__LINE__,'dr error handler set']);
        set_error_handler(
            (
                key_exists('errorHandler', $canister) && is_callable($canister['errorHandler'])
                ? $canister['errorHandler']
                : $errorHandler
            )//, #TODO allow filtering of types
        );
    }

    if (!key_exists('exceptionHandler', $canister) || !is_null($canister['exceptionHandler']) ) {
        set_exception_handler(
            (
                key_exists('exceptionHandler', $canister) && is_callable($canister['exceptionHandler'])
                ? $canister['exceptionHandler']
                : $exceptionHandler
            )//, #TODO allow filtering of types
        );
    }

    if (!key_exists('shutdownHandler', $canister) || !is_null($canister['shutdownHandler']) ) {
        register_shutdown_function(
            (
                key_exists('shutdownHandler', $canister) && is_callable($canister['shutdownHandler'])
                ? $canister['shutdownHandler']
                : $shutdownHandler
            ),
            ...(
                key_exists('shutdownParams', $canister) 
                ? (
                    is_array($canister['shutdownParams']) 
                    ? $canister['shutdownParams'] 
                    : [$canister['shutdownParams']]
                ) : []
            )
        );
    }

    key_exists('tickHandler', $canister) 
        && is_callable($canister['tickHandler']) 
        && register_tick_function(
            $canister['tickHandler'], 
            ...(
                key_exists('tickParams', $canister) 
                ? (
                    is_array($canister['tickParams']) 
                    ? $canister['tickParams'] 
                    : [$canister['tickParams']]
                ) : []
            )
        );

    if (key_exists('resolverPylon',$canister) && $canister['resolverPylon'] == 'doctor') {
        print_r([__FILE__,__LINE__,'the doctor is in', []]);
    } 
    return 1;
};