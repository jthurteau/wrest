<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Auth Plugin Class for Shibboleth integration

*******************************************************************************/
class Saf_Auth_Plugin_Shib extends Saf_Auth_Plugin_Basic{

	public function auth(){
		$username = $this->getProvidedUsername();
		$idp = $this->getCurrentIdp();
		if (
			$username
			&& $this->allowedIdp($idp)
		) {
			return self::_succeed();
		}
		self::fail();
		return FALSE;
	}

	public function logout()
	{
		return TRUE;
	}

	public function getExternalLoginUrl()
	{
		return '';
	}

	public function getExternalLogoutUrl()
	{
		return '';
	}

	public function getProvidedUsername()
	{
		return
			array_key_exists('usernameField', $this->_config)
				&& array_key_exists($this->_config['usernameField'], $_SERVER)
			? $_SERVER[$this->_config['usernameField']]
			: NULL;
	}

	public function setUsername($username)
	{
		if (array_key_exists('usernameField', $this->_config)) {
			$_SERVER[$this->_config['usernameField']] = $username;
		}
	}

	public function getProvidedPassword()
	{
		return '';
	}

	public function getUserInfo($what = NULL){
		if (is_null($what)) {
			return array(
					Saf_Auth::PLUGIN_INFO_USERNAME => $this->getProvidedUsername(),
					Saf_Auth::PLUGIN_INFO_REALM => 'apache'
			);
		} else {
			switch($what){
				case Saf_Auth::PLUGIN_INFO_USERNAME :
					return $this->getProvidedUsername();
				case Saf_Auth::PLUGIN_INFO_REALM :
					return 'apache';
				default:
					return NULL;
			}
		}
		/*
		 * 	public static function getUserInfo() //#TODO try using preffered
	{
		return array(
			'username' => self::getProvidedUsername(),
			'firstName' => (
				array_key_exists(self::$_conf['firstnameField'], $_SERVER)
				? $_SERVER[self::$_conf['firstnameField']]
				: ''
			),
			'lastName' => (
				array_key_exists(self::$_conf['lastnameField'], $_SERVER)
				? $_SERVER[self::$_conf['lastnameField']]
				: ''
			),
			'email' => (
				array_key_exists(self::$_conf['emailField'], $_SERVER)
				? $_SERVER[self::$_conf['emailField']]
				: ''
			)
		);
	}
		 */
	}

	public function fail()
	{
		if (Saf_Debug::isEnabled()) {
			Saf_Debug::out('Authentication Declined.');
		}
	}

	protected function _succeed()
	{
		//#TODO implement an Saf_Identity class;
		Saf_Auth::setStatus(TRUE, NULL, '');
		return TRUE;
// 		$user = new user();
//
// 		if ('' == trim($username)) {
// 			Rd_Auth::failPlugin();
// 			return false;
// 		}
// 		if (!$user->getUserByUserName($username)) {
// 			if (Rd_Auth::willAutocreateUsers()){
// 				$userInfo = Rd_Auth::getPluginUserInfo();
// 				if (Rd_Auth::createUser($user,$userInfo)) {
// 					Rd_Auth::setStatus(true, $user);
// 					return true;
// 				}
// 				Rd_Auth::setStatus(false, $user, '011');
// 				return false;
// 			} else {
// 				Rd_Auth::setStatus(false, NULL, '009');
// 			}
// 		} else {
// 			Rd_Auth::setStatus(true, $user);
// 			return true;
// 		}
	}

	public function setPluginStatus($success, $errorCode)
	{
		//only some plugins will need to do this.
	}

	public function promptsForInfo()
	{
		return FALSE;
	}

	public function getCurrentIdp()
	{
		return
			array_key_exists('idpField', $this->_config)
			? (
				array_key_exists($this->_config['idpField'],$_SERVER)
				? $_SERVER[$this->_config['idpField']]
				: NULL
			) : NULL;
	}

	public function allowedIdp($idpId)
	{
		$searchIdps = array_key_exists('idpField', $this->_config)
			&& array_key_exists('idps', $this->_config);
		if ($searchIdps) {
			foreach($this->_config['idps'] as $idp=>$idpData){
				if(array_key_exists('allowed', $idpData)
						&& 'false' == strtolower($idpData['allowed'])
						&& array_key_exists('idpId', $idpData)
						&& $idpId == $idpData['idpId']
				){
					return FALSE;
				}
			}
			return TRUE;
		}
	}

	public function getSpLogoutUrl($fullyQualified = FALSE)
	{
		return (
			$fullyQualified
			? APPLICATION_BASE_URL
			: ''
		) .  $this->_config['logoutUrl'];
	}

	public function getIdpLogoutUrl($idpId, $redirectTo = '')
	{
		$searchIdps = array_key_exists('idpField',  $this->_config)
		&& array_key_exists('idps',  $this->_config);
		if ($searchIdps) {
			foreach( $this->_config['idps'] as $idp=>$idpData){
				if(
					array_key_exists('idpLogout', $idpData)
					&& array_key_exists('idpId', $idpData)
					&& $idpId == $idpData['idpId']
				){
					$idpLogout = $idpData['idpLogout'];
				}
			}
		} else {
			throw new Exception('Trying to get logout URL for unknown IDP.');
		}
		return $idpLogout . (
			'' != $redirectTo
			? ('?' .  $this->_config['returnUrlField'] . '=' . $redirectTo)
			: ''
		);
	}

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