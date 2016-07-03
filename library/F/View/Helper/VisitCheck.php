<?php 
/**
 * 访问核查，检测用户访问url是否与 F_Controller_Request_Http 中 module controller action params 中的相符合
 * 
 */
final class F_View_Helper_VisitCheck
{
    /**
     * 访问核查
     * 
     * @param array $condition array('module' => '', 'controller' => '', 'action' => '', 'param' => 'a')
     * @param string $rightback
     * @param string $errorback
     * @return boolean
     */
    public function visitCheck(array $condition, $rightback = '', $errorback='')
    {
        $requestObj = F_Controller_Request_Http::getInstance();
        $total = count($condition);
        foreach($condition as $k=>$v){
            switch(strtolower($k)){
                case 'module':
                    $module = $requestObj->getModule();
                    if(is_array($v)){
                        if(in_array($module, $v)){
                            $total--;
                        }
                    } else {
                        if($module === $v){
                            $total--;
                        }
                    }
                    break;
                case 'controller':
                    $controller = $requestObj->getController();
                    if(is_array($v)){
                        if(in_array($controller, $v)){
                            $total--;
                        }
                    } else {
                        if($controller === $v){
                            $total--;
                        }
                    }
                    break;
                case 'action':
                    $action = $requestObj->getAction();
                    if(is_array($v)){
                        if(in_array($action, $v)){
                            $total--;
                        }
                    } else {
                        if($action === $v){
                            $total--;
                        }
                    }
                    break;
                default:
                    $param = $requestObj->getParam($k);
                    if(is_array($v)){
                        if(in_array($param, $v)){
                            $total--;
                        }
                    } else {
                        if($param == $v){
                            $total--;
                        }
                    }
                    break;
            }
        }
        if($total === 0){
            if(empty($rightback)){
                return true;
            } else {
                return $rightback;
            }
        }
        if(empty($errorback)){
            return false;
        } else {
            return $errorback;
        }
    }
}