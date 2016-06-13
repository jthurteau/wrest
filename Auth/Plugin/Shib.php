<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Auth Plugin Class for Shibboleth integration

*******************************************************************************/
class Saf_Auth_Shib extends Saf_Auth_Plugin_Basic{
	
/*
 *        $idp = $_SERVER['Shib-Identity-Provider'];
        if ($idp === $config->shib->idp->unity){
        	//unity IdP
        	$data = explode("@",$_SERVER['SHIB_CPID']);
        	Zend_Registry::set('uid',(string) $data[0]);
        	//Zend_Registry::set('uid','enter user id here to test');
        	Zend_Registry::set('get',false);
        	Zend_Registry::set('uname',(string)$_SERVER['SHIB_DISPLAYNAME']);
        	Zend_Registry::set('idp', "unity");
        	Zend_Registry::set('logoutUrl', $config->shib->logout->unity);
        	
        }elseif ($idp === $config->shib->idp->fol){
        	//library IdP
        	Zend_Registry::set('uid',(string) $_SERVER['patronid']);
        	Zend_Registry::set('get',false);
        	Zend_Registry::set('uname',(string)$_SERVER['pname']);
        	Zend_Registry::set('idp', "fol");
        	Zend_Registry::set('logoutUrl', $config->shib->logout->fol);
        	
        }else{
        	//shib optional guest
        	Zend_Registry::set('uid','');
        	Zend_Registry::set('get',false);
        	Zend_Registry::set('uname','');
        	Zend_Registry::set('idp', '');
        	Zend_Registry::set('logoutUrl', '');	
        }
*/
}