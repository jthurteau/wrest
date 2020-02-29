<?php //#SCOPE_NCSU_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Acl extends Saf_Acl
{
	public function who($module, $controller = 'index', $action = 'index' , $stack = array())
	{
		$defaultAcl = parent::who($module, $controller, $action, $stack);		
		return 
			Saf_Acl::ACL_WHO_UNKNOWN == $defaultAcl
			? Saf_Acl::ACL_WHO_ANYUSER
			: $defaultAcl;
	}
}