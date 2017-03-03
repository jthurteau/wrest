<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Exception class when the app is not configured to do something

*******************************************************************************/
require_once(LIBRARY_PATH . '/Saf/Exception/Workflow.php');

class Saf_Exception_NotConfigured extends Saf_Exception_Workflow
{
	public function getTitle()
	{
		return 'Not Configured';
	}
	
	public function getAdditionalInfo()
	{
		return '<p>Please inform your system administrator.</p>';
	}
}