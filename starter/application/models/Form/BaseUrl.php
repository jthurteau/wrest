<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Form_BaseUrl //#TODO #1.1.0 move into lib
{
	public function __toString()
	{
		return APPLICATION_BASE_URL;
	}
	
}