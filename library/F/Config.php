<?php
/**
 * 框架应用程序获取配置类
 * 
 * @category F
 * @package F_Config
 * @author allen <allenifox@163.com>
 */
final class F_Config
{
    /**
     * 配置数组
     * 
     * @var array
     */
    private static $_configs = array();
    
    /**
     * 之后加载配置文件的名字
     * 
     * @var string
     */
    private static $_lastFlag = null;
    
    private function __construct()
    {
        //empty
    }

    /**
     * 加载其他配置
     * 
     * @param string $filename 配置文件的路径 /configs/db.cfg.php
     * @return F_Config
     */
    public static function load($filename)
    {
        static $instance = null;
        
        if (is_null($instance)) {
            $instance = new F_Config();
        }
        
        $filename = APPLICATION_PATH . $filename;
        if (!isset(self::$_configs[$filename])) {
            if (!file_exists($filename)) {
                throw new F_Exception("F_Config::load 文件 {$filename} 找不到");
            }
            self::$_configs[$filename] = include $filename;
        }
        self::$_lastFlag = $filename;
        
        return $instance;
    }
    
    /**
     * 获取其他配置
     * 
     * @param string $sectionName
     * @param string $optionName
     * @return mixed
     */
    public function get($sectionName = '', $optionName = '')
    {
        if (empty($sectionName) && !empty($optionName)) {
            throw new F_Exception('F_Config->get 中 不能参数 $sectionName为空，$optionName不为空');
        }
        
        if (empty(self::$_lastFlag) || !isset(self::$_configs[self::$_lastFlag])) {
            throw new F_Exception('F_Config->get 中 self::$_lastFlag('.self::$_lastFlag.') 不能为空 或 self::$_lastFlag 在 self::$_lastConfig 中不存在');
        }
        
        $returnCfg = self::$_configs[self::$_lastFlag];
        
        if (empty($sectionName) && empty($optionName)) {
            return $returnCfg;
        }
        
        if (!isset($returnCfg[$sectionName])) {
            throw new F_Exception('F_Config->get 中 $sectionName('.$sectionName.') 在 $returnCfg 中不存在');
        }
        
        if (empty($optionName)) {
            return $returnCfg[$sectionName];
        }
        
        if (!isset($returnCfg[$sectionName][$optionName])) {
            throw new F_Exception('F_Config->get 中 $optionName('.$optionName.') 在 $returnCfg[$sectionName('.$sectionName.')] 中不存在');
        }
        
        return $returnCfg[$sectionName][$optionName];
    }
}