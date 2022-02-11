<?php
/**
* @brief
* @author aztr0v0iz
* @developer w9721066@gmail.com
*/
class SvActionLogger
{
	var $_g_oThis = null;
/**
 * @brief 
 **/
	public function SvActionLogger($oThis, $sWatchingUri)
	{
		if( $sWatchingUri )
		{
			$nPos = strpos($_SERVER['QUERY_STRING'], $sWatchingUri);
			if( $nPos === false )
				;
			else
			{
				$this->_g_oConfig = $oThis;
				$this->_doLog();
			}
		}
		else
		{
			$this->_g_oConfig = $oThis;
			$this->_doLog();
		}
	}
/**
 * @brief 
 **/
	private function _doLog()
	{
		$sMemeberSrl = 'guest';
		$logged_info = Context::get('logged_info');
		if( $logged_info )
			$sMemeberSrl = $logged_info->member_srl;

		$sMid = Context::get('mid');
		if( !$sMid )
			$sMid = 'none';

		$sLog = date('h:i:s').'|@|'.$sMemeberSrl.'|@|'.$this->_g_oConfig->module.'|@|'.$sMid.'|@|'.$this->_g_oConfig->act.'|@|'.$_SERVER['REMOTE_ADDR'].'|@|'.$_SERVER['HTTP_USER_AGENT'].'|@|'.$_SERVER['QUERY_STRING'].PHP_EOL;
		
		$sLogFile = './files/action_log/'.date('Ymd').'.log.php';
		if( FileHandler::exists($sLogFile) )
			FileHandler::writeFile($sLogFile ,$sLog, 'a');
		else
		{
			$sLog = '<?php exit() ?>'.PHP_EOL.$sLog;
			FileHandler::writeFile($sLogFile, $sLog, 'w');
		}
	}
}