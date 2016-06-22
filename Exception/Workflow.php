<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base class for exceptions that should provide user help

*******************************************************************************/

class Saf_Exception_Workflow extends Exception
{
	public function getTitle()
	{
		return 'Prerequisite Unmet';
	}

	public function getAdditionalInfo()
	{//#TODO #1.5.0 pull from dict
		return "<p>Please refer to NCSU Libraries <a href=\"https://www.lib.ncsu.edu/askus\">\"Ask Us\"</a> for help with this issue.</p>";
	}
}