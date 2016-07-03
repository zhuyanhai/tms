<?php
/**
 * PDO 连接池封装类
 */
final class F_Db_PdoConnectPool
{
    /**
     * 数据库连接需要用到的配置
     * 
     * @var array 
     */
    public static $dbDsn = array();
    
    /**
     * 创建PDO链接对象
     */
    public static function &get($connectConfig)
    {
        static $dbHandelPool = array();
        if (!isset($connectConfig['host']) || !isset($connectConfig['port']) || !isset($connectConfig['username']) || !isset($connectConfig['password']) || !isset($connectConfig['charset'])) {
            throw new F_Db_Exception('Connection failed: params error');
        }
        $dsn = 'mysql:host='.$connectConfig['host'].';port='.$connectConfig['port'];
        if (!isset($dbHandelPool[$dsn])) {
            try {
                $dbHandelPool[$dsn] = new PDO($dsn, $connectConfig['username'], $connectConfig['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \''.$connectConfig['charset'].'\''));
            } catch (PDOException $e) {
                throw new F_Db_Exception('Connection failed: '.$e->getMessage());
            }
        }
        return $dbHandelPool[$dsn];
    }
    
    /**
     * 第一次访问数据库时，初始bulid数据库连接需要的配置
     */
    public static function bulidDbConfig($dbShortName)
    {
        static $defaultParams = array();
        
        if (!isset(self::$dbDsn[$dbShortName])) {//配置初始加载
            $dbConfigsObj = F_Config::load('/configs/db.cfg.php');
            if (empty($defaultParams)) {
                $defaultParams = $dbConfigsObj->get('default');
            }
            $dbConfigs = $dbConfigsObj->get($dbShortName);
            self::$dbDsn[$dbShortName]['dbName'] = $dbConfigs['dbName'];
            if (isset($dbConfigs['params'])) {
                $params = array_merge($defaultParams, $dbConfigs['params']);
            } else {
                $params = $defaultParams;
            }
            self::$dbDsn[$dbShortName]['master'] = $params['master'];
            self::$dbDsn[$dbShortName]['slave']  = $params['slave'];
            unset($params, $dbConfigs);
        }
    }
    
    /**
     * 获取数据库全名
     */
    public static function getDbName($dbShortName)
    {
        if (!isset(self::$dbDsn[$dbShortName])) {
            self::bulidDbConfig($dbShortName);
        }
        return self::$dbDsn[$dbShortName]['dbName'];
    }
}