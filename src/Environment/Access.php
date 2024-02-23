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
	/**
	 * used with isset(), but not key_exists()/array_key_exists()
	 * use a #TBD funtion in ArrayLike
	 */

	public function offsetExists (mixed $offset) : bool
	{
		return key_exists($offset, $this->environment);
	}

	public function &offsetGet (mixed $offset) : mixed
	{
		$result = null;
		if (key_exists($offset, $this->environment)) {
			$result = &$this->environment[$offset];
		}
		return $result;
	}

	public function offsetSet (mixed $offset, mixed $value) : void //&$value)
	{
		if (is_null($offset)) {
			$offset = max(array_keys($this->environment)) + 1;
		}
		$this->environment[$offset] = &$value;
	}

	public function offsetUnset (mixed $offset) : void
	{
		unset($this->environment[$offset]);
	}

}