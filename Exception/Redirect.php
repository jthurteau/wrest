<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Instructs the framework to redirect the user to continue

*******************************************************************************/
class Saf_Exception_Redirect extends Exception {

	/**
	 * indicates if the exception's redirect should be kept in the browser history
	 */
	protected $_keep = FALSE;
	
	/**
	 * sets the exception's redirect to be kept in the browser's history
	 * i.e. throw new Saf_Exception_Redirect('<your url>')->keep();
	 *
	 * @return Saf_Exception_Redirect $this profient method.
	 */
	public function keep()
	{
		$this->_keep = TRUE;
		return $this;
	}

	/**
	 * returns whether or not the exception should be kept
	 *
	 * @return bool indicating to keep in history
	 */
	public function isKept()
	{
		return $this->_keep;
	}

}
