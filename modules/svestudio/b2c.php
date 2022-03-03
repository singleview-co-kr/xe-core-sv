<?php
// for XE compatibility
define('__XE__', TRUE);
define('_XE_PATH_', str_replace('modules/svestudio/b2c.php', '', str_replace('\\', '/', __FILE__)));

require_once( _XE_PATH_.'modules/svestudio/sv_classes/mysql_pdo.php');
require_once( _XE_PATH_.'modules/svestudio/sv_classes/svapi_crypt.php');
require_once( _XE_PATH_.'modules/svestudio/sv_classes/svapi_msg_protocol.php');
$oSvApi = new singleviewApi();
$oSvApi->procTask();

class singleviewApi
{
	var $_g_bAllow=false;
	var $_g_oDbInfo=null;
	var $_g_aMsg=array();
	/*var $_g_aMsg=array(
		'OK' => 1, # OK
		'FIN' => 2, # finish 
		'MIHY' => 3, # may i help you?
		'LMKL' => 4, # let me know new data
		'IWSY' => 5, # I will send you
		'ALD' => 6, # add latest data
		'MTG' => 7, # more to go
		'IHND' => 8, # i have new data
		'IWWFY' => 9, # i will wait for you
		'IHNI' => 10, # i have no idea
		'RRC' => 11, # remaining record count
		'PUP' => 12, # Plz Update Period
		'LMKP' => 13, # Let me know Period
		'WLYK' => 14, # will Let you know
		);*/
	var $_g_sPadding = "{";  //same padding as python
	var $_g_oReceivedParams = null;
	var $_g_oRespParam = null;
/**
 * @brief
 */
	public function __construct()
	{
		$this->_getConfigFile();
		if( !array_key_exists( '@v', $_POST) )
			return;

		$this->_g_bAllow = true;
		$this->_g_oRespParam = new stdClass();
		$this->_g_oRespParam->a = null;
		$this->_g_oRespParam->d = null;
	}
/**
 * @brief
 */
	public function procTask()
	{
		if( !$this->_g_bAllow )
			return;
		// begin - get Protocol message dictionary
		$oSvApiMsgProtocol = new singleviewMsgProtocol();
		$this->_g_aMsg = $oSvApiMsgProtocol->getMsgCode();
		unset($oSvApiMsgProtocol);
		// end - get Protocol message dictionary

		$oSvApiCrypt = new singleviewApiOpenSsl();

		$this->_g_oReceivedParams = $oSvApiCrypt->translateMsgCode($_POST['@v']);
		foreach( $this->_g_oReceivedParams->c as $key => $val)
			$this->_g_oReceivedParams->msg = array_search( $val, $this->_g_aMsg);

		switch( $this->_g_oReceivedParams->msg )
		{
			case 'MIHY':
				$this->_checkAddNew();
				break;
			case 'IWSY':
				$this->_checkStatus();
				break;
			case 'PUP':
				$this->_checkReplace();
				break;
			case 'WLYK':
				$this->_deletePeriod();
				break;
			case 'ALD':
				$this->_addLatestData();
				break;
			default:
				$this->_g_oRespParam->a = array($this->_g_aMsg['FIN']);
		}
		// encrypt transmit
		$res = $oSvApiCrypt->encryptData($this->_g_oRespParam);
		print $res;
	}
/**
 * @brief
 */
	private function _addLatestData()
	{
		$oMysql = new svMysqlPdo($this->_g_oDbInfo);
		array_shift($this->_g_oReceivedParams->d); // field 정보가 기록된 헤더 레코드를 제거
		foreach( $this->_g_oReceivedParams->d as $key=>$aVal )
		{
			$aVal[26] = date('YmdHis'); // set XE compatible registration date time 
			$oMysql->executeQuery('insertGrossCompiledLog', $aVal );
		}
	}
/**
 * @brief
 */
	private function _checkStatus()
	{
		if( $this->_g_oReceivedParams->msg == 'IWSY' )
			$nRespCode = $this->_g_aMsg['IWWFY'];
		else
			$nRespCode = $this->_g_aMsg['FIN'];
		$this->_g_oRespParam->a = array($nRespCode);
	}
/**
 * @brief
 */
	private function _checkAddNew()
	{
		$aDateRange = $this->_getTouchDateRange();
		if( $aDateRange['start_date'] != 'na' || $aDateRange['end_date'] != 'na' )
		{
			$this->_g_oRespParam->a = array($this->_g_aMsg['LMKL']);
			$this->_g_oRespParam->d = $aDateRange;
		}
		else
			$this->_g_oRespParam->a = array($this->_g_aMsg['FIN']);
	}
/**
 * @brief
 */
	private function _checkReplace()
	{
		$this->_g_oRespParam->a = array($this->_g_aMsg['LMKP']);
	}
/**
 * @brief
 */
	private function _deletePeriod()
	{
		$sDataMonth = $this->_g_oReceivedParams->d;
		if( !preg_match("/^[0-9]{4}(0[1-9]|1[0-2])$/", $sDataMonth) )
		{
			$this->_g_oRespParam->a = array($this->_g_aMsg['IHNI']);
			return;
		}

		$oMysql = new svMysqlPdo($this->_g_oDbInfo);
		$aYyyymm = array($sDataMonth);
		$oMysql->executeQuery('deleteCompiledLogByPeriod', $aYyyymm );
		$this->_g_oRespParam->a = array($this->_g_aMsg['IWWFY']);
		return;
	}
/**
 * @brief
 */
	private function _getTouchDateRange()
	{
		# init rst arrray
		$aRst = Array('start_date'=>'na','end_date'=>'na');

		# define last date of process
		// $aLastDate = Array();

		$oMysql = new svMysqlPdo($this->_g_oDbInfo);
		$aDateRange = $oMysql->executeQuery('getGrossCompiledOldestLatestDay');
		$sYesterday = date('Ymd', strtotime('yesterday'));
		if( isset( $aDateRange[0]['maxdate'] ) ) //'maxdate'
		{
			$datetime = new DateTime($aDateRange[0]['maxdate']);
			$datetime->modify('+1 day');
			$sStartDate = $datetime->format('Ymd');
			if( (int)$sStartDate <= (int)$sYesterday )
				$aRst['start_date'] = $sStartDate;
		}
		else
			$aRst['end_date'] = $sYesterday;
		return $aRst;
	}
/**
 * Get config file
 * @retrun string The path of the config file that contains database settings
 */
	private function _getConfigFile()
	{
		$config_file = _XE_PATH_.'files/config/db.config.php';
		if(is_readable($config_file))
			include($config_file);
		$this->_g_oDbInfo = $db_info;
	}
}
/* End of file b2c.php */
/* Location: ./modules/svestudio/b2c.php */