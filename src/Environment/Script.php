<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Envionment adapter for CLI Applications
 */

namespace Saf\Environment;

use Saf\Exception\NotImplemented;

class Script { // extends Saf_Application{

    public const ERROR_CODES = [
        0 => 'Script executed.',
        1 => 'General error.',
        2 => 'Misuse of Bash.',
        126 => 'Requested script is not file executable.',
        127 => 'Requested script does not exist.',
        128 => 'Invalid exit.',
        130 => 'Terminated by user signal.',
        255 => 'Script encountered a fatal error.',
    ];

    protected $path = null;
    
    public function __construct(string $path) //$configEnvironment = NULL, $configFilePath = NULL, $autoStart = false)
    {
        $this->path = $path;
    }
    
    public function run(null|string|array $request = null, ?string &$response = null, ?int &$status = null)
    {
        if (is_array($request)) { //#TODO #3.0.0 not supported yet
            throw new NotImplemented('Running scripts with $request as an array not supported.');
        } else {
            $command = $this->path . ($request ? " {$request}" : '');
        }
        print_r([__FILE__,__LINE__,$request]); die;
        $outputLines = [];
        $status = null;
        $return = "Running {$command} \n------------------------------------------------------------\n";
        exec($command, $outputLines, $status);
        $fullResult = implode("\n",$outputLines);
        $statusDescription = self::explainStatus($status);
        $return .= (
            is_null($status)
            ? "An error occured attempting to run the script.\n"
            : "Status: {$statusDescription}\n"
        ) . $fullResult;
        $response = $return;
        return $return;
    }
    
    protected static function explainStatus($code)
    {
        return 
            key_exists($code, self::ERROR_CODES)
            ? self::ERROR_CODES[$code]
            : "Unknown Status Result: {$code}";
    }
    
}