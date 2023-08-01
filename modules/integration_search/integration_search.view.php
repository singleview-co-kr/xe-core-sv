<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/**
 * The view class of the integration_search module
 *
 * @author XEHub (developers@xpressengine.com)
 */
class integration_searchView extends integration_search
{
	/**
	 * Target mid
	 * @var array target mid
	 */
	var $target_mid = array();
	/**
	 * Skin
	 * @var string skin name
	 */
	var $skin = 'default';

	protected $_g_oConfig = null;

	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
		// Check permissions
		if(!$this->grant->access) return new BaseObject(-1,'msg_not_permitted');
		
		$oISModel = getModel('integration_search');
		$this->_g_oConfig = $oISModel->getModuleConfig();

		$template_path = sprintf('%sskins/%s', $this->module_path, $this->_g_oConfig->skin);
		// Template path
		$this->setTemplatePath($template_path);
	}

	/**
	 * Search Result
	 *
	 * @return BaseObject
	 */
	function IS()
	{
		$skin_vars = ($this->_g_oConfig->skin_vars) ? unserialize($this->_g_oConfig->skin_vars) : new stdClass;
		Context::set('module_info', $skin_vars);

		$target = $this->_g_oConfig->target;
		if(!$target) 
			$target = 'include';

		if(empty($this->_g_oConfig->target_module_srl))
			$module_srl_list = array();
		else
			$module_srl_list = explode(',',$this->_g_oConfig->target_module_srl);
		
		// https://github.com/xpressengine/xe-core/issues/1522
		// 검색 대상을 지정하지 않았을 때 검색 제한
		if($target === 'include' && !count($module_srl_list))
		{
			$oMessageObject = ModuleHandler::getModuleInstance('message');
			$oMessageObject->setError(-1);
			$oMessageObject->setMessage('msg_not_enabled');
			$oMessageObject->dispMessage();
			$this->setTemplatePath($oMessageObject->getTemplatePath());
			$this->setTemplateFile($oMessageObject->getTemplateFile());
			return;
		}

		// Set a variable for search keyword
		$is_keyword = Context::get('is_keyword');
		// Set page variables
		$page = (int)Context::get('page');
		if(!$page) $page = 1;
		// Search by search tab
		$where = Context::get('where');
		// Create integration search model object
		if($is_keyword)
		{
			$bNcpExecuted = false;
			$oIS = getModel('integration_search');
			switch($where)
			{
				case 'document' :
					if($this->_g_oConfig->use_ncp_cloud_search == 'Y')
					{
						if(!$this->_g_oConfig->ncp_allowed_ip || $this->_g_oConfig->ncp_allowed_ip[$_SERVER['REMOTE_ADDR']] == 1)
						{
							$output = $oIS->getNcpCloudSearch($target, $module_srl_list, $is_keyword, $page, 10);
							$bNcpExecuted = true;
						}
					}
					if(!$bNcpExecuted)
					{
						$search_target = Context::get('search_target');
						if(!in_array($search_target, array('title','content','title_content','tag'))) $search_target = 'title';
						Context::set('search_target', $search_target);
						$output = $oIS->getDocuments($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
					}
					Context::set('output', $output);
					Context::set('bNcpExecuted', $bNcpExecuted);
					$this->setTemplateFile("document", $page);
					break;
				case 'comment' :
					$output = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("comment", $page);
					break;
				case 'file' :
					$output = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 20);
					Context::set('output', $output);
					$this->setTemplateFile("file", $page);
					break;
				default :
					if($this->_g_oConfig->use_ncp_cloud_search == 'Y' )
					{
						if(!$this->_g_oConfig->ncp_allowed_ip || $this->_g_oConfig->ncp_allowed_ip[$_SERVER['REMOTE_ADDR']] == 1)
						{
							$output['document'] = $oIS->getNcpCloudSearch($target, $module_srl_list, $is_keyword, $page, 5);
							$bNcpExecuted = true;
						}
					}
					if(!$bNcpExecuted)
						$output['document'] = $oIS->getDocuments($target, $module_srl_list, 'title', $is_keyword, $page, 5);
					$output['comment'] = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 5);
					$output['file'] = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 5);
					Context::set('search_result', $output);
					Context::set('search_target', 'title');
					Context::set('bNcpExecuted', $bNcpExecuted);
					$this->setTemplateFile("index", $page);
					break;
			}
		}
		else
		{
			$this->setTemplateFile("no_keywords");
		}

		$security = new Security();
		$security->encodeHTML('is_keyword', 'search_target', 'where', 'page');
	}
}
/* End of file integration_search.view.php */
/* Location: ./modules/integration_search/integration_search.view.php */
