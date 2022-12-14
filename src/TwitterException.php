<?php

namespace Ospina\Apitter;

class TwitterException extends \Exception
{
    private object $errorObject;


    public function __construct($errorObject, $code = 0, $message = 'There was a problem with your API request', $previous = null)
    {
        $this->errorObject = $errorObject;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorObject(): object
    {
        return $this->errorObject;
    }

    public function getErrorName()
    {
        if (isset($this->errorObject->error)) {
            return $this->errorObject->error;
        }
        return 'No error detected';
    }
    public function getErrorDescription(): string
    {
        if (isset($this->errorObject->error_description)) {
            return $this->errorObject->error_description;
        }
        return 'No error description detected';
    }

}