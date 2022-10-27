<?php 
/**
 * Ldap Client Adapter
 * PHP version 8
 * 
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 */

namespace Saf\Client\Ldap;

use LDAP\Connection;

Class Adapter 
{
    /**
     * The object's connection resource.
     */
    protected ?Connection $connection = null;
    
    /**
     * The object's connection status.
     */
    protected bool $connected = false;    
    
    /**
     * Indicates a secure connection was made.
     */
    protected bool $connectedSecurely = false;    
    
    /**
     * The object's binding status.
     */
    protected bool $bound = false;        
    
    /**
     * The object's setup status.
     */
    protected bool $setup = false;        
    
    /**
     * Array of errors (strings) encountered by the object.
     */
    protected array $error = [];
    
    /**
     * Array of results from searches. If one search is performed it is an array of all results returned. If more than one 
     * search is performed this array nests and each set of results is indexed numerically by the order the queries are performed in.
     */
    protected array $results = [];
    
    /**
     * The search string to be performed. If empty, it will attempt to lookup the information of the currenly 
     * logged in user via LDAP , WRAP, or basic Auth (in that order) if available. If no user information is
     * available it will return no records.
     */
    protected string $search = '';    
    
    /**
     * The DN bind string to use when connecting to the DB.
     */
    protected string $context = '';
    
    /**
     * An array of attributes (string names matching attributes) to be requested by each search. 
     * When this is an empty array all attributes are requested.
     */
    protected array $attributes = [];    
    
    /**
     * The server hostname or IP address to connect to.
     */
    protected string $remoteAddress = '';        
    
    /**
     * The server port to connect to.
     */
    protected string $remotePort = '';    
    
    /**
     * The server protocol to connect with.
     */
    protected string $remoteProtocol = 'ldap://';
    
    /**
     * The login name to use when attempting an anthenticated connetion.
     */
    protected string$remoteLogin = '';    
    
    /**
     * The login password to use when attempting an anthenticated connetion.
     */
    protected string $remotePassword = '';    
    
    /**
     * The allows the object to send login and password data in clear text if true.
     */
    protected bool $allowInsecureAuth = false;        
    
    /**
     * Sets the ldap protocol version to be used.
     */
    protected int $ldapVersion = 3;    
                                    
    /**
     * Create a new Ldap connection adapter. Accepts a config array, or a connection 
     * address string and context with optional login Id and password.
     */
    public function __construct($addressOrConfig, $context='', $login='', $password='' )
    {
        if(is_array($addressOrConfig) || ($addressOrConfig instanceOf \ArrayAccess)){
            $host = 
                key_exists('host', $addressOrConfig) 
                ? $addressOrConfig['host']
                : 'localhost';
            key_exists('bind', $addressOrConfig) && '' == trim($context)
                && ($context = $addressOrConfig['bind']);
            key_exists('login', $addressOrConfig) && '' == trim($login)
                && ($login = $addressOrConfig['login']);
            key_exists('pass', $addressOrConfig) && '' == trim($password)
                && ($password = $addressOrConfig['pass']);
            key_exists('password', $addressOrConfig) && '' == trim($password)
                && ($password = $addressOrConfig['password']);
            $this->setupConnection($host, $context, $login, $password);
            key_exists('search', $addressOrConfig) 
                && ($this->search = $addressOrConfig['search']);
            key_exists('secure', $addressOrConfig) && !$addressOrConfig['secure']
                && ($this->allowInsecureAuth());
            if (
                key_exists('attributes', $addressOrConfig) 
                && is_array($addressOrConfig['attributes'])
            )  {
                $this->attributes = array_keys($addressOrConfig['attributes']);
            }
        } else {
            $this->setupConnection($addressOrConfig, $context, $login, $password);    
        }
    }
    
    /**
     * Uses the provided information to prepare the object for a connection.
     */
    private function setupConnection($address, $context, $login = '', $password = '')
    {
        if (strpos($address,":") !== false){
            $address = explode(":", $address, 2);
            $port = ':' . $address[1];
            $address = $address[0];
        }
        $this->remoteAddress = $address;
        if (isset($port) ){
            $this->remotePort = $port;
        }
        $this->context     = $context;
        if ($login !== false){
            if (!$this->allowInsecureAuth){
                $this->remoteProtocol = 'ldaps://';
            }
            $this->remoteLogin = $login;
            $this->remotePassword = $password;
        } else {
            $this->remoteLogin = '';
            $this->remotePassword = '';            
        }
        $this->setup = true;
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
        if ($this->connection !== null){
            ldap_unbind($this->connection);
            $this->connection = null;
            $this->connected = false;
            $this->connectedSecurely = false;
            $this->bound = false;
        }
    }    

    /**
     * Returns a list of the results from each search (in order) as a serialized string. 
     */
    public function __toString()
    {
        return serialize($this->results);
    }

    /**
     * Opens a connection. Address, context, login Id and password can be
     * specified optionally. If they are they will replace the previously
     * stored options. If they are not specified, the most recently supplied
     * options will be used instead.
     */
    public function open($address='', $context='', $login=false, $password='')
    {
        $this->close();
        if ($address == ''){
            $address = $this->remoteAddress;
            $this->setup = false;
        }
        if ($context == ''){
            $context = $this->context;
            $this->setup = false;
        }
        if ($login !== false){
            $this->remoteLogin = $login;
            $this->setup = false;    
        }
        if ($password !== ''){
            $this->remotePassword = $password;
            $this->setup = false;    
        }
        return($this->getConnection());
    }
    
    /**
     * Opens a connection with the most recently specified options.
     */
    public function getConnection()
    {        
        $this->close();
        if (!$this->setup){
            $this->setupConnection($this->remoteAddress, $this->context, $this->remoteLogin, $this->remotePassword);
        }
        
        $this->connection = ldap_connect($this->remoteProtocol . $this->remoteAddress . $this->remotePort);
        if (!$this->connection && $this->remoteProtocol == 'ldap://'){
            $this->connection = ldap_connect($this->remoteAddress.$this->remotePort); // try again with support for native sun ldap module.
        }
        
        if ($this->connection){
            $this->connected = true;
            $this->connectedSecurely = ($this->remoteProtocol == 'ldaps://');
            $versionSet = ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->ldapVersion);
            
            if (!$versionSet){
                $errorMessage = '';
                ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);
                $this->error[] = "Failed to set the protocol version: " 
                    . $errorMessage . " " 
                    . ldap_error( $this->connection);
                throw new \Exception($this->error[]);
            }
            
            return true;    
        }
                
        $this->error[] = "Unable to connect to the LDAP server: " 
            . $this->remoteProtocol.$this->remoteAddress.$this->remotePort; 
        $this->connection = null;
        throw new \Exception($this->error[array_key_last($this->error)]);
        
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
        if (!$this->connected ){
            $this->error[] = "Attempting to bind when not connected";        
            return false;
        }        
        if ($login != ''){
            $this->remoteLogin = $login;
            $this->remotePassword = $password;
        }
        if ($this->remoteLogin != '' && ($this->connectedSecurely || $this->allowInsecureAuth)){
            if ($this->allowInsecureAuth){
                $this->error[] = "Sent login and password over clear text because it was explicitly requested. ";
            }
            $this->bound = ldap_bind($this->connection, $this->remoteLogin, $this->remotePassword);
        } else {
            if ($this->remoteLogin != ''){
                $this->error[] = 'Attempted to login with authentication via '
                    . $this->remoteProtocol . $this->remoteAddress . $this->remotePort
                    . "with login {$this->remoteLogin} but was not connected securely so "
                    . 'anonymous access was used instead. ';
            }
            $this->bound = ldap_bind($this->connection);
        }
        if ($this->bound){
            return true;
        }
        
        $errorMessage = '';
        ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);
        $this->error[] = "Unable to bind to LDAP {$this->remoteProtocol}{$this->remoteAddress}{$this->remotePort} : " 
            . $errorMessage . ", " 
            . ldap_error($this->connection);
        $this->close();
        return false;
    }
    
    /**
     * Performs a search and returns resulting records. Add the results to a 
     * cummlative list of all search results.
     */    
    public function search($searchString, $newContext = '')
    {
        if ($newContext != ''){
            $this->context = $newContext;
        }
        if ($searchString == ""){
            $this->error[] = "No search criteria. " ;
            return false;
        }    
        if (!$this->bound){
            $this->bind();
            if (!$this->bound){
                return false;
            }
        }
    
        if (count($this->attributes) !== 0){
            $ldapSearchResult = ldap_search($this->connection,$this->context,$searchString,$this->attributes);
        } else {
            $ldapSearchResult = ldap_search($this->connection,$this->context,$searchString);
        }
        
        if ($ldapSearchResult){
            if (ldap_count_entries($this->connection, $ldapSearchResult) > 0){
                $entries = ldap_get_entries($this->connection, $ldapSearchResult);
                if ($entries['count'] > 0 ){
                    $this->results[] = $entries;
                    return $entries;
                } else {
                    $errorMessage = '';
                    ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);
                    $this->error[] = "No entries returned. "
                        . $errorMessage . " " 
                        . ldap_error( $this->connection);
                    return [];                
                }        
            } else {
                $errorMessage = '';
                ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);
                $this->error[] = "No matches found. "
                    . $errorMessage . " " 
                    . ldap_error( $this->connection);
                return [];
            }        
        }

        $errorMessage = '';
        ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);
        $this->error[] = "Could not perform LDAP search: " 
            . $errorMessage . " " 
            . ldap_error($this->connection);
        return false;
    }    
    
    /**
     * Sets the adapter to connect over a secure connection. If it is currently 
     * connected in an insecure mannner (clear text) it will disconnect.
     */
    public function secureConnection()
    {
        if ($this->remoteProtocol != 'ldaps://'){
            $this->close();
            $this->remoteProtocol = 'ldaps://';
        }
    }

    /**
     * Sets the adapter to connect over a clear text (unsecure) connection. 
     * If it is currently connected in an secure mannner it will disconnect.
     */    
    public function unsecureConnection()
    {
        if ($this->remoteProtocol != 'ldap://'){
            $this->close();
            $this->remoteProtocol = 'ldap://';
        }
    }    

    /**
     * Sets the adapter to allow sending of login and password over clear text. 
     * Normally it will refuse. Once set, it cannot be reverted.
     */
    public function allowInsecureAuth()
    {
        $this->allowInsecureAuth = true;
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
        $this->attributes = $array;
    }
    
    /**
     * Returns the current list of attributes.
     */
    public function getAttributes()
    {
        return($this->attributes);
    }
    
    /**
     * Returns the string of all errors encountered by the adapter.
     */
    public function getErrors()
    {
        return $this->error;
    }

    public function clearErrors()
    {
        $this->error = [];
        return $this;
    }
    
    /**
     * Returns the list of all results from each search. Even if only one search has been
     * performed, it will be nested in an array by itself.
     */
    public function getAllResults()
    {
        return $this->results;
    }

    /**
     * Returns the most recent results.
     */
    public function getResults()
    {
        return end($this->results);
    }

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
            $dnString = $this->context;
        }
        if (!$this->bound){
            $this->bind();
            if(!$this->bound){
                return false;
            }
        }
        $result = @ldap_mod_add($this->connection, $dnString, $attributeArray);
        if ($result) {
            return true;
        } else {
            $errorMessage = '';
            ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);        
            $this->error[] = "Could not add record to LDAP: " 
                . $errorMessage . " " 
                . ldap_error($this->connection);
            return false;
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
            $dnString = $this->context;
        }
        if (!$this->bound){
            $this->bind();
            if(!$this->bound){
                return false;
            }
        }
        $result = @ldap_mod_replace($this->connection, $dnString, $attributeArray); //#TODO why do we repress this?
        if ($result) {
            return true;
        } else {
            $errorMessage = '';
            ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);        
            $this->error[] = "Could not add record to LDAP: " 
                . $errorMessage . " " 
                . ldap_error( $this->connection);
            return false;
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
            $dnString = $this->context;
        }
        if (!$this->bound){
            $this->bind();
            if(!$this->bound){
                return false;
            }
        }
        ob_start();
        $result = @ldap_mod_del($this->connection, $dnString, $attributeArray);  //#TODO why do we repress this? and buffer it?
        ob_clean();
        if ($result) {
            return true;
        } else {
            $errorMessage = '';
            ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);        
            $this->error[] = "Could not add record to LDAP: " 
                . $errorMessage . " " 
                . ldap_error($this->connection);
            return false;
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
            $dnString = $this->context;
        }
        if (!$this->bound){
            $this->bind();
            if(!$this->bound){
                return false;
            }
        }
        $result = ldap_add($this->connection, $dnString, $attributeArray);
        if ($result) {
            return true;
        } else {
            $errorMessage = '';
            ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);        
            $this->error[] = "Could not add record to LDAP: " 
                . $errorMessage . " " 
                . ldap_error( $this->connection);
            return false;
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
            $dnString = $this->context;
        }
        if (!$this->bound){
            $this->bind();
            if(!$this->bound){
                return false;
            }
        }
        $result = ldap_delete($this->connection, $dnString);
        if ($result) {
            return true;
        } else {
            $errorMessage = '';
            ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);        
            $this->error[] = "Could not add record to LDAP: " 
                . $errorMessage . " " 
                . ldap_error( $this->connection);
            return false;
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
            $this->context = $newContext;
        }
        if (!$this->bound){
            $this->bind();
            if(!$this->bound){
                return false;
            }
        }
        $ldapListResult = ldap_list($this->connection, $newContext, $typeToSearch , array($attribute));

        if ($ldapListResult){
            if (ldap_count_entries($this->connection, $ldapListResult) > 0){
                $entries = ldap_get_entries($this->connection, $ldapListResult);
                if ($entries["count"] > 0 ){
                    $this->results[] = $entries;
                    return $entries;
                } else{
                    $errorMessage = '';
                    ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);
                    $this->error[] = "No entries returned. "
                        . $errorMessage . " " 
                        . ldap_error( $this->connection);
                    return [];                
                }        
            } else {
                $errorMessage = '';
                ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);
                $this->error[] = "No matches found. "
                    . $errorMessage . " " 
                    . ldap_error( $this->connection);
                return [];
            }        
        }
        $errorMessage = '';
        ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $errorMessage);
        $this->error[] = "Could not perform LDAP list: " 
            . $errorMessage . " " 
            . ldap_error( $this->connection);
        return false;
    
    }

}