<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Ldap connect,search,edit object for basic manipulation of LDAP

*******************************************************************************/

class Saf_Client_Ldap 
{

	/**
	 * The object's connection resource.
	 *
	 * @var resource
	 */
	protected $_connection = NULL;
	
	/**
	 * The object's connection status.
	 *
	 * @var bool
	 */
	protected $_connected = FALSE;
	
	/**
	 * Indicates a secure connection was made.
	 *
	 * @var bool
	 */
	protected $_connectedSecurely = FALSE;
	
	/**
	 * The object's binding status.
	 *
	 * @var bool
	 */
	protected $_bound = FALSE;
	
	/**
	 * The object's setup status.
	 *
	 * @var bool
	 */
	protected $_setup = FALSE;
	
	/**
	 * Array of errors (strings) encountered by the object.
	 *
	 * @var array
	 */
	protected $_error = array();	
	
	/**
	 * Array of results from searches. If one search is performed it is an array of all results returned. If more than one 
	 * search is performed this array nests and each set of results is indexed numerically by the order the queries are performed in.
	 *
	 * @var array
	 */
	protected $_results = array();	
	
	/**
	 * The search string to be performed. If empty, it will attempt to lookup the information of the currenly 
	 * logged in user via LDAP , WRAP, or basic Auth (in that order) if available. If no user information is
	 * available it will return no records.
	 *
	 * @var string
	 */
	protected $_search = '';	
	
	/**
	 * The DN bind string to use when connecting to the DB.
	 *
	 * @var string
	 */
	protected $_context = '';
	
	/**
	 * An array of attributes (string names matching attributes) to be requested by each search. 
	 * When this is an empty array all attributes are requested.
	 *
	 * @var array
	 */
	protected $_attributes = array();	
	
	/**
	 * The server hostname or IP address to connect to.
	 *
	 * @var string
	 */
	protected $_remoteAddress = '';		
	
	/**
	 * The server port to connect to.
	 *
	 * @var string
	 */
	protected $_remotePort = '';	
	
	/**
	 * The server protocol to connect with.
	 *
	 * @var string
	 */
	protected $_remoteProtocol = 'ldap://';
	
	/**
	 * The login name to use when attempting an anthenticated connetion.
	 *
	 * @var string
	 */
	protected $_remoteLogin = '';	
	
	/**
	 * The login password to use when attempting an anthenticated connetion.
	 *
	 * @var string
	 */
	protected $_remotePassword = '';	
	
	/**
	 * The allows the object to send login and password data in clear text if true.
	 *
	 * @var bool
	 */
	protected $_allowInsecureAuth = FALSE;
	
	/**
	 * Sets the ldap protocol version to be used.
	 *
	 * @var bool
	 */
	protected $_ldapVersion = 3;	
									
	/**
	 * Create a new Ldap connection adapter. Accepts a config array, or a connection 
	 * address string and context with optional login Id and password.
	 */
	public function __construct($addressOrConfig, $context = '', $login = '', $password = '' )
	{
		Saf_Debug::outData(array($addressOrConfig, $context, $login, $password));
		if(is_array($addressOrConfig)){
			$this->_setupConnection(
				$addressOrConfig['params']['host'],
				$addressOrConfig['params']['bind'],
				$addressOrConfig['params']['login'],
				$addressOrConfig['params']['pass']
			);
			$this->_search = array_key_exists('search', $addressOrConfig['params']) ? $addressOrConfig['params']['search'] :  '';
			if(array_key_exists('secure', $addressOrConfig['params']) && Saf_Filter_Truthy::filter($addressOrConfig['params']['secure'])){
				$this->secureConnection();
			} else {
				$this->allowInsecureAuth();
				$this->unsecureConnection();
			}
			$this->_attributes = array_values($addressOrConfig['map']);
		} else {
			$this->_setupConnection($addressOrConfig, $context, $login, $password);
			$this->unsecureConnection(); //#TODO #2.0.0 should detect this from the address...
			$this->_setupConnection();
		}
	}
	
