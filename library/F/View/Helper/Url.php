<?php
/**
 * url 处理，会更具路由反解析需要使用的url
 *
 * @author allen <allen@yuorngcorp.com>
 * @package F_View
 */
final class F_View_Helper_Url
{
    /**
     * 重组 url
     * 
     * @param string $mca /module/controller/action/
     * @param array|string $params URL 参数，使用数组 或 域名，使用字符串
     * @param string $domain http://technology.utan.com
     * @return string
     * 
     * @example
     * 
     * $this->url(/product/index, 'technology'); 指定域名，不指定参数
     * $this->url(/product/index, array('id=>1), 'technology'); 执行参数，同时指定域名
     * $this->url(/product/index, array('id=>1)); 指定参数，不指定域名，使用当前URL域名
     */
    public function url($mca, $params = array(), $domain = 'local')
    {
        if (!is_array($params)) {
            $domain = $params;
        }
        if ('local' === $domain) {
            $schema = 'http://' . $_SERVER['HTTP_HOST'];
        } else {
            $domains = F_Application::getInstance()->getConfigs('domain');
            if (!isset($domains[$domain])) {
                throw new Exception('F_View_Helper_Url->url domain notfound');
            }
            $schema = 'http://' . $domains[$domain];
        }          
        //路由 todo
        return $schema . $mca . ((is_array($params) && !empty($params))?'?'.http_build_query($params):'');
    }
}