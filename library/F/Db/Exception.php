<?php
/**
 * F_Db 异常类
 *
 */
class F_Db_Exception extends F_Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}