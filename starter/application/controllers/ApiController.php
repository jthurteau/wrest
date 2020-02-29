<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class ApiController extends Saf_Controller_Zend
{

    public function indexAction()
    {
    	$username = Saf_Auth::getPluginProvidedUsername();
    	$authPlugin = Saf_Auth::getPluginName();
    	$this->view->versionData = array(
    		'success' => TRUE,
    		'authMethod' => $authPlugin,
    		'version' => APPLICATION_VERSION,
    		'id' => APPLICATION_ID,
    		'instance' => APPLICATION_INSTANCE,
    		'environment' => APPLICATION_ENV
    	);
    	if ($username) {
    		$this->view->versionData['authUser'] = $username;
    	}
    	if (!Saf_Layout::formatIsHtml()) {
    		$this->json($this->view->versionData);
    	}
    	Saf_Layout_Location::pushCrumb('System Status');
    }
}
