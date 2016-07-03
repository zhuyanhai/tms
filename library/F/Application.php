<?php
/**
 * 框架应用程序最基础的类
 * 
 * @category F
 * @package F_Application
 * @author allen <allenifox@163.com>
 */
final class F_Application
{
    /**
     * 应用程序全局配置
     * 
     * @var array
     */
    private $_configs = array();
    
    /**
     * Bootstrap 类实例
     * 
     * @var Bootstrap 
     */
    private $_bootstrapObj = null;
    
    /**
     * 自动加载的命名空间数组
     * 
     * @var array 
     */
    private static $_autoloadNamespaces = array();
    
    /**
     * 单例实例
     * 
     * @var F_Application 
     */
    private static $_instance = null;
    
    /**
     * 调试错误类对象实例
     * 
     * @var \Whoops\Run
     */
    private $_whoops = null;

    /**
     * 构造函数
     * 
     * 初始化应用程序必备的
     * - 全局配置
     * - autoloader
     * 
     * @throws F_Exception
     */
    private function __construct()
    {
        //设置自动加载
        spl_autoload_register('F_Application::autoload');
        
        $libraryPath = ROOT_PATH . '/library/';
        self::$_autoloadNamespaces = array(
            'F_' => $libraryPath,
            'C_' => $libraryPath,
            'T_' => $librarypath,
            'Utils_' => $libraryPath,
            'Bll_' => APPLICATION_PATH . '/models/',
            'Dao_' => APPLICATION_PATH . '/models/',
            'Controller_' => APPLICATION_PATH . '/controllers/',
        );
        
        //初始化全局配置
        $this->_configs = include APPLICATION_PATH . '/configs/application.cfg.php';
        
        if (isset($this->_configs['autoloaderNamespaces'])) {
            self::$_autoloadNamespaces = array_merge(self::$_autoloadNamespaces, $this->_configs['autoloaderNamespaces']);
        }

        if (!Utils_EnvCheck::isCli()) {//非cli方式执行脚本
            
            if (!Utils_EnvCheck::isProduction()) {//非正式环境
                require_once APPLICATION_PATH . '/../library/T/Whoops/autoload.php';
                $this->_whoops = new \Whoops\Run;
                if (F_Controller_Request_Http::getInstance()->isXmlHttpRequest()) {//ajax 请求
                    $this->_whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler);
                } else {
                    $this->_whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
                }
                $this->_whoops->register();
            }

        
            if (!isset($_SERVER['HTTP_HOST'])) {
                throw new F_Application_Exception('HTTP_HOST notfound');
            }
            
            $localDomainArray = explode('.', $_SERVER['HTTP_HOST']);
            $localDomainCount = count($localDomainArray);
            if ($localDomainCount > 3) {
                $localDomainLength = count($localDomain);
                if (in_array($localDomainArray[$localDomainCount-1], array('com','cn','net')) && 
                   in_array($localDomainArray[$localDomainCount-2], array('com','cn','net'))) {
                    $cookeDomain = $localDomainArray[$localDomainCount-3].'.'.$localDomainArray[$localDomainCount-2].'.'.$localDomainArray[$localDomainCount-1];
                } else {
                    $cookeDomain = $localDomainArray[$localDomainCount-2].'.'.$localDomainArray[$localDomainCount-1];
                }
            } else if($localDomainCount == 3){
                $cookeDomain = $localDomainArray[1].'.'.$localDomainArray[2];
            } else {
                $cookeDomain = $localDomainArray[0].'.'.$localDomainArray[1];
            }

            $this->_configs['cookie']['domain'] = '.' . $cookeDomain;
        } else {
            $this->_configs['cookie']['domain'] = '';
        }
    }
    
    /**
     * 单例模式
     * 
     * @return F_Application
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new F_Application();
        }
        
        return self::$_instance;
    }
    
    /**
     * spl_autoload
     * 
     * @param string $class 需自动加载的类名
     * @return boolean
     */
    public static function autoload($class)
    {
		// 检查类 或 接口 是否已经定义
		if (class_exists($class, false) || interface_exists($class, false)) {
			return false;
		}
        
        if (preg_match('%Controller$%', $class)) {//controller
            $classArray = array('Controller');
        } else {
            $classArray = explode('_', $class);
        }

        if (!isset(self::$_autoloadNamespaces[$classArray[0].'_'])) {
            throw new F_Application_Exception('autoloadNamespaces 不存在', 6666);
        }

        // 自动组织类路径
        switch ($classArray[0]) {
            case 'DAPI':
            case 'MAPI':
                $namespace = $classArray[0];
                $classArray = explode('_', $class);
                $tmp = $classArray[0];
                $classArray[0] = $classArray[1];
                $classArray[1] = $tmp;
                $file = self::$_autoloadNamespaces[$namespace.'_'] . implode(DIRECTORY_SEPARATOR, $classArray) . '.php';
                break;
            default:
                $file = self::$_autoloadNamespaces[$classArray[0].'_'] . strtr($class, '_', DIRECTORY_SEPARATOR) . '.php';
                break;
        }

        // 检查文件是否存在
		if(false === file_exists($file)){
            throw new F_Application_Exception('文件 ['.$file.'] 不存在', 5555);
		} else {
			require $file;
		}
    }
    
    /**
     * 获取错误调试类对象
     * 
     * @return \Whoops\Run
     */
    public function getWhoops()
    {
        return $this->_whoops;
    }
    
    /**
     * 获取全局配置文件
     * 
     * @return array
     */
    public function getConfigs($sectionName = '')
    {
        if (empty($sectionName)) {
            return $this->_configs;
        }
        if (isset($this->_configs[$sectionName])) {
            return $this->_configs[$sectionName];
        } else {
            throw new F_Application_Exception("Section '$sectionName' cannot be found in application.cfg.php");
        }
    }
    
    /**
     * 引导程序
     * 
     * @return F_Application
     */
    public function bootstrap()
    {
        //设置默认时区
        date_default_timezone_set('Asia/Chongqing');

        if (isset($this->_configs['phpSettings'])) {//设置php.ini
            foreach ($this->_configs['phpSettings'] as $k => $v) {
                ini_set($k, $v);
            }
        }
        
        require $this->_configs['bootstrap']['path'];
        $this->_bootstrapObj = new $this->_configs['bootstrap']['class']();
        return $this;
    }
    
    /**
     * 开始运行整个框架机制
     */
    public function run()
    {
        $frontObj = F_Controller_Front::getInstance();
        $response = $frontObj->dispatch();
    }
}