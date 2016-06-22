<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Exception class when a user's account exists, but is not valid

*******************************************************************************/

class Saf_Exception_AccountDisorder extends Saf_Exception_Workflow
{
	public function getTitle()
	{
		return 'Assistance Is Required';
	}

	public function getAdditionalInfo()
	{//#TODO #1.5.0 pull from dict
		return '<p>We load university accounts into the scheduling system on '
			. 'a nightly basis. On occasion new staff, students that are not registered '
			. 'for the next semester, and other accounts may encounter access issues.</p>'
			. parent::getAdditionalInfo();
	}
}