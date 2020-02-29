<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Sample extends Saf_Application_Mvc
{
	public function run(&$request = NULL, &$response = NULL)
	{
    	$something = 'world';
    	print('hello ' . __FILE__);
    	return $something;
	}
}
