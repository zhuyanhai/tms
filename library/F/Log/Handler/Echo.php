<?php
/**
 * 打印记录
 */
class F_Log_Handler_Echo extends F_Log_Handler_Abstract
{
    /**
     * 打印
     */
	public function save()
	{
        //当 $_GET 参数中带有 sLogFlag 参数时，并且参数值＝echo，就会在程序结束后打印到页面上
        if (isset($_GET['sLogFlag']) && $_GET['sLogFlag'] === 'echo') {
            echo $logMessage;
        }
	}
}
