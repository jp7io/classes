<?php

/**
 * Throwing exceptions with this class you are not exposing the internal part of
 * your WebServices.
 */
class Jp7_InterAdmin_Soap_Exception extends Exception
{
    public function __construct($message = '', $code = 0)
    {
        return parent::__construct($message, $code);
    }
}
