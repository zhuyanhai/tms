<?php
/**
 * 页面中使用 title 标签加载
 *
 * @author allen <allen@yuorngcorp.com>
 * @package F_View
 */
final class F_View_Helper_HeadTitle
{
    /**
     * title 字符串
     * 
     * @var string 
     */
    private $_title = '';
            
    public function __construct()
    {
        //empty
    }
    
    /**
     * 设置 html title标签
     * 
     * @param string $content
     * @return \F_View_Helper_HeadTitle
     */
    public function headTitle($content = null)
    {
        if (!is_null($content)) {
            $this->_title .= $content;
        }
        return $this;
    }
    
    /**
     * 输出时
     */
    public function __tostring()
    {
        return $this->_title;
    }
}