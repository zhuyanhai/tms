<?php
/**
 * 关于抛出异常基类
 *
 * 所有自定义异常的基类
 */
class F_Exception extends Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}