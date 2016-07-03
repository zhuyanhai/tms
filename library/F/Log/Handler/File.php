<?php
/**
 * 文件方式记录
 */
class F_Log_Handler_File extends F_Log_Handler_Abstract
{
    /**
     * 保存
     */
	public function save()
	{
		$logMessage = $this->_format().PHP_EOL;
		$destDir = F_Log_Config::getBasePath() . date('Y'). '/' . date('m');
		if (!is_dir($destDir)) {
			mkdir($destDir, 0777, true);
		}
		$destFile = $destDir . '/' . $this->_formatterObj->args['level'] . '_' . date('Y-m-d') . '.log';
		touch($destDir);
		chmod($destDir, 0777);
		file_put_contents($destFile, $logMessage, FILE_APPEND);
	}
}
