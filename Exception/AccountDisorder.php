<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Exception class when a user's account exists, but is not valid

*******************************************************************************/

class Saf_Exception_AccountDisorder extends Saf_Exception_Workflow
{
	public function getAdditionalInfo()
	{
		return "<p>We load university users into the scheduling system on "
		. "a nightly basis. On occasion new users, students that are not registered "
		. "for the next sememster, and other access issues can occur.</p>"
			. parent::getAdditionalInfo();
	}
}