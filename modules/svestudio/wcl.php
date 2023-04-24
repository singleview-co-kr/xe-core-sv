<?php
// transmit plain text from document and comment for word cloud
// for XE compatibility
define('__XE__', TRUE);
define('_XE_PATH_', str_replace('modules/svestudio/wcl.php', '', str_replace('\\', '/', __FILE__)));

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
		'LMKLD' => 1, # let me know latest data + data: requested sync date since
		'FIN' => 2, # finish 	
		'ALD' => 3, # add latest data + data: doc_srls + com_srls
		'GMDL' => 4, # give me document list  -> data: doc_srls
		'GMCL' => 5, # give me comment list  -> data: com_srls
		'HYA' => 6, # here you are -> data: text list
		);*/
	var $_g_sPadding = "{";  //same padding as python
	var $_g_oReceivedParams = null;
	var $_g_oRespParam = null;
	var $_g_aIgonreMemberSrl = null;
	var $_g_aAllowModuleSrl = null;
/**
 * @brief
 */
	public function __construct()
	{
		$this->_getConfigFile();
//echo '<BR>'.__FILE__.':'.__lINE__.'<BR>';
//var_dump($_POST);
//echo '<BR><BR>';
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

		$sConfigPath = _XE_PATH_.'files/svestudio/wcl.config.php';
		if(is_readable($sConfigPath))
			require_once($sConfigPath);

		if(isset($aWclConfig['ignore_member_srl']))
			$this->aIgonreMemberSrl = $aWclConfig['ignore_member_srl'];
		else
			$this->aIgonreMemberSrl = [];

		if(isset($aWclConfig['allow_module_srl']))
			$this->_g_aAllowModuleSrl = $aWclConfig['allow_module_srl'];
		else
			$this->_g_aAllowModuleSrl = [];

		$oSvApiCrypt = new singleviewApiOpenSsl();
		$this->_g_oReceivedParams = $oSvApiCrypt->translateMsgCode($_POST['@v']);
		foreach( $this->_g_oReceivedParams->c as $key => $val)
			$this->_g_oReceivedParams->msg = array_search( $val, $this->_g_aMsg);

		switch( $this->_g_oReceivedParams->msg )
		{
			case 'LMKL':
				$this->_checkLatest();
				break;
			case 'GMDL':
				$this->_getDocumentListDetail();
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
	private function _getDocumentListDetail()
	{
		$aSyncList = $this->_getUpdatedDocInfo();
		if( $aSyncList['aDocInfoList'] != 'na')
		{
			$this->_g_oRespParam->a = array($this->_g_aMsg['HYA']);
			$this->_g_oRespParam->d = $aSyncList['aDocInfoList'];
		}
		else
			$this->_g_oRespParam->a = array($this->_g_aMsg['FIN']);
	}
/**
 * @brief html tag, &nbsp;, new line 제거
 */
	private function _cleanupMarkupStr($sStr)
	{
		$sStr = strip_tags(html_entity_decode($sStr));
		return trim(preg_replace('/\s\s+/', ' ', $sStr));
	}
/**
 * @brief 갱신할 새글 상세 정보 추출
 */
	private function _getUpdatedDocInfo()
	{
		# init rst arrray
		$aRst = Array('aDocInfoList'=>'na');
		
		$oMysql = new svMysqlPdo($this->_g_oDbInfo);
		$aDocInfo = [];
		foreach($this->_g_oReceivedParams->d as $nIdx => $nDocSrl)
		{
			$aRequestedDocSrl = array($nDocSrl);
			$aDocInfoToTransmit = $oMysql->executeQuery('getDocDetailBySrl', $aRequestedDocSrl);
			$aComParam = array($aDocInfoToTransmit[0]['document_srl'], $this->aIgonreMemberSrl);
			$aComInfoToTransmit = $oMysql->executeDynamicQuery('getCommentByDocSrl', $aComParam);
			unset($aComParam);
			$sTitle = $this->_cleanupMarkupStr($aDocInfoToTransmit[0]['title']);
			$sContent = $this->_cleanupMarkupStr($aDocInfoToTransmit[0]['content']);
			$sAnswer = $this->_cleanupMarkupStr($aComInfoToTransmit[0]['content']);
			$aDocInfo[] = array('document_srl' => $aDocInfoToTransmit[0]['document_srl'], 
								'module_srl' => $aDocInfoToTransmit[0]['module_srl'], 
								'title' => $sTitle, 'content' => $sContent,
								'answer' => $sAnswer, 
								'regdate' => $aDocInfoToTransmit[0]['regdate'], 
								'last_update' => $aDocInfoToTransmit[0]['last_update'] );
			unset($aComInfoToTransmit);
			unset($aDocInfoToTransmit);
		}
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
		$aSyncList = $this->_getUpdatedDocSrls();
		if( $aSyncList['aDocSrls'] != 'na' || $aSyncList['aComSrls'] != 'na' )
		{
			$this->_g_oRespParam->a = array($this->_g_aMsg['ALD']);
			$this->_g_oRespParam->d = $aSyncList;
		}
		else
			$this->_g_oRespParam->a = array($this->_g_aMsg['FIN']);
	}
/**
 * @briefw 전송할 새글 srl array 추출
 */
	private function _getUpdatedDocSrls()
	{
		# init rst arrray
		$aRst = Array('aDocSrls'=>'na','aComSrls'=>'na');
		$sBeginYyyymmddhhmmss = $this->_g_oReceivedParams->d->s_begin_date.'000000';
		$sEndYyyymmddhhmmss = $this->_g_oReceivedParams->d->s_end_date.'235959';
		$aParam = array($sBeginYyyymmddhhmmss, $sEndYyyymmddhhmmss, $this->aIgonreMemberSrl, $this->_g_aAllowModuleSrl);
		$oMysql = new svMysqlPdo($this->_g_oDbInfo);
		$aDocsToSync = $oMysql->executeDynamicQuery('getUpdatedDocSrlsByMemberModuleSrl', $aParam);
		if(count($aDocsToSync))
		{
			$aDocSrls = [];
			foreach( $aDocsToSync as $nIdx => $aVal)
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
/* End of file b2c.php */
/* Location: ./modules/svestudio/b2c.php */