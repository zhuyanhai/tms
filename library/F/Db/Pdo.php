<?php
/**
 * PDO 封装类
 * 
 * @method string lastInsertId (([ string $name= NULL ] ) 返回最后insert的行的主键值
 */
final class F_Db_Pdo
{
    /**
     * 数据库缩略名字
     * 
     * @var string 
     */
    private $_dbShortName = null;
    
    /**
     * 当前要用到的数据库连接配置
     * 
     * @var array 
     */
    private $_dsn = array();
    
    /**
     * 当前数据库的链接句柄
     * 
     * @var PDO
     */
    private $_dbHandel = null;
    
    /**
     * 当前数据库的stmt句柄
     * 
     * @var PDOStatement
     */
    private $_stmtHandel = null;
    
    /**
     * 获取PDO实例
     * 
     * @staticvar array $instances
     * @param array $options
     * @return \F_Db_Pdo
     */
    public static function getInstance($options)
    {
        static $instances = array();

        if (!isset($options['dbShortName'])) {
            throw new F_Db_Exception('数据表中 $options 配置错误');
        }

        if (!isset(F_Db_PdoConnectPool::$dbDsn[$options['dbShortName']])) {//每个库使用到dsn配置的初始加载
            F_Db_PdoConnectPool::bulidDbConfig($options['dbShortName']);
        } else {
            throw new F_Db_Exception(__METHOD__ . ' '.$options['dbShortName'].' 在 F_Db_PdoConnectPool::$dbDsn 中不存在');
        }
        
        if (!isset($instances[$options['dbShortName']])) {
            $instances[$options['dbShortName']] = new F_Db_Pdo($options);
        }
        
        return $instances[$options['dbShortName']];
    }
    
    /**
     * 构造函数
     * 
     * @param array $options
     */
    public function __construct($options)
    {
        $this->_dbShortName = $options['dbShortName'];
    }
    
    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->_dbHandel   = null;
        $this->_stmtHandel = null;
    }
    
    /**
     * 主动关闭数据库连接
     * 
     * @return \F_Db_Pdo
     */
    public function close()
    {
        $this->_dbHandel   = null;
        $this->_stmtHandel = null;
        return $this;
    }
    
    /**
     * 插入数据
     * 
     * @param array $rowData 需要插入的数据
     * @param string $tableName 数据表名字[格式：db.table]
     * @return string
     */
    public function insert($rowData, $tableName)
    {
        $fields = array_keys($rowData);
        $this->prepare('INSERT INTO '. $tableName . ' ('.implode(',', $fields).') VALUES (:'.implode(',:', $fields).')');
        foreach ($rowData as $k => $v) {
            $this->bindParam(':'.$k, $v, PDO::PARAM_STR);
        }
        return $this->execute()->lastInsertId();
    }
    
    /**
     * 更新数据
     * 
     * @param array $rowData 需要更新的数据
     * @param string $whereCondition 更新条件
     * @param array $whereBind 更新条件绑定数据
     * @param string $tableName 数据表名字[格式：db.table]
     * @return int
     * @throws PDOException
     */
    public function update($rowData, $whereCondition, $whereBind, $tableName)
    {
        $fields = array_keys($rowData);
        
        $sql = 'UPDATE ' . $tableName . ' SET ';
        foreach ($rowData as $rk => $rv) {
            $sql .= $rk . '=:FIELD_' . $rk . ',';
        }
        $sql = rtrim($sql, ',');
        $sql .= ' WHERE ' . $whereCondition; 
        $this->prepare($sql);
        foreach ($rowData as $rk => $rv) {
            $this->bindParam(':FIELD_'.$rk, $rv, PDO::PARAM_STR);
        }
        foreach ($whereBind as $wk => $wv) {
            $wkParam = ':'.$wk;
            if (!preg_match('%'.$wkParam.'%', $whereCondition)) {
                throw new PDOException('Pdo update where bindParam ['.$wkParam.'] not exist');
            }
            $this->bindParam($wkParam, $wv, PDO::PARAM_STR);
        }
        return $this->execute()->_stmtHandel->rowCount();
    }

    /**
     * prepare 预处理sql
     * 
     * @param string $sql
     * @return \F_Db_Pdo
     * @throws PDOException
     */
    public function prepare($sql)
    {
    	$this->_connect();
        try {
            $this->_stmtHandel = $this->_dbHandel->prepare($sql);
            if (!$this->_stmtHandel) {
                throw new PDOException('Pdo prepare error');
            }
        } catch (PDOException $e) {
            throw new PDOException('Pdo prepare error: '.$e->getMessage());
        }
    	return $this; 
    }
    
    /**
     * 绑定参数
     * 
     * @param string $parameter
     * @param mixed $variable
     * @return \F_Db_Pdo
     */
    public function bindParam($parameter , $variable = null)
    {
        $this->_stmtHandel->bindParam($parameter, $variable);
        return $this;
    }
    
    /**
     * 执行
     * 
     * @return \F_Db_Pdo
     * @throws F_Db_Exception
     */
    public function execute()
    {
        if (!$this->_stmtHandel->execute()) {
            $error = $this->_stmtHandel->errorInfo();
            throw new F_Db_Exception('execute failed : '.$error[1].' '.$error[2]);
        }
        return $this;
    }
    
    /**
     * 获取单行记录
     * 
     * @return mixed
     * @throws F_Db_Exception
     */
    public function fetchRow()
    {
        if (!$this->_stmtHandel->execute()) {
            $error = $this->_stmtHandel->errorInfo();
            throw new F_Db_Exception('execute failed : '.$error[1].' '.$error[2]);
        }
        return $this->_stmtHandel->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取多行记录
     * 
     * @return array
     * @throws F_Db_Exception
     */
    public function fetchAll()
    {
        if (!$this->_stmtHandel->execute()) {
            $error = $this->_stmtHandel->errorInfo();
            throw new F_Db_Exception('execute failed : '.$error[1].' '.$error[2]);
        }
        return $this->_stmtHandel->fetchAll(PDO::FETCH_ASSOC);
    }
    
     /**
     * 主动切换使用的【主】数据库连接
     * 
     * @return \F_Db_Pdo
     */
    public function changeMaster()
    {
        $this->_dsn = F_Db_PdoConnectPool::$dbDsn[$this->_dbShortName]['master'];
        $this->_dbHandel   = null;
        $this->_stmtHandel = null;
        return $this;
    }
    
    /**
     * 主动切换使用的【从】数据库连接
     * 
     * @return \F_Db_Pdo
     */
    public function changeSlave()
    {
        $this->_dsn = F_Db_PdoConnectPool::$dbDsn[$this->_dbShortName]['slave'];
        $this->_dbHandel   = null;
        $this->_stmtHandel = null;
        return $this;
    }
    
    /**
     * 魔术方法，调用PDO对象中的方法
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $this->_connect();
    	return call_user_func_array(array($this->_dbHandel, $method), $args);
    }
    
    /**
     * 连接数据库
     * 
     * @throws F_Db_Exception
     */
    private function _connect()
    {
        if (is_null($this->_dbHandel)) {
            if (empty($this->_dsn)) {
                throw new F_Db_Exception('$this->_dsn 不能为空');
            }
            $this->_dbHandel = F_Db_PdoConnectPool::get($this->_dsn);
        }
    }
}