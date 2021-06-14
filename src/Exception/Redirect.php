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
	 * indicates if the redirect should be remembered by the client
	 */
	protected $permanent = false;
	
	/**
	 * sets the exception's redirect to be remembered in the browser's history
	 * i.e. throw new Redirect('<your url>')->permanent();
	 *
	 * @return Saf\Exception\Redirect $this profient method.
	 */
	public function permanent()
	{
		$this->permanent = true;
		return $this;
	}

	/**
	 * returns whether the redirect should be remembered by the client
	 *
	 * @return bool indicating to keep in history
	 */
	public function isPermanent()
	{
		return $this->permanent;
	}

}