<?php

namespace Ospina\Apitter;

class TwitterException extends \Exception
{
    private object $errorObject;

    public function __construct($errorObject, $code = 0)
    {
        $this->errorObject = $errorObject;
        parent::__construct($this->getErrorDescription(), $code, null);
    }

    public function getErrorDescription(): string
    {
        if (isset($this->errorObject->error_description)) {
            return $this->errorObject->error_description;
        }
        if (isset($this->errorObject->detail)) {
            return $this->errorObject->detail;
        }
        try {
            return json_encode($this->errorObject, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return 'No error name description detected';
        }
    }

    public function getErrorObject(): object
    {
        return $this->errorObject;
    }

    public function getErrorName(): string
    {
        if (isset($this->errorObject->error)) {
            return $this->errorObject->error;
        }
        if (isset($this->errorObject->title)) {
            return $this->errorObject->title;
        }
        return 'No fue posible detectar el error.';
    }

}