<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/**
 * The admin view class of the integration_search module
 *
 * @author XEHub (developers@xpressengine.com)
 */
class integration_searchAdminController extends integration_search
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Save Settings
	 *
	 * @return mixed
	 */
	function procIntegration_searchAdminInsertConfig()
	{
		// Get configurations (using module model object)
		$oModuleModel = getModel('module');
		$oConfig = $oModuleModel->getModuleConfig('integration_search');
		unset($oModuleModel);
// var_Dump($args = Context::getRequestVars());
		$oArgs = new stdClass;
		if(!Context::get('use_ncp_cloud_search'))
			$oArgs->use_ncp_cloud_search = '';
		else
			$oArgs->use_ncp_cloud_search = Context::get('use_ncp_cloud_search');

		if(!Context::get('use_cache'))
			$oArgs->use_cache = '';
		else
			$oArgs->use_cache = Context::get('use_cache');

		$oArgs->ncp_allowed_ip = Context::get('ncp_allowed_ip');
		$oArgs->ncp_access_key = Context::get('ncp_access_key');
		$oArgs->ncp_secret_key = Context::get('ncp_secret_key');
		$oArgs->domain_name = Context::get('domain_name');
		$oArgs->idx_title = Context::get('idx_title');
		$oArgs->skin = Context::get('skin');
		$oArgs->layout_srl = Context::get('layout_srl');
		$oArgs->mlayout_srl = Context::get('mlayout_srl');
		$oArgs->mskin = Context::get('mskin');
		$oArgs->target = Context::get('target');
		$oArgs->target_module_srl = Context::get('target_module_srl');
		if(!$oArgs->target_module_srl) $oArgs->target_module_srl = '';
		$oArgs->skin_vars = $oConfig->skin_vars;

		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('integration_search',$oArgs);
		unset($oArgs);
		unset($oModuleController);
// exit;
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispIntegration_searchAdminContent');
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Save the skin information
	 *
	 * @return mixed
	 */
	function procIntegration_searchAdminInsertSkin()
	{
		// Get configurations (using module model object)
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('integration_search');

		$args = new stdClass;
		$args->skin = $config->skin;
		$args->target_module_srl = $config->target_module_srl;
		// Get skin information (to check extra_vars)
		$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $config->skin);
		// Check received variables (delete the basic variables such as mo, act, module_srl, page)
		$obj = Context::getRequestVars();
		unset($obj->act);
		unset($obj->module_srl);
		unset($obj->page);
		// Separately handle if the extra_vars is an image type in the original skin_info
		if($skin_info->extra_vars)
		{
			foreach($skin_info->extra_vars as $vars)
			{
				if($vars->type!='image') continue;

				$image_obj = $obj->{$vars->name};
				// Get a variable on a request to delete
				$del_var = $obj->{"del_".$vars->name};
				unset($obj->{"del_".$vars->name});
				if($del_var == 'Y')
				{
					FileHandler::removeFile($module_info->{$vars->name});
					continue;
				}
				// Use the previous data if not uploaded
				if(!$image_obj['tmp_name'])
				{
					$obj->{$vars->name} = $module_info->{$vars->name};
					continue;
				}
				// Ignore if the file is not successfully uploaded, and check uploaded file
				if(!is_uploaded_file($image_obj['tmp_name']) || !checkUploadedFile($image_obj['tmp_name']))
				{
					unset($obj->{$vars->name});
					continue;
				}
				// Ignore if the file is not an image
				if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name']))
				{
					unset($obj->{$vars->name});
					continue;
				}
				// Upload the file to a path
				$path = sprintf("./files/attach/images/%s/", $module_srl);
				// Create a directory
				if(!FileHandler::makeDir($path)) return false;

				$filename = $path.$image_obj['name'];
				// Move the file
				if(!move_uploaded_file($image_obj['tmp_name'], $filename))
				{
					unset($obj->{$vars->name});
					continue;
				}
				// Change a variable
				unset($obj->{$vars->name});
				$obj->{$vars->name} = $filename;
			}
		}
		// Serialize and save 
		$args->skin_vars = serialize($obj);

		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('integration_search',$args);

		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispIntegration_searchAdminSkinInfo');
		return $this->setRedirectUrl($returnUrl, $output);
	}
	/**
	 * upsert all allowed docs into NCP cloud search
	 *
	 * @return mixed
	 */
	public function procIntegration_searchAdminUploadDbAll()
	{
//echo __FILE__.':'.__LINE__.'<BR>';
		$oModuleModel = getModel('module');
		$oConfig = $oModuleModel->getModuleConfig('integration_search');
		unset($oModuleModel);
		var_dump($oConfig);

		// $aTargetModuleSrls = [];
		// if(strlen($oConfig->target_module_srl))
		// {
		// 	$aModuleSrls = explode(',', $oConfig->target_module_srl);
		// 	foreach($aModuleSrls as $nIdx => $nModuleSrl)
		// 		$aTargetModuleSrls[$nModuleSrl] = 1;
		// 	unset($aModuleSrls);
		// }
		// var_dump($aTargetModuleSrls);
		
		$sWhereClause = null;
		if(strlen($oConfig->target_module_srl))
		{
			$sWhereClause = ' WHERE `module_srl`';
			if($oConfig->target == 'exclude')
				$sWhereClause .= ' NOT'; // IN ('.$oConfig->target_module_srl.')';
			// elseif($oConfig->target == 'include')
			$sWhereClause .= ' IN ('.$oConfig->target_module_srl.')';
		}

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
		$oSqlInfo->sSqlStmt = 'SELECT `document_srl`, `title`, `content`, `tags` FROM `'.$oContextDbInfo->master_db['db_table_prefix'].'documents`'.$sWhereClause;

		$oReqInfo = new stdClass();
		$oReqInfo->oSourceDbInfo = $oSourceDbInfo;
		$oReqInfo->oSqlInfo = $oSqlInfo;

		//var_dump($oReqInfo->oSqlInfo);
		//echo '<BR>';
		//exit;

		$oSvNcpCloudSearch = new svNcpCloudSearch();
		$oSvNcpCloudSearch->setUserConfig(['ncp_access_key' => $oConfig->ncp_access_key,
										 'ncp_secret_key' => $oConfig->ncp_secret_key,
										 'idx_title' => $oConfig->idx_title]);
		$oSvNcpCloudSearch->setDomain($oConfig->domain_name);
		$oRst = $oSvNcpCloudSearch->uploadDb($oReqInfo);
		//var_dump($oRst);
		//echo '<BR>';
		unset($oConfig);
		unset($oSvNcpCloudSearch);
		unset($oContextDbInfo);
		unset($oReqInfo);
		unset($oSourceDbInfo);
		unset($oSqlInfo);
//exit;		
	}
	
}
/* End of file integration_search.admin.controller.php */
/* Location: ./modules/integration_search/integration_search.admin.controller.php */
