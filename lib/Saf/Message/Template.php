<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for generating messages (e.g. XML)

*******************************************************************************/

class Saf_Message_Template
{
        protected $_rawMessage = '';

        public function __construct($messageName, $ext='.xml')
        {
        		if(!file_exists(APPLICATION_PATH . '/configs/messages/' . $messageName. $ext)){
                        throw new Exception("Template for {$messageName} not found.");
                }
                $this->_rawMessage = file_get_contents(
                        APPLICATION_PATH . '/configs/messages/' . $messageName . $ext
                );
        }
        
        public function get($config = NULL){
        	return
        		is_null($config)
        		? $this->_rawMessage
        		: Saf_Model_Reflector::dereference($this->_rawMessage, $config);
        }
}

