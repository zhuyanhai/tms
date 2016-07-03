<?php
/**
 * 前端控制器 - route 类
 * 
 * 主要是输入后的路由操作
 * 
 * @category F
 * @package F_Controller
 * @subpackage F_Controller_Router_Route
 * @author allen <allenifox@163.com>
 */
class F_Controller_Router_Route
{
    /**
     * REQUEST_URI 的分隔符
     * 
     * @var string 
     */
    private $_urlDelimiter = '/';
    
    /**
     * 路由规则 - 正则表达式
     * 
     * @var array 
     */
    private static $_routeRegexRules = array();
    
    /**
     * 单例实例
     * 
     * @var F_Controller_Router_Route
     */
    private static $_instance = null;

    /**
     * 构造函数
     * 
     */
    private function __construct()
    {
        //empty
    }
    
    /**
     * 单例模式
     * 
     * @return F_Controller_Router_Route
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new F_Controller_Router_Route();
        }
        
        return self::$_instance;
    }
    
    /**
     * 添加路由规则 - 正则形式的
     * 
     * @param string $regexString
     */
    public static function addRouteOfRegexRule($regexString)
    {
        array_push(self::$_routeRegexRules, $regexString);
    }
    
    public function route()
    {
        $isMatch = false;
        
        $requestObj = F_Controller_Request_Http::getInstance();
        
        //桌面应用
        $this->_startupOfDapiCheck($requestObj);
        
        //手机应用
        $this->_startupOfMapiCheck($requestObj);
        
        if (!empty(self::$_routeRegexRules)) {//使用正则形式检测符合的路由
            //todo
        }
        
        if (false === $isMatch) {//使用正常 module 形式检测路由
            
            $path = $requestObj->getRequestUri();
            $path = trim($path);

            if (empty($path)) {
                throw new F_Controller_Router_Exception();
            }
            
            $module     = 'Index';
            $controller = 'Index';
            $action     = 'Index';
            
            if ('/' !== $path) {
                $checkModuleExist = false;
                $path = explode('?', $path);
                if (count($path) > 1) {
                    $tmpParams = $path[1];
                    $path = trim($path[0], '/');
                    $tmpParams = explode('&', $tmpParams);
                    $params = array();
                    foreach ($tmpParams as $tp) {
                        $tpp = explode('=', $tp);
                        $params[$tpp[0]] = $tpp[1];
                    }
                } else {
                    $path = trim($path[0], '/');
                }

                $pathArray = explode($this->_urlDelimiter, $path);
                $pathArrayCount = count($pathArray);

                if (1 === $pathArrayCount) {// REQUEST_URI = /demo
                    $checkModuleExist = true;
                    $controller = $pathArray[0];
                    $action     = 'Index';
                    unset($pathArray[0]);
                } elseif (0 === $pathArrayCount % 2) {// REQUEST_URI = /demo/a
                    $checkModuleExist = true;
                    $controller = $pathArray[0];
                    $action     = $pathArray[1];
                    unset($pathArray[0], $pathArray[1]);
                } else {// REQUEST_URI = /demo/a/i/id/9
                    $module     = $pathArray[0];
                    $controller = $pathArray[1];
                    $action     = $pathArray[2];
                    unset($pathArray[0], $pathArray[1], $pathArray[2]);
                }
                if ($numSegs = count($pathArray)) {
                    for ($i = 0; $i < $numSegs; $i = $i + 2) {
                        $key = urldecode($path[$i]);
                        $val = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
                        $params[$key] = (isset($params[$key]) ? (array_merge((array) $params[$key], array($val))): $val);
                    }
                    $requestObj->setParams($params);
                }
                if ($checkModuleExist) {
                    try {
                        $moduleClassName = ucfirst($controller).'_'.ucfirst($action).'Controller';
                        $moduleClassObj = new $moduleClassName();
                        unset($moduleClassObj);
                        $module     = $controller;
                        $controller = $action;
                        $action     = 'index';
                    } catch (Exception $e) {
                        //continue
                    }
                }
            }
        }
        
        $requestObj->setModule($module)->setController($controller)->setAction($action);
    }
    
    /**
     * 路由启动前关于【桌面应用】访问检测并处理
     */
    private function _startupOfDapiCheck($requestObj)
    {
        $requestMethod = Utils_Validation::filter($requestObj->getParam('sDrMethod', ''))->removeStr()->removeHtml()->receive();
        if (!empty($requestMethod)) {//手机接口请求处理
            $requestObj->setRequestUri('/dapi/run');
        }
    }
    
    /**
     * 路由启动前关于【手机应用】访问检测并处理
     */
    private function _startupOfMapiCheck($requestObj)
    {
        $requestMethod = Utils_Validation::filter($requestObj->getParam('sMrMethod', ''))->removeStr()->removeHtml()->receive();
        if (!empty($requestMethod)) {//手机接口请求处理
            $requestObj->setRequestUri('/mapi/run');
        }
    }
}