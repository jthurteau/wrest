<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

use Saf\Environment\Define;
use Saf\Exception\Redirect;
use Saf\Exception\NotImplemented;

class IndexController extends Saf_Controller_Zend
{

    public function indexAction()
    {
    	//$this->_forward('action', 'controller', NULL);
    }
    
    public function loginAction()
    {
    	Define::load('APPLICATION_SIMULATED_USER', '');
    	$request = $this->getRequest();
    	if (APPLICATION_SIMULATED_USER) {
    		Saf_Auth::autodetect(Saf_Auth::MODE_SIMULATED);
    		$url = trim($request->getParam('forwardUrl'));
    		$url = 
    			$url
    			? Saf_UrlRewrite::decodeForward($url)
    			: Saf_UrlRewrite::decodeForward(trim($request->getParam('forwardCode')));
    		throw new Redirect($url);
    	}
    	throw new NotImplemented('Please Login Using Shibboleth.'); //#TODO #2.0.0 roll this into the framework/language
    }
    
    public function logoutAction()
    {
    	Saf_Auth::logout();
    	throw new Redirect('?loggedout=true');
    }
}
