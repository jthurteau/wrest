<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Indicates an exception that should be shown to the user 
(but does not replace the default support message like Saf_Exception_Workflow

*******************************************************************************/

class Saf_Exception_Startup extends Exception {

	public function getTitle()
	{
		return 'An Error Occured During Startup';
	}

	public function getAdditionalInfo()
	{//#TODO #1.5.0 pull from dict
		return APPLICATION_BASE_ERROR_MESSAGE;
	}
}