	/**
	 * Uses the provided information to prepare the object for a connection.
	 */
	private function _setupConnection($address, $context, $login = '', $password = '')
	{
		if (strpos($address,":") !== FALSE){
			$address = explode(":", $address, 2);
			$port = ':' . $address[1];
			$address = $address[0];
		}
		$this->_remoteAddress = $address;
		if (isset($port) ){
			$this->_remotePort = $port;
		}
		$this->_context	 = $context;
		if ($login !== FALSE){
			if (!$this->_allowInsecureAuth){
				$this->_remoteProtocol = 'ldaps://';
			}
			$this->_remoteLogin = $login;
			$this->_remotePassword = $password;
		} else {
			$this->_remoteLogin = '';
			$this->_remotePassword = '';			
		}
		$this->_setup = TRUE;
	}			
	
	/**
	 * If the object is discarded, it will make sure the connection is closed.
	 */
	public function __destruct()
	{
		$this->close();
	}
	 
	/**
     * Closes the current connection if any.
     */	 
	public function close( )
	{
		if ($this->_connection !== NULL){
			ldap_unbind($this->_connection);
			$this->_connection = NULL;
			$this->_connected = FALSE;
			$this->_connectedSecurely = FALSE;
			$this->_bound = FALSE;
		}
	}	

	/**
	 * Returns a list of the results from each search (in order) as a serialized string. 
	 */
	public function __toString()
	{
		return serialize($this->_results);
	}

	/**
     * Opens a connection. Address, context, login Id and password can be 
     * specified optionally. If they are they will replace the previously 
     * stored options. If they are not specified, the most recently supplied 
     * options will be used instead.
     */ 
	public function open($address = '', $context = '', $login = FALSE, $password = '')
	{
		$this->close();
		if ($address == ''){
			$address = $this->_remoteAddress;
			$this->_setup = FALSE;
		}
		if ($context == ''){
			$this->_context = $context;
			$this->_setup = FALSE;
		}
		if ($login !== FALSE){
			$this->_remoteLogin = $login;
			$this->_setup = FALSE;
		}
		if ($password !== ''){
			$this->_remotePassword = $password;
			$this->_setup = FALSE;
		}
		return($this->getConnection());
	}
	
	/**
	 * Opens a connection with the most recently specified options.
	 */
	public function getConnection()
	{		
		$this->close();
		if (!$this->_setup){
			$this->_setupConnection($this->_remoteAddress, $this->_context, $this->_remoteLogin, $this->_remotePassword);
		}
		$this->_connection = ldap_connect($this->_remoteProtocol.$this->_remoteAddress.$this->_remotePort);
		if (!$this->_connection && $this->_remoteProtocol == 'ldap://'){
			$this->_connection = ldap_connect($this->_remoteAddress.$this->_remotePort); // try again with support for native sun ldap module.
		}
		
		if ($this->_connection){
			$this->_connected = TRUE;
			$this->_connectedSecurely = ($this->_remoteProtocol == 'ldaps://');
			$versionSet = ldap_set_option($this->_connection, LDAP_OPT_PROTOCOL_VERSION, $this->_ldapVersion);
			
			if (!$versionSet){
				$errorMessage = '';
				ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);
				$this->_error[] = "Failed to set the protocol version: " 
					. $errorMessage . " " 
					. ldap_error( $this->_connection);
				throw new NcsuLib_Exception($this->_error[]);
			}
			
			return TRUE;
		}
				
