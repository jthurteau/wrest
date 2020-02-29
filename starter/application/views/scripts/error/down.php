<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

$applicationName = Zend_Registry::get('language')->get('applicationName', 'Application Name');
?>   
<p><?php print($applicationName); ?> is currently down for maintenance.</p>

