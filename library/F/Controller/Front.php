<?php
/**
 * 前端控制器类
 * 
 * 主要是协调 输入、路由分发、输出 等操作
 * 
 * @category F
 * @package F_Controller
 * @subpackage F_Controller_Front
 * @author allen <allenifox@163.com>
 */
class F_Controller_Front
{
    /**
     * 单例实例
     *
     * @var F_Controller_Front
     */
    protected static $_instance = null;
    
    /**
     * 判断是否截获异常
     * 
     * @var boolean 
     */
    protected $_isInterceptException = true;

    /**
     * 单例模式
     *
     * @return F_Controller_Front
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * 开始处理分配 - 输入、路由、输出
     */
    public function dispatch()
    {
        try {
            $requestObj  = F_Controller_Request_Http::getInstance();
            $routerObj   = F_Controller_Router_Route::getInstance();
            $responseObj = F_Controller_Response_Http::getInstance();

            $routerObj->route();

            do {
                $requestObj->setDispatched(true);
                
                $module     = $requestObj->getModule();
                $controller = $requestObj->getController();
                $action     = $requestObj->getAction();

                if ('Index' === $module) {
                    $controllerClass = ucfirst($controller) . 'Controller';
                } else {
                    $controllerClass = ucfirst($module) . '_' . ucfirst($controller) . 'Controller';
                }
                
                $obLevel = ob_get_level();
                ob_start();
                
                try {
                    $controllerObj = new $controllerClass();
                    if (!($controllerObj instanceof F_Controller_ActionAbstract)) {
                        throw new F_Controller_Exception('Controller "' . $controllerClass . '" is not an instance of F_Controller_ActionAbstract');
                    }
                    $controllerObj->dispatch($action);
                } catch (Exception $e) {
                    // Clean output buffer on error
                    $curObLevel = ob_get_level();
                    if ($curObLevel > $obLevel) {
                        do {
                            ob_get_clean();
                            $curObLevel = ob_get_level();
                        } while ($curObLevel > $obLevel);
                    }
                    if ($this->_isInterceptException) {
                        if ($requestObj->getController() !== 'Error') {
                            F_Controller_ErrorHandle::getInstance()->forward($e);
                        } else {
                            throw $e;
                        }
                    } else {
                        throw $e;
                    }
                }
            } while(!$requestObj->isDispatched());
        
        } catch (Exception $e) {//判断是否截获异常
            throw $e;
        }
        
        $responseObj->sendResponse();
    }
}