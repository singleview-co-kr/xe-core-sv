<?php
/**
 * @class  integration_searchController
 * @author singleview(root@singleview.co.kr)
 * @brief  integration_searchController
 */
class integration_searchController extends integration_search
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}
	/**
	 * A trigger to upload inserted document to NCP cloud search
	 * @param object $obj Trigger object
	 * @return BaseObject
	 */
	public function __triggerNcpUpload($oNewDoc)
	{
		// var_dump($oNewDoc->module_srl);
		if(!$oNewDoc->document_srl) 
			return new BaseObject();

		$oModuleModel = getModel('module');
		$oConfig = $oModuleModel->getModuleConfig('integration_search');
		unset($oModuleModel);
		// var_dump($oConfig);

		$aTargetModuleSrls = [];
		if(strlen($oConfig->target_module_srl))
		{
			$aModuleSrls = explode(',', $oConfig->target_module_srl);
			foreach($aModuleSrls as $nIdx => $nModuleSrl)
				$aTargetModuleSrls[$nModuleSrl] = 1;
			unset($aModuleSrls);
		}
		// var_dump($aTargetModuleSrls);

		if($oConfig->target == 'exclude' && $aTargetModuleSrls[$oNewDoc->module_srl] == 1)
		{
			unset($aTargetModuleSrls);
			// echo __FILE__.':'.__LINE__.'<BR>';
			return new BaseObject();
		}
		elseif($oConfig->target == 'include' && $aTargetModuleSrls[$oNewDoc->module_srl] != 1)
		{
			unset($aTargetModuleSrls);
			// echo __FILE__.':'.__LINE__.'<BR>';
			return new BaseObject();
		}
		unset($aTargetModuleSrls);

		$sNcpCloudSearchApi = _XE_PATH_.'modules/integration_search/svncp.cloud_search.php';
		if(is_readable($sNcpCloudSearchApi))
			require_once($sNcpCloudSearchApi);
		else
		{
			echo 'weird error has been occured on '.__FILE__.':'.__LINE__.'<BR>';	
			exit;
		}

		$oSvNcpCloudSearch = new svNcpCloudSearch();
		$oSvNcpCloudSearch->setUserConfig(['ncp_access_key' => $oConfig->ncp_access_key,
										 'ncp_secret_key' => $oConfig->ncp_secret_key,
										 'idx_title' => $oConfig->idx_title]);
		
		$oSvNcpCloudSearch->setDomain($oConfig->domain_name);
		// $oSvNcpCloudSearch->setHttpMethod('post');
		$oNewDocInfo = new stdClass();
		$oNewDocInfo->nDocSrl = $oNewDoc->document_srl;
		$oNewDocInfo->sTitle =  str_replace(array('\'', '"', PHP_EOL), '', strip_tags($oNewDoc->title));
		$oNewDocInfo->sContent = str_replace(array('\'', '"', PHP_EOL), ' ', strip_tags($oNewDoc->content));
		$oNewDocInfo->sTags = str_replace(array('\'', '"'), '', strip_tags($oNewDoc->tags));
		$oRst = $oSvNcpCloudSearch->upsertDoc($oNewDocInfo);
		var_dump($oRst);
		// $oNcpRst->result == 'ok' if succeeded

		unset($oConfig);
		unset($oNewDocInfo);
		unset($oSvNcpCloudSearch);
		exit;
		return new BaseObject();
	}

	/**
	 * A trigger to upload inserted document to NCP cloud search
	 * @param object $obj Trigger object
	 * @return BaseObject
	 */
	public function triggerNcpUpload($oNewDoc)
	{
		// var_dump($oNewDoc->module_srl);
		if(!$oNewDoc->document_srl) 
			return new BaseObject();

		$oModuleModel = getModel('module');
		$oConfig = $oModuleModel->getModuleConfig('integration_search');
		unset($oModuleModel);
		// var_dump($oConfig);

		$aTargetModuleSrls = [];
		if(strlen($oConfig->target_module_srl))
		{
			$aModuleSrls = explode(',', $oConfig->target_module_srl);
			foreach($aModuleSrls as $nIdx => $nModuleSrl)
				$aTargetModuleSrls[$nModuleSrl] = 1;
			unset($aModuleSrls);
		}
		// var_dump($aTargetModuleSrls);

		if($oConfig->target == 'exclude' && $aTargetModuleSrls[$oNewDoc->module_srl] == 1)
		{
			unset($aTargetModuleSrls);
			// echo __FILE__.':'.__LINE__.'<BR>';
			return new BaseObject();
		}
		elseif($oConfig->target == 'include' && $aTargetModuleSrls[$oNewDoc->module_srl] != 1)
		{
			unset($aTargetModuleSrls);
			// echo __FILE__.':'.__LINE__.'<BR>';
			return new BaseObject();
		}
		unset($aTargetModuleSrls);

		$sNcpCloudSearchApi = _XE_PATH_.'modules/integration_search/svncp.cloud_search.php';
		if(is_readable($sNcpCloudSearchApi))
			require_once($sNcpCloudSearchApi);
		else
		{
			echo 'weird error has been occured on '.__FILE__.':'.__LINE__.'<BR>';	
			exit;
		}

		$oContextDbInfo = Context::getDBInfo();
		$oSourceDbInfo = new stdClass();
		$oSourceDbInfo->sPort = $oContextDbInfo->master_db['db_port'];
		$oSourceDbInfo->sHostname = $oContextDbInfo->master_db['db_hostname'];
		$oSourceDbInfo->sUserid = $oContextDbInfo->master_db['db_userid'];
		$oSourceDbInfo->sPassword = $oContextDbInfo->master_db['db_password'];
		$oSourceDbInfo->sDatabase = $oContextDbInfo->master_db['db_database'];

		$oSqlInfo = new stdClass();
		$oSqlInfo->sKeyField = 'document_srl';
		$oSqlInfo->sSqlStmt = 'SELECT `document_srl`, `title`, `content`, `tags` FROM `'.$oContextDbInfo->master_db['db_table_prefix'].'documents` WHERE `document_srl` = '.$oNewDoc->document_srl;

		$oReqInfo = new stdClass();
		$oReqInfo->oSourceDbInfo = $oSourceDbInfo;
		$oReqInfo->oSqlInfo = $oSqlInfo;

		$oSvNcpCloudSearch = new svNcpCloudSearch();
		$oSvNcpCloudSearch->setUserConfig(['ncp_access_key' => $oConfig->ncp_access_key,
										 'ncp_secret_key' => $oConfig->ncp_secret_key,
										 'idx_title' => $oConfig->idx_title]);
		$oSvNcpCloudSearch->setDomain($oConfig->domain_name);
		// $oSvNcpCloudSearch->setHttpMethod('post');
		$oRst = $oSvNcpCloudSearch->uploadDb($oReqInfo);
		var_dump($oRst);
		// $oNcpRst->result == 'ok' if succeeded
		unset($oConfig);
		unset($oSvNcpCloudSearch);
		unset($oContextDbInfo);
		unset($oReqInfo);
		unset($oSourceDbInfo);
		unset($oSqlInfo);

		exit;
		return new BaseObject();
	}
}
/* End of file integration_search.controller.php */
/* Location: ./modules/integration_search/integration_search.controller.php */