<?php
/**
 * 日志类
 * 
 * @category F
 * @package F_Log
 * @author allen <allenifox@163.com>
 * 
 * @method void debug(string $message) 调试日志，指出细粒度信息事件对调试应用程序是非常有帮助的
 * @method void info(string $message) 过程日志，表明消息在粗粒度级别上突出强调应用程序的运行过程
 * @method void warn(string $message) 警告日志，表明会出现潜在错误的情形
 * @method void error(string $message) 错误日志，指出虽然发生错误事件，但仍然不影响系统的继续运行
 * @method void fatal(string $message) 致命日志，指出每个严重的错误事件将会导致应用程序的退出
 * 
 * 例子：
 * F_Log::factory()->error('error model');
 * F_Log::factory('product')->debug('debug model');
 */
final class F_Log
{
    /**
     * 对象实例集合
     * 
     * @var array 
     */
    private static $_instances = array();
    
    /**
     * 工厂方法
     * 
     * @param string $logger 记录器名字，例如：thread=帖子相关的日志记录器
     * @return F_Log
     */
    public static function factory($logger = 'default')
    {
        if (empty(self::$_instances[$logger])) {
            self::$_instances[$logger] = new self($logger);
        }
        return self::$_instances[$logger];
    }
    
    /**
     * 记录器名字
     * 
     * @var string
     */
    private $_logger;
    
    /**
     * 日志处理类
     * 
     * 不同的处理方式，文件、直接输出 等
     * 
     * @var array
     */
    private $_logHandlers = array();
    
    /**
     * 构造函数
     * 
     * @param string $logger
     */
    public function __construct($logger)
    {
        $this->_logger = $logger;
        foreach (F_Log_Config::$handlers as $handler) {
            if (!empty($handler['enabled']) && $handler['enabled'] == true) {
                $this->_logHandlers[] = $handler;
            }
        }
    }
    
    /**
     * 魔术方法
     * 
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args)
    {
        $method = strtoupper($method);
        if (!in_array($method, F_Log_Config::$levels)) {
            throw F_Log_Exception(sprintf('method not allowed: %s', $method));
        }
        if (empty($this->_logHandlers)) {
            throw F_Log_Exception(sprintf('_loggerHandlers is empty'));
        }
        foreach ($this->_logHandlers as $handler) {
            if (in_array($method, $handler['level'])) {
                $class = 'F_Log_Handler_' . ucfirst($handler['driver']);
                $obj   = $class::getInstance($handler['driver']);
                $obj->setFormatterArgs(array(
                    'message' => $args[0],
                    'level'   => $method,
                    'logger'  => $this->_logger,
                ));
                $obj->save();
            }
        }
    }

}