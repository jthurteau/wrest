<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Exception class when a value that is expected to be an array can't be converted.

 *******************************************************************************/

class Saf_Exception_NotAnArray extends Exception{
	protected $message = 'Attempted to extract data, search target was not an array, could not be converted to an array.';
}