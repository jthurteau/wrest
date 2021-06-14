<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Instructs the framework to redirect the user to continue
 */

namespace Saf\Exception;

class Redirect extends \Exception {

	/**
	 * indicates if the exception's redirect should be kept in the browser history
	 */
	protected $keep = false;
	
	/**
	 * sets the exception's redirect to be kept in the browser's history
	 * i.e. throw new Saf_Exception_Redirect('<your url>')->keep();
	 *
	 * @return Saf_Exception_Redirect $this profient method.
	 */
	public function keep()
	{
		$this->keep = true;
		return $this;
	}

	/**
	 * returns whether or not the exception should be kept
	 *
	 * @return bool indicating to keep in history
	 */
	public function isKept()
	{
		return $this->keep;
	}

}