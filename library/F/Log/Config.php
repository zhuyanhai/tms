<?php
/**
 * 日志 - 配置
 * 
 * @category F
 * @package F_Log
 * @author allen <allenifox@163.com>
 */
final class F_Log_Config
{   
    /**
     * 日志级别定义
     * 
     * @var array
     */
    public static $levels = array(
        'DEBUG', 'INFO', 'WARN', 'ERROR', 'FATAL'
    );
    
    /**
     * 日志记录格式定义
     * 
     * @var array 
     */
    public static $formatters = array(
        'generic' => '{time} {level} [{logger}] {uri} """{message}"""',
    );
    
    /**
     * 记录日志实现方式
     * 
     * @var array
     */
    public static $handlers = array(
        'file' => array(
			'driver'    => 'file',//记录日志的实现方式
			'level'     => array('WARN', 'ERROR', 'FATAL'),//记录何种等级的日志
			'formatter' => 'generic',//记录日志使用到的格式名字
			'enabled'   => true,//是否启用
		),
        'console' => array(
			'driver'    => 'echo',//记录日志的实现方式
			'level'     => array('DEBUG', 'INFO'),//记录何种等级的日志
			'formatter' => 'generic',//记录日志使用到的格式名字
			'enabled'   => true,//是否启用
		),
    );
    
    /**
     * 获取日志的基础路径
     * 
     * @return string
     */
    public static function getBasePath()
    {
        return ROOT_PATH . '/runtime/log/';
    }
}