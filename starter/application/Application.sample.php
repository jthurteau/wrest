<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Application extends Saf_Application
{
	public function run($request = NULL)
	{
    	$something = 'world';
    	print('hello ' . __FILE__);
    	return $something;
	}
}
