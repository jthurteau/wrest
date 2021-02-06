<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Exception class when a required default value is missing.

 *******************************************************************************/

class Saf_Exception_NoDefault extends Exception{
	protected $message = 'Attempted to extract data, requested data missing and no default provided.';
}