<?php
/**
 * 框架应用程序session基础类
 * 
 * @category F
 * @package F_Session
 * @author allen <allenifox@163.com>
 */
final class F_Session
{
    /**
     * session 是否写完成
     * 
     * @var boolean 
     */
    private static $_writeClosed = false;
    
    /**
     * 检测session 是否开启
     *
     * @var bool
     */
    private static $_sessionStarted = false;

    private function __construct()
    {
        //empty
    }
    
    /**
     * getId() - get the current session id
     *
     * @return string
     */
    public static function getId()
    {
        return session_id();
    }
    
    /**
     * isStarted() - convenience method to determine if the session is already started.
     *
     * @return bool
     */
    public static function isStarted()
    {
        return self::$_sessionStarted;
    }
    
    /**
     * writeClose() - Shutdown the sesssion, close writing and detach $_SESSION from the back-end storage mechanism.
     * This will complete the internal data transformation on this request.
     *
     * @return void
     */
    public static function writeClose()
    {
        if (self::$_writeClosed) {
            return;
        }
        session_write_close();
        self::$_writeClosed = true;
    }
}