<?php
/**
 * 框架应用程序桌面应用基础类
 * 
 * windows 桌面应用需要的API
 * 
 * @category F
 * @package F_Dapi
 * @author allen <allenifox@163.com>
 * 
 */
final class F_Dapi
{
    const CLASS_NAME_PREFIX = 'DAPI';

    /**
     * 开始解析接口类
     * 
     * @param F_Controller_ActionAbstract $actionObj
     */
    public static function run($actionObj)
    {
        $requestObj    = $actionObj->getRequestObj();
        $responseObj   = $actionObj->getResponseObj();
        $requestMethod = Utils_Validation::filter($requestObj->getParam('sDrMethod', ''))->removeStr()->removeHtml()->receive();
        if (empty($requestMethod)) {
            //todo log
            throw new Exception('requestMethod not found');
        }
        $requestMethodArray = explode('.', $requestMethod);
        $controller = $requestMethodArray[0];
        $action     = $requestMethodArray[1];
        $className = self::CLASS_NAME_PREFIX . '_' . ucfirst($controller) . '_' . ucfirst($action);
        try {
            $obj = new $className($requestObj, $responseObj);
            $obj->init()->run($action);
        } catch (Exception $e) {
            //todo log
            echo $e->getMessage().'  '.$e->getTraceAsString();
        }
        exit;
    }
    
}