		$this->_error[] = "Unable to connect to the LDAP server: " 
			. $this->_remoteProtocol.$this->_remoteAddress.$this->_remotePort; 
		$this->_connection = NULL;
		throw new Exception($this->_error[]);
		
	}	
	
	/**
	 * Attempt to bind after connecting. If no login and password are supplied, 
	 * the most recently provided login and password are used. Binding is normally
	 * handled by the adapter, but this method makes it possible to manually
	 * bind. Functions that require a binding will only attempt to bind if there
	 * is not already an existing binding.
	 */
	public function bind($login='', $password='')
	{
		if (!$this->_connected ){
			$this->_error[] = "Attempting to bind when not connected";		
			return FALSE;
		}		
		if ($login != '') {
			$this->_remoteLogin = $login;
			$this->remotePassword = $password;
		}
		if ($this->_remoteLogin != '' && ($this->_connectedSecurely || $this->_allowInsecureAuth)){
			if ($this->_allowInsecureAuth) {
				$this->_error[] = "Sent login and password over clear text because it was explicitly requested. ";
			}
			$this->_bound = ldap_bind($this->_connection, $this->_remoteLogin, $this->_remotePassword);
			Saf_Debug::outData(array($this->_remoteLogin, $this->_remotePassword, $this->_context,$this->_bound));
			
		} else {
			if ($this->_remoteLogin != '') {
				$this->_error[] = 'Attempted to login with authentication via '
					. $this->_remoteProtocol . $this->_remoteAddress . $this->_remotePort
					. "with login {$this->_remoteLogin} but was not connected securely so "
					. 'anonymous access was used instead. ';
			}
			$this->_bound = ldap_bind($this->_connection);
		}
		
		if ($this->_bound) { //#TODO #2.0.0 it seems as if sometimes AD will return true for bind even when it fails. Not sure how to detect this pre-search
			return TRUE;
		}
		
		$errorMessage = '';
		ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);
		$this->_error[] = "Unable to bind to LDAP {$this->_remoteProtocol}{$this->_remoteAddress}{$this->_remotePort} : " 
			. $errorMessage . ", " 
			. ldap_error($this->_connection);
		$this->close();
		return FALSE;
	}
	
	/**
	 * Performs a search and returns resulting records. Add the results to a 
	 * cummlative list of all search results.
	 */	
	public function search($searchString, $newContext = '')
	{
		if ($newContext != ''){
			$this->_context = $newContext;
		}
		if ($searchString == ""){
			$this->_error[] = "No search criteria." ;
			return FALSE;
		}	
		if (!$this->_bound){
			$this->bind();
			if (!$this->_bound){
				return FALSE;
			}
		}
	
		if (count($this->_attributes) !== 0){
			$ldapSearchResult = ldap_search($this->_connection,$this->_context,$searchString,$this->_attributes);
		} else {
			$ldapSearchResult = ldap_search($this->_connection,$this->_context,$searchString);
		}
		
		if ($ldapSearchResult){
			if (ldap_count_entries($this->_connection, $ldapSearchResult) > 0){
				$entries = ldap_get_entries($this->_connection, $ldapSearchResult);
				if ($entries["count"] > 0 ){
					$this->_results[] = $entries;
					return $entries;
				} else {
					$errorMessage = '';
					ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);
					$this->_error[] = "No entries returned.  " 
						. $errorMessage . " " 
						. ldap_error( $this->_connection);
					return array();				
				}		
			} else {
				$errorMessage = '';
				ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);
				$this->_error[] = "No matches found.  " 
					. $errorMessage . " " 
					. ldap_error( $this->_connection);
				return array();
			}		
		}

		$errorMessage = '';
		ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);
		$this->_error[] = "Could not perform LDAP search: " 
			. $errorMessage . " " 
			. ldap_error( $this->_connection);
		return FALSE;
	}	
	
	/**
	 * Sets the adapter to connect over a secure connection. If it is currently 
	 * connected in an insecure mannner (clear text) it will disconnect.
	 */
	public function secureConnection()
	{
		if ($this->_remoteProtocol != 'ldaps://'){
			$this->close();
			$this->_remoteProtocol = 'ldaps://';
		}
	}

	/**
	 * Sets the adapter to connect over a clear text (unsecure) connection. 
	 * If it is currently connected in an secure mannner it will disconnect.
	 */	
	public function unsecureConnection()
	{
		if ($this->_remoteProtocol != 'ldap://'){
			$this->close();
			$this->_remoteProtocol = 'ldap://';
		}
	}	

	/**
	 * Sets the adapter to allow sending of login and password over clear text. 
	 * Normally it will refuse. Once set, it cannot be reverted.
	 */
	public function allowInsecureAuth()
	{
		$this->_allowInsecureAuth = TRUE;
	}

	public function setContext($context)
	{
		$this->_context = $context;
	}


	public function getContext()
	{
		return $this->_context;
	}
	
	public function setLimit($limit)
	{
		//ldap_set_option($this->_connection, LDAP_OPT_DEREF, LDAP_DEREF_ALWAYS);
		ldap_set_option($this->_connection, LDAP_OPT_SIZELIMIT, $limit);
	}
	
	/**
	 * Accepts an array and sets the attributes that will be requested for 
	 * each search.
	 */
	public function setAttributes($array)
	{
		if (is_object($array) && method_exists($array,'toArray')){
			$array = $array->toArray();
		} else if (!is_array($array)){
			$array = array($array);
		}
		$this->_attributes = $array;
	}
	
	/**
	 * Returns the current list of attributes.
	 */
	public function getAttributes()
	{
		return($this->_attributes);
	}
	
	/**
	 * Returns the current list of attributes.
	 */
	public function clearAttributes()
	{
		$this->_attributes = NULL;
	}
	
	/**
	 * Returns the string of all errors encountered by the adapter.
	 */
	public function getErrors()
	{
		return $this->_error;
	}
	
	/**
	 * Returns the list of all results from each search. Even if only one search has been
	 * performed, it will be nested in an array by itself.
	 */
	public function getAllResults()
	{
		return $this->_results;
	}

	/**
	 * Returns the most recent results.
	 */
	public function getResults()
	{
		return end($this->_results);
	}
	
