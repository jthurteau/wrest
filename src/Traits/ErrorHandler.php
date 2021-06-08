<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Traits for Error Handling
 */

namespace Saf\Traits;

trait ErrorHandler {

    protected $errorMessage = [];

    abstract public function pullErrorCallback();

    protected function hasInternalError(){
        return count($this->errorMessage);
    }

    public function addError($error)
    {
        $this->errorMessage[] = $error;
        // if ($this->_debugMode) {
        //     Saf_Debug::out($error);
        // }
        return $this;
    }

    public function clearErrors()
    {
        $this->errorMessage = array();
        return $this;
    }

    protected function pullError()
    {
        $error = $this->pullErrorCallback()();
        if ($error) {
            $this->addError($error);
        }
        return $this;
    }
}