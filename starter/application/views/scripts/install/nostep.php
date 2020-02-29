<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

$applicationName = Zend_Registry::get('language')->get('applicationName', 'Application Name');
$header = 'Step ' . htmlentities($this->step) . ' Not Found'
?>
<h2><?php print($header); ?></h2>
<p>Invalid install step requested.</p>
