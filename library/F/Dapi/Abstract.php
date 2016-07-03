<?php
/**
 * 桌面应用 controller api接口的抽象类
 * 
 * public function nav_200
 * public function nav_100
 * 
 */
abstract class F_Dapi_Abstract
{   
    /**
     * http 请求对象
     * 
     * @var F_Controller_Request_Http 
     */
    protected $_requestObj = null;
    
    /**
     * http 返回对象
     * 
     * @var F_Controller_Response_Http 
     */
    protected $_responseObj = null;

    /**
     * 错误编号 0 为无错误 <0 错误标号
     * 
     * @var int 
     */
    protected $_errorCode = 0;
    
    /**
     * 错误信息
     * 
     * @var string
     */
    protected $_errorMsg = '';
    
    /**
     * 登陆用户对象
     * 
     * @var type 
     */
    protected $user = null;
    
    /**
     * 请求版本号
     * 
     * @var int
     */
    protected $version = 0;


    /**
     * 构造函数
     * 
     * @param F_Controller_Request_Http $requestObj
     * @param F_Controller_Reponse_Http $responseObj
     */
    public function __construct($requestObj, $responseObj)
    {
        $this->_requestObj  = $requestObj;
        $this->_responseObj = $responseObj;
    }

    /**
     * new class 时必需调用初始化对象
     * 
     * @return \YR_MAPI_Abstract
     */
    public function init()
    {
        $this->version = $this->_requestObj->getParam('iV', 0);
        
        //DAPI秘钥检测
        //todo

        return $this;
    }
    
    /**
     * 开始执行接口
     * 
     * @param string $action MVC - C中的action名字
     * @return F_Dapi_Abstract
     * @throws Exception
     */
    public function run($action)
    {
        $action = lcfirst($action);
        $methodName = $action . '_' . $this->version;
        
        //所有方法必须是按版本号倒序排
        if (!method_exists($this, $methodName)) {
            $lastFunVersion = 0;
            $classMethods = get_class_methods($this);
            foreach ($classMethods as $cm) {
                $cmArray    = explode('_', $cm);
                $funVersion = (isset($cmArray[1]))?intval($cmArray[1]):0;
                if (count($cmArray) > 1 && $action === $cmArray[0] && $this->version >= $funVersion) {
                    if ($lastFunVersion < $funVersion) {
                        $lastFunVersion = $funVersion;
                        $methodName = $action . '_' . $lastFunVersion;
                    }
                }
            }
            if (!method_exists($this, $methodName)) {
                $methodName = '';
                if(method_exists($this, $action)){
                    $methodName = $action;
                }
            }
        }
        
        if(empty($methodName)){//方法不存在
            throw new Exception($action .' is not in '.get_class($this));
        }
        
        $this->$methodName();
        
        return $this;
    }
    
//-----以下为 protected 或 private

    /**
     * 设置错误
     * 
     * @param int $error
     * @param string $msg
     */
    protected function error($errorMsg = '', $errorCode = -1)
    {
        $this->_errorCode = $errorCode;
        $this->_errorMsg  = $errorMsg;
        return $this;
    }

    /**
     * 返回给客户端
     * 
     * @param array $attr 需要返回的内容
     * @param string $msg 错误时需要返回给客户端显示的内容
     * @param boolean $error true = 标识出错
     */
    protected function response($attr = array())
    {
        $response = array(
            'status'        => $this->_errorCode,
            'data'          => $attr,
            'msg'           => $this->_errorMsg,
        );
        $this->_responseObj->setHeader('Content-Type', 'application/json', true);
        //允许AJAX跨域访问，但（*）太危险，不宜使用生产缓存，注意指定有效域名
        //同域是指：协议、域名、端口全部一样
        $this->_responseObj->setHeader('Access-Control-Allow-Origin', '*', true);
        $body = json_encode($response);
        $this->_responseObj->setBody($body);
        $this->_responseObj->sendResponseAndExit();
    }

    /**
     * 检测登录，如果未登录返回
     */
    protected function checkLoginAndResponse()
    {
        //todo
    }
    
}