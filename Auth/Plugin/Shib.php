<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Auth Plugin Class for Shibboleth integration

*******************************************************************************/
class Saf_Auth_Plugin_Shib extends Saf_Auth_Plugin_Basic{
	
	protected $_pluginName = 'Shibboleth Auth';

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
}