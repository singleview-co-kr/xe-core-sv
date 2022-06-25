<?php
// transmit addr info from document extra vars for geovisualiztion
// for XE compatibility
define('__XE__', TRUE);
define('_XE_PATH_', str_replace('modules/svestudio/adr.php', '', str_replace('\\', '/', __FILE__)));

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
	var $_g_sPadding = "{";  //same padding as python
	var $_g_oReceivedParams = null;
	var $_g_oRespParam = null;
	var $_g_aAllowedCollectionBase = array('date', 'document_srl');
/**
 * @brief
 */
	public function __construct()
	{
		$this->_getConfigFile();
		if(!array_key_exists('@v', $_POST))
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
		if(!$this->_g_bAllow)
			return;
		// begin - get Protocol message dictionary
		$oSvApiMsgProtocol = new singleviewMsgProtocol();
		$this->_g_aMsg = $oSvApiMsgProtocol->getMsgCode();
		unset($oSvApiMsgProtocol);
		// end - get Protocol message dictionary

		$oSvApiCrypt = new singleviewApiOpenSsl();
		$this->_g_oReceivedParams = $oSvApiCrypt->translateMsgCode($_POST['@v']);
		foreach($this->_g_oReceivedParams->c as $key => $val)
			$this->_g_oReceivedParams->msg = array_search($val, $this->_g_aMsg);

		if(!in_array($this->_g_oReceivedParams->d->s_collection_base, $this->_g_aAllowedCollectionBase))
		{
			$this->_g_oRespParam->a = array($this->_g_aMsg['ERR']);
			$this->_g_oRespParam->d = 's_collection_base is invalid';
			// encrypt transmit
			$res = $oSvApiCrypt->encryptData($this->_g_oRespParam);
			print $res;
			return;
		}

		if(is_null($this->_g_oReceivedParams->d->n_module_srl) || 
			is_null($this->_g_oReceivedParams->d->s_addr_field_title))
		{
			$this->_g_oRespParam->a = array($this->_g_aMsg['ERR']);
			$this->_g_oRespParam->d = 'n_module_srl or s_addr_field_title is null';
			// encrypt transmit
			$res = $oSvApiCrypt->encryptData($this->_g_oRespParam);
			print $res;
			return;
		}

		switch($this->_g_oReceivedParams->msg)
		{
			case 'LMKL':
				$this->_checkLatest();
				break;
			case 'GMDL':
				$this->_getAddrList();
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
	private function _getAddrList()
	{
		$aSyncList = $this->_getNewAddrInfo();
		if( $aSyncList['aDocInfoList'] != 'na')
		{
			$this->_g_oRespParam->a = array($this->_g_aMsg['HYA']);
			$this->_g_oRespParam->d = $aSyncList['aDocInfoList'];
		}
		else
			$this->_g_oRespParam->a = array($this->_g_aMsg['FIN']);
	}
/**
 * @brief 갱신할 새글 주소 정보 추출
 */
	private function _getNewAddrInfo()
	{
		# init rst arrray
		$aRst = Array('aDocInfoList'=>'na');
		$oMysql = new svMysqlPdo($this->_g_oDbInfo);
		$aDocInfo = [];
		foreach($this->_g_oReceivedParams->d->a_doc_srl as $nIdx => $nDocSrl)
		{
			$aDocInfoToTransmit = $oMysql->executeQuery('getDocDetailBySrl', array($nDocSrl));
			$aRequestedExtVarSrl = array($nDocSrl, $this->_g_oReceivedParams->d->s_addr_field_title);
			$aExtraVarsToTransmit = $oMysql->executeQuery('getExtraVarsByDocSrl', $aRequestedExtVarSrl);
			unset($aRequestedExtVarSrl);
			if($aExtraVarsToTransmit[0]['value'])
			{
				$aDocInfo[] = array('document_srl' => $aDocInfoToTransmit[0]['document_srl'], 
									'module_srl' => $aDocInfoToTransmit[0]['module_srl'], 
									'adr' => $aExtraVarsToTransmit[0]['value'],
									'regdate' => $aDocInfoToTransmit[0]['regdate']);
			}
			unset($aExtraVarsToTransmit);
			unset($aDocInfoToTransmit);
			
		}
		unset($oMysql);
		if(count($aDocInfo))
			$aRst['aDocInfoList'] = $aDocInfo;
		unset($oMysql);
		return $aRst;
	}
/**
 * @brief
 */
	private function _checkLatest()
	{
		$aSyncList = $this->_getNewAddr();
		if( $aSyncList['aDocSrls'] != 'na' || $aSyncList['aComSrls'] != 'na' )
		{
			$this->_g_oRespParam->a = array($this->_g_aMsg['ALD']);
			$this->_g_oRespParam->d = $aSyncList;
		}
		else
			$this->_g_oRespParam->a = array($this->_g_aMsg['FIN']);
	}
/**
 * @briefw 전송할 주소의 새글 srl array 추출
 */
	private function _getNewAddr()
	{
		# init rst arrray
		$aRst = Array('aDocSrls'=>'na');
		if($this->_g_oReceivedParams->d->s_collection_base == 'date')
		{
			$sBeginYyyymmddhhmmss = $this->_g_oReceivedParams->d->s_begin_date.'000000';
			$sEndYyyymmddhhmmss = $this->_g_oReceivedParams->d->s_end_date.'235959';
			$aParam = array($sBeginYyyymmddhhmmss, $sEndYyyymmddhhmmss, [$this->_g_oReceivedParams->d->n_module_srl]);
			$oMysql = new svMysqlPdo($this->_g_oDbInfo);
			$aDocsToSync = $oMysql->executeDynamicQuery('getNewDocSrlsByRegdateModuleSrl', $aParam);
		}
		elseif($this->_g_oReceivedParams->d->s_collection_base == 'document_srl')
		{
			$oMysql = new svMysqlPdo($this->_g_oDbInfo);
			$aDocsToSync = $oMysql->executeQuery('getNewDocSrlsByDocModuleSrl', array($this->_g_oReceivedParams->d->n_module_srl, $this->_g_oReceivedParams->d->n_last_doc_srl));
		}
		if(count($aDocsToSync))
		{
			$aDocSrls = [];
			foreach($aDocsToSync as $nIdx => $aVal)
				$aDocSrls[] = $aVal['document_srl'];
			$aRst['aDocSrls'] = $aDocSrls;
		}
		unset($aDocsToSync);
		unset($oMysql);
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
/* End of file adr.php */
/* Location: ./modules/svestudio/adr.php */