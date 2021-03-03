<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Standard methods for encapsulated environment access
 */

namespace Saf\Environment;

trait Access {

	/**
	 * Environment is encapsulated as an Array/Hash
	 */
	protected $environment = [];

    public function duplicate()
    {
        $return = [];
        foreach($this->environment as $key => $value) {
            $return[$key] = $value; #TODO clone objects?
        }
        return $return;
    }

//-- required ArrayAccess interface methods

	public function offsetExists ($offset)
	{
		return key_exists($offset, $this->environment);
	}

	public function &offsetGet ($offset)
	{
		$result = null;
		if (key_exists($offset, $this->environment)) {
			$result = &$this->environment[$offset];
		}
		return $result;
	}

	public function offsetSet ($offset, $value)//&$value)
	{
		if (is_null($offset)) {
			$offset = max(array_keys($this->environment)) + 1;
		}
		$this->environment[$offset] = &$value;
	}

	public function offsetUnset ($offset)
	{
		unset($this->environment[$offset]);
	}

}