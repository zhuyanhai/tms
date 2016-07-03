<?php
/**
 * 前端控制器 - 异常截获处理 类
 * 
 * 主要是处理截获后的异常跳转到 ErrorController
 * 
 * @category F
 * @package F_Controller
 * @subpackage F_Controller_ErrorHandle
 * @author allen <allenifox@163.com>
 */
class F_Controller_ErrorHandle
{
    /**
     * 单例实例
     * 
     * @var F_Controller_ErrorHandle 
     */
    private static $_instance = null;
    
    /**
     * 单例模式
     * 
     * @return F_Controller_ErrorHandle
     */    
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new F_Controller_ErrorHandle();
        }
        
        return self::$_instance;
    }
    
    /**
     * 转发到 ErrorController errorAction
     * 
     * @param Exception $error
     */
    public function forward(Exception $error)
    {
        // Forward to the error handler
        F_Controller_Request_Http::getInstance()->setParam('errorHandler', $error)
                ->setModule('Index')
                ->setController('Error')
                ->setAction('error')
                ->setDispatched(false);
    }
}