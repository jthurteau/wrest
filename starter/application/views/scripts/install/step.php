<?php
$applicationName = Zend_Registry::get('language')->get('applicationName', 'Application Name');
$header = (
	$this->step
	? "Step {$this->step}"
	: "Installing {$applicationName}"
);
?>
<h2><?php print($header); ?></h2>
<?php print($this->results); ?>

