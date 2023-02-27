<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Exception class when a user's account exists, but is not valid
 */

namespace Saf\Exception;

class AccountDisorder extends Workflow
{
	public function getTitle()
	{
		return 'Assistance Is Required';
	}

	public function getAdditionalInfo()
	{//#TODO #1.5.0 pull from dict
		return '__ACCOUNT_ERROR_MESSAGE__'
		// return '<p>We load university accounts into the scheduling system on '
		// 	. 'a nightly basis. On occasion new staff, students that are not registered '
		// 	. 'for the current semester, and other accounts may encounter access issues.</p>'
			. parent::getAdditionalInfo();
	}
}