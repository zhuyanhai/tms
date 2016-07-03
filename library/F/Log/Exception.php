<?php
/**
 * 日志类抛出的异常
 *
 */
class F_Log_Exception extends F_Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}