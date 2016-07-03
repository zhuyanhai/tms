<?php
/**
 * 抽象类
 */
abstract class F_Log_Handler_Abstract
{
    /**
     * 对象实例集合
     * 
     * @var array 
     */
	protected static $_instances = array();
    
    /**
     * 【单例模式】获取对象实例
     * 
     * @param string $driver
     * @return F_Log_Handler_Abstract
     */
	public static function getInstance($driver)
	{
		if (empty(self::$_instances[$driver])) {
			$class = 'F_Log_Handler_'.$driver;
			self::$_instances[$driver] = new $class($driver);
		}
		return self::$_instances[$driver];
	}
    
	protected $_formatter;
	protected $_formatterObj = null;

	public function __construct($driver)
	{
		foreach (F_Log_Config::$handlers as $handler) {
			if (strtolower($handler['driver']) == $driver) {
				$this->_formatter = F_Log_Config::$formatters[$handler['formatter']];
			}
		}
		$this->_formatterObj = new F_Log_Formatter();
	}

	public function setFormatterArgs($args)
	{
		$this->_formatterObj->args = $args;
	}
    
	protected function _format()
	{
		preg_match_all('/\{([a-zA-Z_-]+)\}/u', $this->_formatter, $matches);
		$replaceArr = array();
		foreach ($matches[1] as $key) {
			$replaceArr['{'.$key.'}'] = $this->_formatterObj->{'get'.$key}();
		}
		return strtr($this->_formatter, $replaceArr);
	}

	abstract function save();
}
