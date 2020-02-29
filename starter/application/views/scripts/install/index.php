<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

$applicationName = Zend_Registry::get('language')->get('applicationName', 'Application Name');

?>
<p><?php print($applicationName); ?> is in install mode.</p>
<?php 
if ($this->installAvailable) { 
?>
	<p>You may <a href="<?php print($this->baseUrl); ?>install/start/">begin installation</a>.</p>
<?php 
} 
?>

