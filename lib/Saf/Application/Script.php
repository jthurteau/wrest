<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Application handler for MVC style applications

*******************************************************************************/

class Saf_Application_Script extends Saf_Application{
	
	protected $_path = NULL;
	
	public function __construct($configEnvironment = NULL, $configFilePath = NULL, $autoStart = FALSE)
	{
		$this->_path = $configEnvironment;
	}
	
	public function run(&$request = NULL, &$response = NULL) {
		if (is_array($request)) {
			//#TODO #3.0.0 not supported yet
			throw new Exception('Running scripts with $request as an array not supported.');
			//$fullPath = APPLICATION_PATH . $this->_path;
		} else {
			$fullPath = APPLICATION_PATH . $this->_path . ($request ? " {$request}" : '');
		}
		
		$outputLines = array();
		$status = NULL;
		$return = "Running {$fullPath} \n------------------------------------------------------------\n";
		exec($fullPath, $outputLines, $status);
		$fullResult = implode("\n",$outputLines);
		$statusDescription = $this->_explainStatus($status);
		$return .= (
			is_null($status)
			? "An error occured attempting to run the script.\n"
			: "Status: {$statusDescription}\n"
		) . $fullResult;
		$response = $return;
		return $return;
	}
	
	protected function _explainStatus($code)
	{
		switch($code)
		{
			case 0:
				return 'Script executed.';
			case 1:
				return 'General error.';
			case 2:
				return 'Misuse of Bash.';
			case 126:
				return 'Requested script is not file executable.';
			case 127:
				return 'Requested script does not exist.';
			case 128:
				return 'Invalid exit.';
			case 130:
				return 'Terminated by user signal.';
			case 255:
				return 'Script encountered a fatal error.';
			default:
				return "Unknown Status Result: {$code}";
		}
	}
	
}