<?php
/**
 * 前端控制器 - action 基类
 * 
 * 主要是负者 action 处理
 * 
 * @category F
 * @package F_Controller
 * @subpackage F_Controller_Action
 * @author allen <allenifox@163.com>
 */
abstract class F_Controller_ActionAbstract
{
    /**
     * F_Application 对象
     * 
     * @var F_Application 
     */
    protected $_applicationObj = null;
    
    /**
     * F_Controller_Request_Http 对象
     * 
     * @var F_Controller_Request_Http 
     */
    protected $_requestObj = null;
    
    /**
     * F_Controller_Reponse_Http 对象
     * 
     * @var F_Controller_Reponse_Http 
     */
    protected $_responseObj = null;  
    
    /**
     * F_Controller_Redirector 对象，通过 getRedirector() 方法获取
     * 
     * @var F_Controller_Redirector 
     */
    protected $_redirectorObj = null;

    /**
     * 视图元素容器对象
     * 
     * @var F_View 
     */
    public $view = null;
    
    /**
     * 错误编号 0 为无错误 <0 错误标号
     * 
     * @var int 
     */
    private $_errorCode = 0;
    
    /**
     * 错误信息
     * 
     * @var string
     */
    private $_errorMsg = '';

    /**
     * 构造函数
     * 
     */
    public function __construct() 
    {
        $this->_applicationObj = F_Application::getInstance();
        $this->_requestObj     = F_Controller_Request_Http::getInstance();
        $this->_responseObj    = F_Controller_Response_Http::getInstance();
        $this->view            = F_View::getInstance();
        $this->_redirectorObj  = F_Controller_Redirector::getInstance();
    }
    
    /**
     * 在调用 action 前执行
     *
     * @return void
     */
    public function preDispatch()
    {
        //empty
    }
    
    /**
     * 在调用 action 后执行
     *
     * @return void
     */
    public function postDispatch()
    {
        //empty
    }
    
    /**
     * 转跳到其他的 controller/action
     * 
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @return void
     */
    public function forward($action, $controller = null, $module = null, array $params = null)
    {
        if (null !== $params) {
            $this->_requestObj->setParams($params);
        }

        if (null !== $controller) {
            $this->_requestObj->setController($controller);

            // Module should only be reset if controller has been specified
            if (null !== $module) {
                $this->_requestObj->setModule($module);
            }
        }

        $this->_requestObj->setAction($action)->setDispatched(false);
    }
    
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
     * @return void
     */
    public function response($attr = array())
    {
        $response = array(
            'status'        => $this->_errorCode,
            'data'          => $attr,
            'msg'           => $this->_errorMsg,
        );
        $this->_responseObj->setHeader('Content-Type', 'application/json', true);
        $body = json_encode($response);
        $this->_responseObj->setBody($body);
        $this->_responseObj->sendResponseAndExit();
    }
    
    /**
     * 获取转向器对象，负责URL跳转处理
     * 
     * @return F_Controller_Redirector
     */
    public function getRedirector()
    {
        if (is_null($this->_redirectorObj)) {
            $this->_redirectorObj = F_Controller_Redirector::getInstance();
        }
        return $this->_redirectorObj;
    }
    
    /**
     * 获取request对象
     * 
     * @return F_Controller_Request_Http
     */
    public function getRequestObj()
    {
        if (is_null($this->_requestObj)) {
            $this->_requestObj = F_Controller_Request_Http::getInstance();
        }
        return $this->_requestObj;
    }
    
    /**
     * 获取response对象
     * 
     * @return F_Controller_Response_Http
     */
    public function getResponseObj()
    {
        if (is_null($this->_responseObj)) {
            $this->_responseObj = F_Controller_Response_Http::getInstance();
        }
        return $this->_responseObj;
    }
    
    /**
     * 开始执行分配的 action
     * 
     * @param string $action
     */
    public function dispatch($action)
    {
        $this->preDispatch();
        
        if ($this->_requestObj->isDispatched()) {
            // 如果在 preDispatch 中执行了一个 300 - 307的跳转 或 执行了forward，就先停止 dispatch
            if (!($this->_responseObj->isRedirect())) {
                $action .= 'Action'; 
                if (method_exists($this, $action)) {
                    $this->$action();
                } else {
                    throw new F_Controller_Exception('action ['.$action.'] 在 controller ['.get_class($this).'] 中未找到');
                }
            }
            
            //如果在 action 中执行了一个 300 - 307的跳转 或 执行了forward，就先停止 dispatch
            if ($this->_requestObj->isDispatched()) {
                
                $this->postDispatch();
                
                // 如果在 postDispatch 中执行了一个 300 - 307的跳转 或 执行了forward，就先停止 dispatch
                if ($this->_requestObj->isDispatched()) {
                    //渲染视图
                    $this->view->parse();
                }
            }
        }
    }
}