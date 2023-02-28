<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for exceptions that should provide user help
 */

 namespace Saf\Exception;

class Workflow extends \Exception
{
	public function getTitle()
	{
		return 'Prerequisite Unmet';
	}

	public function getAdditionalInfo()
	{//#TODO #2.1.0 pull from dict
		return \Saf\APPLICATION_BASE_ERROR_MESSAGE;
	}
}