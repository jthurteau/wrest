<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

/**
 *  This view script is designed to run both within a View object (using $this to 
 *  access the model), and inside the scope of a function with the model as local
 *  values.
 */
$debugMode =
    class_exists('Saf_Debug', FALSE)
    ? Saf_Debug::isEnabled()
    : (
        defined('APPLICATION_ENV')
        && strpos(APPLICATION_ENV, 'development') !== FALSE
    );
if (!isset($caughtLevel)) {
	$caughtLevel = (isset($this->caughtLevel) ? $this->caughtLevel : 'APPLICATION');
}
$userFriendlyCaughtLevel = ucwords(strtolower($caughtLevel));
if (!isset($rootUrl)) {
	$rootUrl = Zend_Registry::get('baseUrl');
}
if (!isset($title)) {
	$title = isset($this->title) ? $this->title : "{$userFriendlyCaughtLevel} Error";
}
if (!isset($e) ) {
	$e = isset($this) ? $this->exception : new Exception('No Exception Provided.');
}
if (!isset($additionalError)) {
	$requestProtocol = APPLICATION_PROTOCOL . '://';
	$host = APPLICATION_HOST;
	$optionalPort = (
		APPLICATION_SUGGESTED_PORT
		? (':' .  APPLICATION_SUGGESTED_PORT)
		: ''
	);
	if ($caughtLevel == 'APPLICATION') {
		$where = 'inside of the main application';
	} else if ($caughtLevel == 'BOOTSTRAP') {
		$where = 'outside of the main application';
	} else {
		$where = "in the {$userFriendlyCaughtLevel} module";
	}
	$additionalError = (
		isset($this) && isset($this->additionalError)
		? $this->additionalError
		: '<p>' . APPLICATION_BASE_ERROR_MESSAGE . '</p>'
			. '<p>Additional information that may help diagnose the problem:</p>'
		       	. "<ul><li>Requested URL: {$requestProtocol}{$host}{$optionalPort}{$_SERVER['REQUEST_URI']}</li>"

			. (array_key_exists('HTTP_REFERER', $_SERVER) ? "<li>Referring URL: {$_SERVER['HTTP_REFERER']}</li>" : '')
			. "<li>Your IP: {$_SERVER['REMOTE_ADDR']}</li>"
			. "<li>Your Browser ID: {$_SERVER['HTTP_USER_AGENT']}</li>"
			. "<li>This error was caught {$where}.</li></ul>"
	);
}

if ('json' == Saf_Layout::getFormat()) {
	$response = array(
		'success' => FALSE,
		'message' => $e->getMessage(),
		'caughtBy' => $caughtLevel
	);
	if (isset($additionalError)) {
		$response['additionalInfo'] = $additionalError;
	}

    if ($debugMode) {
		$response['trace'] = $e->getTraceAsString();
		$response['exception_type'] = get_class($e);
		$previous = $e->getPrevious();
		$currentTarget = $response;
		while($previous) {
			$response['previous_exception'] = array();
			$currentTarget = $currentTarget['previous_exception'];
			$currentTarget['trace'] = $previous->getTraceAsString(); //TODO #2.0.0 add a utility function to Saf_Debug to eliminate the output of duplicated trace points.
			$currentTarget['exception_type'] = get_class($previous);
			$currentTarget['message'] = $previous->getMessage();
			$previous = $previous->getPrevious();
		}
    }
    print(json_encode($response, JSON_FORCE_OBJECT)); //#TODO #2.0.0 format the JSON
    die();
} else if ('text' == Saf_Layout::getFormat()) {
    die(
        (
            isset($additionalError)
            ? ($e->getMessage() . "\n" . $additionalError)
            : $e->getMessage()
        ) . "\nCaught in {$caughtLevel}"
        . (
            $debugMode
            ? ("\nTrace: " . $e->getTraceAsString()) 
            : "\nEnable debugging for more information.\n\n"
        )              
    );
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php print($title); ?></title>
<?php 
	if (FALSE) {
?>
    <link href="<?php print($rootUrl);?>public/css/exception.css" rel="stylesheet" type="text/css" media="screen">

<?php 
	}
?>    
</head>
<body>
<?php 
if ($caughtLevel !== 'APPLICATION'){
?>	
    <h1><?php print($title); ?></h1>
<?php 
} else {
?>
	<h2><?php print($title); ?></h2>
<?php
}
if (get_class($e) != 'Saf_Exception_Redirect') {
?>
    <p class="exceptionMessage"><?php print($e->getMessage()); ?></p>
<?php 
}
?>
    <div class="additionalInformation"><?php print($additionalError); ?></div>
<?php
if ($debugMode) { 
?>
    <h2 class="exceptionClass"><?php print(get_class($e)); ?></h2>
    <pre class="exceptionTrace"><?php print($e->getTraceAsString()); ?></pre>
<?php 
	$previous = $e->getPrevious();
	if ($previous) {
?>
    <p>Additional exceptions detected.</p>
    <ul>
<?php 
		while($previous) {
?>
    	<li>
    		<h3><?php print(get_class($previous));?></h3>
    		<p class="exceptionMessage">
			<pre><code><?php print(html_entity_decode($previous->getMessage())); ?></code></pre></p>
			<pre class="exceptionTrace"><?php print($previous->getTraceAsString()); ?></pre>   	
    	</li>
<?php 
			$previous = $previous->getPrevious();
		}
?>
    </ul>
<?php
	}
} 
?>
</body>
</html>

