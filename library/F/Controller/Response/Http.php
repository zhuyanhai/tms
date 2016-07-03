<?php
/**
 * 前端控制器 - response 类
 * 
 * 主要是输出处理
 * 
 * @category F
 * @package F_Controller
 * @subpackage F_Controller_Response_Http
 * @author allen <allenifox@163.com>
 */
class F_Controller_Response_Http
{
    /**
     * 单例实例
     * 
     * @var F_Controller_Response_Http
     */
    private static $_instance = null;
    
    /**
     * Flag; if true, when header operations are called after headers have been
     * sent, an exception will be raised; otherwise, processing will continue
     * as normal. Defaults to true.
     *
     * @see canSendHeaders()
     * @var boolean
     */
    public $headersSentThrowsException = true;
    
    /**
     * Body 部分内容
     * 
     * @var string
     */
    protected $_body = '';
    
    /**
     * headers 数组. 每个 header 是 key -> value 形式
     * 
     * @var array
     */
    protected $_headers = array();
    
    /**
     * HTTP response code to use in headers
     * 
     * @var int
     */
    protected $_httpResponseCode = 200;
    
    /**
     * Flag; is this response a redirect?
     * 
     * @var boolean
     */
    protected $_isRedirect = false;
    
    /**
     * 构造函数
     *
     * @return void
     */
    private function __construct()
    {
        //empty
    }
    
    /**
     * 单例模式
     * 
     * @return F_Controller_Response_Http
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new F_Controller_Response_Http();
        }
        
        return self::$_instance;
    }
    
    /**
     * 发送响应内容, 包括所有 headers, rendering exceptions if so requested.
     *
     * @return void
     */
    public function sendResponse()
    {
        $this->sendHeaders();

        echo $this->_body;
    }
    
    /**
     * 发送响应内容(并且停止程序), 包括所有 headers, rendering exceptions if so requested.
     *
     * @return void
     */
    public function sendResponseAndExit()
    {
        $this->sendResponse();
        exit;
    }
    
    /**
     * 检测是否能发送 headers
     *
     * @param boolean $throw Whether or not to throw an exception if headers have been sent; defaults to false
     * @return boolean
     * @throws F_Controller_Response_Exception
     */
    public function canSendHeaders($throw = false)
    {
        $ok = headers_sent($file, $line);
        if ($ok && $throw && $this->headersSentThrowsException) {
            throw new F_Controller_Response_Exception('Cannot send headers; headers already sent in ' . $file . ', line ' . $line);
        }

        return !$ok;
    }
    
    /**
     * 设置所有 headers
     *
     * Sends any headers specified. If an {@link setHttpResponseCode() HTTP response code}
     * has been specified, it is sent with the first header.
     *
     * @return F_Controller_Response_Http
     */
    public function sendHeaders()
    {
        // Only check if we can send headers if we have headers to send
        if (count($this->_headers) || (200 != $this->_httpResponseCode)) {
            $this->canSendHeaders(true);
        } elseif (200 == $this->_httpResponseCode) {
            // Haven't changed the response code, and we have no headers
            return $this;
        }

        $httpCodeSent = false;

        foreach ($this->_headers as $header) {
            if (!$httpCodeSent && $this->_httpResponseCode) {
                header($header['name'] . ': ' . $header['value'], $header['replace'], $this->_httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
        }

        if (!$httpCodeSent) {
            header('HTTP/1.1 ' . $this->_httpResponseCode);
            $httpCodeSent = true;
        }

        return $this;
    }
    
    /**
     * 设置要输出的body
     */
    public function setBody($content)
    {
        $this->_body = $content;
    }
    
    /**
     * Normalize a header name
     *
     * Normalizes a header name to X-Capitalized-Names
     *
     * @param  string $name
     * @return string
     */
    protected function _normalizeHeader($name)
    {
        $filtered = str_replace(array('-', '_'), ' ', (string) $name);
        $filtered = ucwords(strtolower($filtered));
        $filtered = str_replace(' ', '-', $filtered);
        return $filtered;
    }
    
    /**
     * Set a header
     *
     * If $replace is true, replaces any headers already defined with that
     * $name.
     *
     * @param string $name
     * @param string $value
     * @param boolean $replace
     * @return Zend_Controller_Response_Abstract
     */
    public function setHeader($name, $value, $replace = false)
    {
        $this->canSendHeaders(true);
        $name  = $this->_normalizeHeader($name);
        $value = (string) $value;

        if ($replace) {
            foreach ($this->_headers as $key => $header) {
                if ($name == $header['name']) {
                    unset($this->_headers[$key]);
                }
            }
        }

        $this->_headers[] = array(
            'name'    => $name,
            'value'   => $value,
            'replace' => $replace
        );

        return $this;
    }
    
    /**
     * Set HTTP response code to use with headers
     *
     * @param int $code
     * @return F_Controller_Response_Http
     */
    public function setHttpResponseCode($code)
    {
        if (!is_int($code) || (100 > $code) || (599 < $code)) {
            // require_once 'Zend/Controller/Response/Exception.php';
            throw new Zend_Controller_Response_Exception('Invalid HTTP response code');
        }

        if ((300 <= $code) && (307 >= $code)) {
            $this->_isRedirect = true;
        } else {
            $this->_isRedirect = false;
        }

        $this->_httpResponseCode = $code;
        return $this;
    }
    
    /**
     * Retrieve HTTP response code
     *
     * @return int
     */
    public function getHttpResponseCode()
    {
        return $this->_httpResponseCode;
    }
    
    /**
     * Is this a redirect?
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return $this->_isRedirect;
    }
    
    /**
     * Set redirect URL
     *
     * Sets Location header and response code. Forces replacement of any prior
     * redirects.
     *
     * @param string $url
     * @param int $code
     * @return F_Controller_Response_Http
     */
    public function setRedirect($url, $code = 302)
    {
        $this->canSendHeaders(true);
        $this->setHeader('Location', $url, true)
             ->setHttpResponseCode($code);

        return $this;
    }
    
}