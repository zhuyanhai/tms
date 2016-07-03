<?php
/**
 * 页面中使用 link 标签加载 css
 *
 * 一个页面单个 css，当然多个也可以这样
 * $this->headLink()->appendStylesheet('page/page_index.css')->appendStylesheet('plugin/utility.css');
 * 一个页面多个 css path
 * $this->headLink()->appendStylesheet(array('page/page_index.css', 'plugin/utility.css'));
 * 合并压缩 css path
 * $this->headLink()->appendStylesheet(array('page/page_index.css', 'plugin/utility.css'), 'minify');
 *
 * @author allen <allen@yuorngcorp.com>
 * @package F_View
 */
final class F_View_Helper_HeadLink
{
    /**
     * CSS 版本号缓存
     *
     * @var array
     */
    private $_cssVersionCache = array();

    /**
     * css link
     *
     * @var array
     */
    private $_stylesheets = array();

    /**
     * CSS CDN 配置
     *
     * @var array
     */
    private $_cssExternalCDN = array();

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
            $this->_cssVersionCache = include ROOT_PATH . '/runtime/version/cssVersionCache.php';
        }
        $assetCfgs = F_Application::getInstance()->getConfigs('asset');
        $this->_isDedicatedDomain = $assetCfgs['isDedicatedDomain'];
        $this->_cdnIsUse = $assetCfgs['cdn']['cssEnable'];
        $this->_cssExternalCDN = $assetCfgs['cdn']['css'];
        $this->_comboIsUse = $assetCfgs['combo']['cssEnable'];
    }
    
    /**
     * 设置 html link标签
     * 
     * @return \F_View_Helper_HeadLink
     */
    public function headLink()
    {
        return $this;
    }

    public function __call($method, $args)
    {
        switch($method){
            case 'prependStylesheet':
            case 'appendStylesheet':
                $this->_setCssVersion($args, $method);
                break;
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
        $outputStr = '';
        $outputStr .= implode(PHP_EOL, $this->_stylesheets);
        return $outputStr;
    }

    /**
     * 仅输出 <link href="%s" media="screen" rel="stylesheet" type="text/css" />
     *
     * @param string $cssPath css路径，不需要 /
     * @param boolean $isNude true 不需要版本号和域名处理，仅需要直接返回传入的文件路径或返回待html标签的
     * @return string
     */
    public function outputCss($cssPath, $isNude = false)
    {
        $cssPathAry = array($cssPath);
        return $this->_setCssVersion($cssPathAry, 'getStylesheet', $isNude);
    }
    
    /**
     * 检测是否是需要使用 cdn URL加载
     *
     * @param string $cssPath css路径
     * @return boolean false 不需要
     */
    private function _checkCDN($cssPath)
    {
        if('off' != $this->_cdnIsUse){//禁用第三方CDN加载CSS
            $cssPathAry = explode('/', $cssPath);
            $index = strtr(end($cssPathAry), '.', '_');
            return (isset($this->_cssExternalCDN[$index]))?$index:false;
        }
        return false;
    }

    /**
     * 处理传入的css路径，添加上版本号
     *
     * @param array $args
     * @param string $method append prepend get
     * @param boolean $isNude true 不需要版本号和域名处理，仅需要直接返回传入的文件路径或返回待html标签的
     * @return string
     */
    private function _setCssVersion(&$args, $method, $isNude = false)
    {
        if (1 < count($args) && $args[1] == 'minify') {
            $stylesheet = $this->_getMinPath($args[0], (isset($args[2])?$args[2]:'css'));
            if (!$isNude) {
                $stylesheet = '<link href="'.$stylesheet.'" media="screen" rel="stylesheet" type="text/css" />';
            }
            switch ($method) {
                case 'prependStylesheet':
                    array_unshift($this->_stylesheets, $stylesheet);
                    break;
                case 'appendStylesheet':
                    array_push($this->_stylesheets, $stylesheet);
                    break;
                case 'getStylesheet':
                    return $stylesheet;
                    break;
            }
        } else {
            $stylesheetAry = $args[0];
            if (!is_array($stylesheetAry)) {
                $stylesheetAry = array($stylesheetAry);
            }
            foreach ($stylesheetAry as $v) {
                if (preg_match('/\.css$/i', $v)) {
                    if (($index = $this->_checkCDN($v))) {
                        $v = $this->_cssExternalCDN[$index];
                    } else {
                        $v .= '?' . ((isset($this->_cssVersionCache[$v]))?$this->_cssVersionCache[$v]:(Utils_EnvCheck::isDevelopment())?time():0);
                        if ('on' === $this->_isDedicatedDomain) {//如果开启独立域名访问
                            //todo
                        }
                    }
                    if (!$isNude) {
                        $v = '<link href="'.$v.'" media="screen" rel="stylesheet" type="text/css" />';
                    }
                    switch ($method) {
                        case 'prependStylesheet':
                            array_unshift($this->_stylesheets, $v);
                            break;
                        case 'appendStylesheet':
                            array_push($this->_stylesheets, $v);
                            break;
                        case 'getStylesheet':
                            unset($stylesheetAry);
                            return $v;
                            break;
                    }
                }
            }
            unset($stylesheetAry);
        }
    }

    /**
     * 获取 minify 路径
     *
     * @param array $cssPathAry array('plugin/a.css', 'v.css')
     * @param string $minifyBase 基础路径，根路径 - 在minify寻找js时,不需要前后的【/】，minify中会自动添加
     * @return string
     */
    private function _getMinPath(&$cssPathAry, $minifyBase)
    {
        $version = $preTime = 0;
        if ('off' != $this->_comboIsUse){//禁用nginx combo加载合并css
            foreach($cssPathAry as &$cssPath) {
                if(preg_match('/\.css$/i', $cssPath)){
                    $version = ((isset($this->_cssVersionCache[$cssPath]))?$this->_cssVersionCache[$cssPath]:(Utils_EnvCheck::isDevelopment())?time():0);
                    ($preTime > $version)?$version = $preTime:$preTime = $version;
                }
            }
            unset($preTime);
            if ('on' === $this->_isDedicatedDomain) {//如果开启独立域名访问
                //todo
            } else {
                return '/asset/minify/?b='.$minifyBase.'&f=' . implode(',', $cssPathAry) . '&' . $version;
            }
        } else {
            $minifyBase = ltrim($minifyBase, 'css/') . '/';
            foreach ($cssPathAry as &$cssPath) {
                if (preg_match('/\.css$/i', $cssPath)) {
                    $version = ((isset($this->_cssVersionCache[$cssPath]))?$this->_cssVersionCache[$cssPath]:(Utils_EnvCheck::isDevelopment())?time():0);
                    ($preTime > $version)?$version = $preTime:$preTime = $version;
                    $cssPath = $minifyBase . ((!Utils_EnvCheck::isDevelopment())?preg_replace('/\.css$/i', '.min.css', $cssPath):$cssPath);
                }
            }
            unset($preTime);
            if ('on' === $this->_isDedicatedDomain) {//如果开启独立域名访问
                //todo
            } else {
                return '/asset/css/??' . implode(',', $cssPathAry) . '?v=' . $version;
            }
        }
    }

}
