<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class TestController extends Saf_Controller_Action
{

    public function indexAction()
    {
		phpinfo();
		die;
    }
}
