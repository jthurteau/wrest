<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Instructs the framework to forward the request (internally) to continue
 */

namespace Saf\Exception;

use Psr\Http\Message\ServerRequestInterface;

class Forward extends \Exception {

	/**
	 * the current request state (if unset the request will revert)
	 */
	protected $request = null;

	/**
	 * sets the request to forward
	 *
	 * @return Saf\Exception\Forward $this profient method.
	 */
	public function with(ServerRequestInterface $request)
	{
		$this->request = $request;
		return $this;
	}

	/**
	 * returns whether the forward has an updated request
	 *
	 * @return bool indicating new request
	 */
	public function hasRequest()
	{
		return !is_null($this->request);
	}

	/**
	 * returns whether the forward has an updated request
	 *
	 * @return bool indicating new request
	 */
	public function getRequest()
	{
		return $this->request;
	}
}