/*	public function add($dnString, $attributeArray)
	{
		if (!$this->_bound){
			$this->bind();
			if(!$this->_bound){
				return false;
			}
		}
		print_r(array($dnString, $attributeArray));
		$result = ldap_add($this->_connection, $dnString, $attributeArray);
		if ($result) {
			print_r($result);
			return true;
		} else {
			$errorMessage = '';
			ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);		
			$this->_error[] = "Could not add record to LDAP: " 
				. $errorMessage . " " 
				. ldap_error($this->_connection);
			print_r($this->_error);
			return false;
		}
	}
*/
	
	/**
	 * Accepts an array of values indexed by attribute name and an optional
	 * context which should be a dn (Distinguished Name, i.e. unique). If
	 * no dn string is spefified it will attempt to use the current context.
	 * If a dn is specified it will replace the current context. Note that
	 * as the name implies this function ADDS the value to record, if the record
	 * already has a value you must use editEntry().
	 */
	public function addToEntry($attributeArray, $dnString='')
	{
		if ($dnString == ''){
			$dnString = $this->_context;
		}
		if (!$this->_bound){
			$this->bind();
			if(!$this->_bound){
				return FALSE;
			}
		}
		$result = ldap_mod_add($this->_connection, $dnString, $attributeArray);
		if ($result) {
			return TRUE;
		} else {
			$errorMessage = '';
			ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);		
			$this->_error[] = "Could not add record to LDAP: " 
				. $errorMessage . " " 
				. ldap_error($this->_connection);
			return FALSE;
		}
	}
	
	/**
	 * Accepts an array of values indexed by attribute name and an optional
	 * context which should be a dn (Distinguished Name, i.e. unique). If
	 * no dn string is spefified it will attempt to use the current context.
	 * If a dn is specified it will replace the current context. Note that
	 * as the name implies this function REPLACES the value at the attribute.
	 */
	public function editEntry($attributeArray, $dnString='')
	{
		if ($dnString == ''){
			$dnString = $this->_context;
		}
		if (!$this->_bound){
			$this->bind();
			if(!$this->_bound){
				return FALSE;
			}
		}
		$result = ldap_mod_replace($this->_connection, $dnString, $attributeArray);
		if ($result) {
			return TRUE;
		} else {
			$errorMessage = '';
			ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);		
			$this->_error[] = "Could not add record to LDAP: " 
				. $errorMessage . " " 
				. ldap_error( $this->_connection);
			return FALSE;
		}
	}
	
	/**
	 * Accepts an array of values indexed by attribute name and an optional
	 * context which should be a dn (Distinguished Name, i.e. unique). If
	 * no dn string is spefified it will attempt to use the current context.
	 * If a dn is specified it will replace the current context. Note that
	 * as the name implies this function REMOVES the value from the record, 
	 * the exact value specified must exist and it may only be deleted if
	 * the attribute is not optional, or if there are multiple values for that
	 * attribute.
	 */
	public function removeFromEntry($attributeArray, $dnString='')
	{
		if ($dnString == ''){
			$dnString = $this->_context;
		}
		if (!$this->_bound){
			$this->bind();
			if(!$this->_bound){
				return FALSE;
			}
		}
		ob_start();
		$result = ldap_mod_del($this->_connection, $dnString, $attributeArray);
		ob_clean();
		if ($result) {
			return TRUE;
		} else {
			$errorMessage = '';
			ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);		
			$this->_error[] = "Could not add record to LDAP: " 
				. $errorMessage . " " 
				. ldap_error($this->_connection);
			return FALSE;
		}
	}	
	
	/**
	 * Creates a new record with the specified attributes at the specified
	 * dn string. If no dn string is spefified it will attempt to use the 
	 * current context. If a dn is specified it will replace the current 
	 * context. 
	 */	
	public function addEntry($attributeArray, $dnString='')
	{
		if ($dnString == ''){
			$dnString = $this->_context;
		}
		if (!$this->_bound){
			$this->bind();
			if(!$this->_bound){
				return FALSE;
			}
		}
		$result = ldap_add($this->_connection, $dnString, $attributeArray);
		if ($result) {
			return TRUE;
		} else {
			$errorMessage = '';
			ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);		
			$this->_error[] = "Could not add record to LDAP: " 
				. $errorMessage . " " 
				. ldap_error( $this->_connection);
			return FALSE;
		}
	}			

	/**
	 * Removes the record at the specified dn string. If no dn string is 
	 * spefified it will attempt to use the current context. If a dn is 
	 * specified it will replace the current context. 
	 */	
	public function removeEntry($dnString='')
	{
		if ($dnString == ''){
			$dnString = $this->_context;
		}
		if (!$this->_bound){
			$this->bind();
			if(!$this->_bound){
				return FALSE;
			}
		}
		$result = ldap_delete($this->_connection, $dnString);
		if ($result) {
			return TRUE;
		} else {
			$errorMessage = '';
			ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);		
			$this->_error[] = "Could not add record to LDAP: " 
				. $errorMessage . " " 
				. ldap_error( $this->_connection);
			return FALSE;
		}
	}	
	
	/**
	 * Returns a list of attributes on records at the current context, or the context specified.
	 * Additionally, a type to search (ou=...) may be speficied. If you want to specify the 
	 * type to search, but want to keep the current context pass an empty string for the context.
	 * This method will use the list of attributes to search to filter what is returned, and will 
	 * return everything that is found if the list of attritues is empty (which is the default).
	 */
	public function listValues($attribute, $newContext = '', $typeToSearch = 'ou=*')
	{
		if ($newContext != ''){
			$this->_context = $newContext;
		}
		if (!$this->_bound){
			$this->bind();
			if(!$this->_bound){
				return FALSE;
			}
		}
		$ldapListResult = ldap_list($this->_connection, $this->_context, $typeToSearch , array($attribute));

		if ($ldapListResult){
			if (ldap_count_entries($this->_connection, $ldapListResult) > 0){
				$entries = ldap_get_entries($this->_connection, $ldapListResult);
				if ($entries["count"] > 0 ){
					$this->_results[] = $entries;
					return $entries;
				} else{
					$errorMessage = '';
					ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);
					$this->_error[] = "No entries returned.  " 
						. $errorMessage . " " 
						. ldap_error( $this->_connection);
					return array();				
				}		
			} else {
				$errorMessage = '';
				ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);
				$this->_error[] = "No matches found.  " 
					. $errorMessage . " " 
					. ldap_error( $this->_connection);
				return array();
			}		
		}
		$errorMessage = '';
		ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorMessage);
		$this->_error[] = "Could not perform LDAP list: " 
			. $errorMessage . " " 
			. ldap_error( $this->_connection);
		return FALSE;
	}
}
