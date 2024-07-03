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

    public const METHOD_BODY = 'body';
    public const METHOD_HEADER = 'header';

	/**
	 * indicates if the redirect should be remembered by the client
	 */
	protected bool $permanent = false;

    /**
     * indicates how the agent should handle the redirect (header or body)
     */
    protected string $method = self::METHOD_BODY;
	
	/**
	 * sets the exception's redirect to be remembered in the browser's history
	 * i.e. throw new Redirect('<your url>')->permanent();
     * #NOTE deprecate in favor of ::makePermanent()
	 *
	 * @return Saf\Exception\Redirect $this profient method.
	 */
	public function permanent(): Redirect
	{
		$this->permanent = true;
		return $this;
	}

	/**
	 * returns whether the redirect should be remembered by the client
	 *
	 * @return bool indicating to keep in history
	 */
	public function isPermanent(): bool
	{
		return $this->permanent;
	}

    /**
     * sets the exception's redirect to be remembered in the browser's history
     *
     * @return Saf\Exception\Redirect $this profient method.
     */
    public function makePermanent(): Redirect
    {
        $this->permanent = true;
        return $this;
    }

    /**
     * sets the exception's redirect to be remembered in the browser's history
     *
     * @return Saf\Exception\Redirect $this profient method.
     */
    public function makeAutomatic(): Redirect
    {
        $this->method(self::METHOD_HEADER);
        return $this;
    }

    /**
     * sets the method
     *
     * @return Saf\Exception\Redirect $this profient method.
     */
    public function method(string $method): Redirect
    {
        $this->method = $method;
        return $this;
    }

    /**
     * returns whether the redirect should be handled automatically as a header
     *
     * @return bool indicating to redirect with header
     */
    public function isAutomatic(): bool
    {
        return $this->method == self::METHOD_HEADER;
    }

}