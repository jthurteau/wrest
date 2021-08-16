$callTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
$caller = key_exists(1, $callTrace) ? $callTrace[1] : null;
$exceptionLine =
    !is_null($caller) && key_exists('line', $caller) 
    ? ":{$caller['line']}"
    : '';
$exceptionPath =
    !is_null($caller) && key_exists('file', $caller)
    ? "{$caller['file']}{$exceptionLine}"
    : (__FILE__ . ':received');
throw new Exception('Gateway Canister Invalid', 126, new Exception($exceptionPath));