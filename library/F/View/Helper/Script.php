<?php
/**
 * 在 页面底部 body 标签前加载JS
 *
 * 1 使用 LAB.2.0.3.js 方式加载 - 并行下载 | 顺序执行
 * $this->script()->appendScript('page/page_reg.js');
 * $this->script()->prependScript('page/page_reg.js');
 *
 * @author allen <allen@yuorngcorp.com>
 * @package F_View
 */

class F_View_Helper_Script
{

    /**
     * 期望加载的脚本列表
     *
     * @var array
     */
    private $_scripts = array();

    /**
     * 当前加载的脚本是那个 - 为了配合 wait()，记录调用wait()前的那个脚本
     *
     * @var string
     */
    private $_current = '';

    /**
     * 记录需要添加wait()的脚本列表
     *
     * @var array
     */
    private $_waits = array();

    /**
     * 记录需要添加wait()的脚本列表
     *
     * @var array
     */
    private $_lastwait = array();

    /**
     * JS 版本号缓存
     *
     * @var array
     */
    private $_jsVersionCache = array();

    /**
     * JS CDN 配置
     *
     * @var array
     */
    private $_jsExternalCDN = array();

    /**
     * cdn使用配置
     *
     * @var string
     */
    private $_cdnIsUse = 'off';
    
    /**
     * combo使用配置
     * 
     * @var string 
     */
    private $_comboIsUse = 'off';
    
    /**
     * 是否开启独立域名访问css
     * 
     * @var string 
     */
    private $_isDedicatedDomain = 'off';

    public function __construct()
    {
        if(!Utils_EnvCheck::isDevelopment()){//测试 或 生产环境
            $this->_cssVersionCache = include ROOT_PATH . '/runtime/version/jsVersionCache.php';
        }
        $assetCfgs = F_Application::getInstance()->getConfigs('asset');
        $this->_isDedicatedDomain = $assetCfgs['isDedicatedDomain'];
        $this->_cdnIsUse = $assetCfgs['cdn']['jsEnable'];
        $this->_jsExternalCDN = $assetCfgs['cdn']['js'];
        $this->_comboIsUse = $assetCfgs['combo']['jsEnable'];
    }
    
    /**
     * 设置 html script标签
     * 
     * @return \F_View_Helper_Script
     */
    public function script()
    {
        return $this;
    }

    /**
     * 把 script 路径
     *
     * @param string $scriptPath 脚本路径，不需要 /
     * @param string $isMinify 当前路径是否是需要 minify合并压缩的，是 = minify | 否 = null
     * @param string $minifyBase 基础路径，根路径 - 在minify寻找js时,不需要前后的【/】，minify中会自动添加
     * @return \F_View_Helper_Script
     */
    public function prependScript($scriptPath, $isMinify = null, $minifyBase = 'js')
    {
        if('minify' == $isMinify){
            return $this->_add('prepend', $this->_getMinPath($scriptPath, $minifyBase));
        } else {
            return $this->_add('prepend', $scriptPath);
        }
    }

    /**
     * 把 script 路径
     *
     * @param string $scriptPath 脚本路径，不需要 /
     * @param string $isMinify 当前路径是否是需要 minify合并压缩的，是 = minify | 否 = null
     * @param string $minifyBase 基础路径，根路径 - 在minify寻找js时,不需要前后的【/】，minify中会自动添加
     * @return \F_View_Helper_Script
     */
    public function appendScript($scriptPath, $isMinify = null, $minifyBase = 'js')
    {
        if('minify' == $isMinify){
            return $this->_add('append', $this->_getMinPath($scriptPath, $minifyBase));
        } else {
            return $this->_add('append', $scriptPath);
        }
    }

    /**
     * lab wait
     *
     * @param object $func 匿名函数
     * @return \F_View_Helper_Script
     */
    public function wait($func = 1)
    {
        if(gettype($func) == 'object'){
            $args = func_get_args();
            array_splice($args, 0, 1);
            if(count($args) > 0){
                $this->_waits[md5($this->_current)] = PHP_EOL . call_user_func_array($func, $args);
            } else {
                $this->_waits[md5($this->_current)] = PHP_EOL . $func();
            }
        } else {
            $this->_waits[md5($this->_current)] = $func;
        }
        return $this;
    }

    /**
     * lab 在所有 script 之后的 wait
     *
     * @param object $func 匿名函数
     * @return \F_View_Helper_Script
     */
    public function lastWait($func = 1)
    {
        if(gettype($func) == 'object'){
            $args = func_get_args();
            array_splice($args, 0, 1);
            if(count($args) > 0){
                array_push($this->_lastwait, PHP_EOL . call_user_func_array($func, $args));
            } else {
                array_push($this->_lastwait, PHP_EOL . $func());
            }
        } else {
            array_push($this->_lastwait, $func());
        }
        return $this;
    }

