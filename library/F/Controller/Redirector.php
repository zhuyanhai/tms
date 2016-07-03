<?php
/**
 * url转向器 类
 * 
 * 主要是负责处理URL跳转
 * 
 * @category F
 * @package F_Controller
 * @subpackage F_Controller_Redirector
 * @author allen <allenifox@163.com>
 */
class F_Controller_Redirector
{
    /**
     * 单例实例
     * 
     * @var F_Controller_Redirector 
     */
    private static $_instance = null;
    
    /**
     * HTTP status code for redirects
     * @var int
     */
    protected $_code = 302;
    
    /**
     * Url to which to redirect
     * @var string
     */
    protected $_redirectUrl = null;
    
    /**
     * 单例模式
     * 
     * @return F_Controller_Redirector
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new F_Controller_Redirector();
        }
        
        return self::$_instance;
    }
    
    /**
     * 转到指定的URL
     * 
     * @param string $url
     * @param array $options
     * @return void
     */
    public function gotoUrlAndExit($url, $options = array())
    {
        $this->setGotoUrl($url, $options);
        $this->redirectAndExit();
    }
    
    /**
     * Validate HTTP status redirect code
     *
     * @param  int $code
     * @throws F_Controller_Exception on invalid HTTP status code
     * @return true
     */
    protected function _checkCode($code)
    {
        $code = (int)$code;
        if ((300 > $code) || (307 < $code) || (304 == $code) || (306 == $code)) {
            throw new F_Controller_Exception('Invalid redirect HTTP status code (' . $code  . ')');
        }
        return true;
    }
    
    /**
     * Set HTTP status code for {@link _redirect()} behaviour
     *
     * @param  int $code
     * @return F_Controller_Redirector Provides a fluent interface
     */
    public function setCode($code)
    {
        $this->_checkCode($code);
        $this->_code = $code;
        return $this;
    }
    
    /**
     * Retrieve HTTP status code to emit on {@link _redirect()} call
     *
     * @return int
     */
    public function getCode()
    {
        return $this->_code;
    }
    
     /**
     * Set redirect in response object
     *
     * @return void
     */
    protected function _redirect($url)
    {
        if (!preg_match('#^(https?|ftp)://#', $url)) {
            $host  = (isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');
            $proto = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=="off") ? 'https' : 'http';
            $port  = (isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:80);
            $uri   = $proto . '://' . $host;
            if ((('http' == $proto) && (80 != $port)) || (('https' == $proto) && (443 != $port))) {
                // do not append if HTTP_HOST already contains port
                if (strrchr($host, ':') === false) {
                    $uri .= ':' . $port;
                }
            }
            $url = $uri . '/' . ltrim($url, '/');
        }
        $this->_redirectUrl = $url;
        F_Controller_Response_Http::getInstance()->setRedirect($url, $this->getCode());
    }
    
    /**
     * Set a redirect URL string
     *
     * By default, emits a 302 HTTP status header, prepends base URL as defined
     * in request object if url is relative, and halts script execution by
     * calling exit().
     *
     * $options is an optional associative array that can be used to control
     * redirect behaviour. The available option keys are:
     * - exit: boolean flag indicating whether or not to halt script execution when done
     * - prependBase: boolean flag indicating whether or not to prepend the base URL when a relative URL is provided
     * - code: integer HTTP status code to use with redirect. Should be between 300 and 307.
     *
     * _redirect() sets the Location header in the response object. If you set
     * the exit flag to false, you can override this header later in code
     * execution.
     *
     * If the exit flag is true (true by default), _redirect() will write and
     * close the current session, if any.
     *
     * @param  string $url
     * @param  array  $options
     * @return void
     */
    public function setGotoUrl($url, array $options = array())
    {
        // prevent header injections
        $url = str_replace(array("\n", "\r"), '', $url);

        if (null !== $options) {
            if (isset($options['code'])) {
                $this->setCode($options['code']);
            }
        }

        $this->_redirect($url);
    }

    /**
     * exit(): Perform exit for redirector
     *
     * @return void
     */
    public function redirectAndExit()
    {
        // Close session, if started
        if (class_exists('F_Session', false) && F_Session::isStarted()) {
            F_Session::writeClose();
        } elseif (isset($_SESSION)) {
            session_write_close();
        }

        F_Controller_Response_Http::getInstance()->sendHeaders();
        exit();
    }
}