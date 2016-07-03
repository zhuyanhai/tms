<?php
/**
 * F_Application 异常类
 *
 */
class F_Application_Exception extends F_Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}