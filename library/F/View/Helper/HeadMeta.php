<?php
/**
 * 页面中使用 meta 标签加载
 *
 * @author allen <allen@yuorngcorp.com>
 * @package F_View
 */
final class F_View_Helper_HeadMeta
{
    /**
     * meta 数据数组
     * 
     * @var array 
     */
    private $_metaDatas = array();
            
    public function __construct()
    {
        //empty
    }
    
    /**
     * 设置 html meta 标签
     * 
     * @param string $content
     * @param string $metaKeyValue
     * @param string $metaKeyName
     * @return \F_View_Helper_HeadMeta
     */
    public function headMeta($content = null, $keyValue = null, $keyName = 'name')
    {
        if (!is_null($content) && !is_null($keyValue)) {
            if (isset($this->_metaDatas[$keyName])) {
                $this->_metaDatas[$keyName]['content'] .= $content; 
            } else {
                $this->_metaDatas[$keyName] = array(
                    'keyName'  => $keyName,
                    'keyValue' => $keyValue,
                    'content'  => $content,
                );
            }
        }
        return $this;
    }
    
    /**
     * 输出时
     */
    public function __tostring()
    {
        $metaStr = '';
        if (count($this->_metaDatas) > 0) {
            foreach ($this->_metaDatas as $meta) {
                if (!is_numeric($meta['content']) && empty($meta['content'])) {
                    $content = '';
                } else {
                    $content = "content=\"{$meta['content']}\"";
                }
                $metaStr .= "<meta {$meta['keyName']}=\"{$meta['keyValue']}\" {$content} />" . PHP_EOL;
            }
        }
        
        return $metaStr;
    }
}