    /**
     * 最终输出
     *
     * @return string
     */
    public function __toString()
    {   
        $str   = '$LAB';
        $begin = '<script type="text/javascript">';
        $end   = '</script>';
        $total = count($this->_scripts);
        if($total > 0){
            $lastWait = '';
            if(!empty($this->_lastwait)){
                $lastWait = implode(PHP_EOL, $this->_lastwait);
            }
            foreach($this->_scripts as $k=>$v){
                $index = md5($v);
                $str .= PHP_EOL . '.script("'.$v.'")';
                if(isset($this->_waits[$index])){
                    if($this->_waits[$index] == 1){
                        $str .= '.wait()';
                    } else {
                        $str .= '.wait(function(){' . $this->_waits[$index] . PHP_EOL . '})';
                    }
                }
            }
            $str .= '.wait(function(){'.$lastWait . PHP_EOL.'})';
            $str .= ';';
        } else {
            $lastWait = '';
            if(!empty($this->_lastwait)){
                $lastWait = implode(PHP_EOL, $this->_lastwait);
            }
            $str .= '.wait(function(){'.$lastWait . PHP_EOL.'})';
            $str .= ';';
        }
        if(!empty($str)){
            $str = $begin. PHP_EOL . $str. PHP_EOL . $end . PHP_EOL;
        }
        return $str;
    }

    /**
     * 仅输出 <script type="text/javascript" src="%s"></script>
     *
     * @param string $scriptPath 脚本路径，不需要 /
     * @return \F_View_Helper_Script
     */
    public function outputScript($scriptPath)
    {
        $scriptPath = $this->_add('get', $scriptPath);
        $script     = '<script type="text/javascript" src="%s"></script>';
        $str        = sprintf($script, $scriptPath).PHP_EOL;
        echo $str;
        return $this;
    }
    
    /**
     * 检测是否是需要使用 cdn URL加载
     *
     * @param string $scriptPath 脚本路径，不需要 /
     * @return boolean false 不需要
     */
    private function _checkCDN($scriptPath)
    {
        if('off' != $this->_cdnIsUse){//禁用第三方CDN加载JS
            $scriptPathAry = explode('/', $scriptPath);
            $index = strtr(end($scriptPathAry), '.', '_');
            return (isset($this->_jsExternalCDN[$index]))?$index:false;
        }        
        return false;
    }

    /**
     * 获取 minify 路径处理
     *
     * @param array $scriptPathAry array('plugin/utility.js', 'public.js')
     * @param string $minifyBase 基础路径，根路径 - 在minify寻找js时,不需要前后的【/】，minify中会自动添加
     * @return string
     */
    private function _getMinPath(&$scriptPathAry, $minifyBase)
    {
        $version = 0;
        foreach($scriptPathAry as &$scriptPath){
            $scriptUrl = $this->_add('get', $scriptPath, false);
            $scriptUrlAry = explode('?', $scriptUrl);
            if($version < $scriptUrlAry[1]){
                $version = $scriptUrlAry[1];
            }
            $scriptPath = $scriptUrlAry[0];
        }
        if ('on' === $this->_isDedicatedDomain) {//如果开启独立域名访问
            //todo
        } else {
            return '/asset/minify/?b='.$minifyBase.'&f=' . implode(',', $scriptPathAry) . '&' . $version;
        }
    }
    
    /**
     * 将 处理好的 script path 添加到 _scripts变量中
     *
     * @param string $mode append 追加到尾 | prepend 追加到头
     * @param string $scriptPath 脚本路径，不需要 /
     * @return \F_View_Helper_Script
     */
    private function _add($mode, $scriptPath)
    {
        $this->_current = $scriptPath;
        if (!preg_match('/minify\/\?f=/i', $scriptPath)) {//不是minify
            if (($index = $this->_checkCDN($scriptPath))) {
                $this->_current = $scriptPath = $this->_jsExternalCDN[$index];
            } else {
                $this->_current = $scriptPath = $this->_scriptPath($scriptPath);
            }
        }
        switch ($mode) {
            case 'prepend':
                array_unshift($this->_scripts, $scriptPath);
                break;
            case 'append':
                array_push($this->_scripts, $scriptPath);
                break;
            case 'get':
                return $scriptPath;
                break;
        }
        return $this;
    }
    
    /**
     * 处理传入的脚本路径，添加上版本号
     *
     * @param string $scriptPath 脚本路径，不需要 /
     * @return string
     */
    private function _scriptPath($scriptPath)
    {
        $tmpArray = explode('/js/', $scriptPath);
        if(count($tmpArray) < 2){
            throw new F_View_Exception('prepend() expects a data token; please use one of the custom _scriptPath() methods');
        }
        
        if (preg_match('%\?\?%', $tmpArray[1])) {//需要合并、压缩 js
            $tmpArray[1] = ltrim($tmpArray[1], '?');
            $itemArray = explode(',', $tmpArray[1]);
            $lastVersion = 0;
            foreach ($itemArray as $item) {
                $version = $this->_setJsVersion($item);
                if($lastVersion < $version){
                    $lastVersion = $version;
                }
            }
            $scriptPath .= '?v=' . $lastVersion;
        } else {
            $scriptPath .= '?v=' . $this->_setJsVersion($tmpArray[1]);
        }
        return $scriptPath;
    }
    
    /**
     * js 版本号
     * 
     * @param string $item
     * @return int
     */
    private function _setJsVersion($item)
    {
        if(Utils_EnvCheck::isProduction()){//正式环境
            $cssPath = '/' . $item;
            if(isset($this->_jsVersionCache[$cssPath])){
                return $this->_jsVersionCache[$cssPath];
            } else {
                return 0;
            }
        } else {
            return time();
        }
    }
}