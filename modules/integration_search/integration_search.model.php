<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/**
 * The model class of integration module
 *
 * @author XEHub (developers@xpressengine.com)
 */
class integration_searchModel extends module
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}
	public function getModuleConfig()
	{
		$oModuleModel = getModel('module');
		$oConfig = $oModuleModel->getModuleConfig('integration_search');
		unset($oModuleModel);
		if(!$oConfig) 
			$oConfig = new stdClass;
		if(!$oConfig->skin) 
			$oConfig->skin = 'default';
		if($oConfig->ncp_allowed_ip)
		{
			$aAllowedIp = explode(',', $oConfig->ncp_allowed_ip);
			$oConfig->ncp_allowed_ip = [];
			foreach($aAllowedIp as $nIdx => $sAllowedIp)
				$oConfig->ncp_allowed_ip[$sAllowedIp] = 1;
			unset($aAllowedIp);
		}
		return $oConfig;
	}
	/**
	 * NCP cloud Search 
	 *
	 * @param string $sTarget choose target. exclude or include for $module_srls_list
	 * @param string $aModuleSrls module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return BaseObject output document list
	 */
	public function getNcpCloudSearch($sTarget, $aModuleSrls, $search_keyword, $page=1, $list_count = 20)
	{
		$oModuleModel = getModel('module');
		$oConfig = $oModuleModel->getModuleConfig('integration_search');
		unset($oModuleModel);
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
										 'idx_title' => $oConfig->idx_title,
										 'display_cnt' => $list_count]);
		$oSvNcpCloudSearch->setDomain($oConfig->domain_name);
		$nStartPosition = ($page - 1) * $list_count + 1;
		$oSvNcpCloudSearch->setStartPosition($nStartPosition);
		$oSvNcpCloudSearch->setQuery($search_keyword);
		$oResponse = $oSvNcpCloudSearch->getSearchList($oConfig->use_cache == 'Y' ? true : false);
		unset($oSvNcpCloudSearch);
		unset($oConfig);
		
		$nTotalPage = (int)ceil($oResponse->nTotalCount / $oResponse->nDisplay);
		$oRst = new BaseObject();
		$oRst->total_count = $oResponse->nTotalCount;
		$oRst->total_page = $nTotalPage;
		$oRst->page = $page;
		// $total_count, $total_page, $cur_page, $page_count = 10)
		$oRst->page_navigation = new PageHandler($oResponse->nTotalCount, $nTotalPage, $page, $list_count);
		// object(PageHandler)#218 (7) { ["total_count"]=> int(313) ["total_page"]=> int(63) ["cur_page"]=> int(1) ["page_count"]=> int(10) ["first_page"]=> int(1) ["last_page"]=> int(63) ["point"]=> int(0) }
// var_dump($oResponse->aItems);
// echo '<BR>';
		$oRst->data = [];
		if(!count($oResponse->aItems))
		{
			unset($oResponse);
			return $oRst;
		}
	
		$oDocumentModel = getModel('document');
		$aCandidateDoc = $oDocumentModel->getDocuments($oResponse->aItems);
		unset($oDocumentModel);
		
		if(!count($aCandidateDoc))
		{
			unset($oResponse);
			return $oRst;
		}
			
		$aRankedDoc = [];
		foreach($oResponse->aItems as $nIdx=>$nDocSrl)
			$aRankedDoc[$nDocSrl] = $aCandidateDoc[$nDocSrl];
		unset($aCandidateDoc);
		$aTargetModuleSrls = [];
		foreach($aModuleSrls as $nIdx => $nModuleSrl)
			$aTargetModuleSrls[$nModuleSrl] = 1;

		foreach($aRankedDoc as $nIdx => $oDoc)
		{
//echo __FILE__.':'.__LINE__.'<BR>';
//var_dump($oDoc->variables['document_srl'],$oDoc->variables['module_srl'], $oDoc->variables['title']);
//echo '<BR>';
			if($oDoc->variables['module_srl'] == 0) // exclude 'trash'
				continue;
			if($sTarget == 'exclude' && $aTargetModuleSrls[$oDoc->variables['module_srl']] != 1) // excluding module specified
				$oRst->data[$nIdx] = $oDoc;
			elseif($aTargetModuleSrls[$oDoc->variables['module_srl']] == 1) // including module specified
				$oRst->data[$nIdx] = $oDoc;
		}
//var_dump($oRst);
//exit;
		unset($oResponse);
		unset($aRankedDoc);
		unset($aTargetModuleSrls);
		return $oRst;
	}
	/**
	 * Search documents
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_target Target
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return BaseObject output document list
	 */
	public function getDocuments($target, $module_srls_list, $search_target, $search_keyword, $page=1, $list_count = 20)
	{
		if(is_array($module_srls_list)) $module_srls_list = implode(',',$module_srls_list);

		$args = new stdClass();
		if($target == 'exclude')
		{
			$module_srls_list .= ',0'; // exclude 'trash'
			if ($module_srls_list[0] == ',') $module_srls_list = substr($module_srls_list, 1);
			$args->exclude_module_srl = $module_srls_list;
		}
		else
		{
			$args->module_srl = $module_srls_list;
			$args->exclude_module_srl = '0'; // exclude 'trash'
		}

		$args->page = $page;
		$args->list_count = $list_count;
		$args->page_count = 10;
		$args->search_target = $search_target;
		$args->search_keyword = $search_keyword;
		$args->sort_index = 'list_order';
		$args->order_type = 'asc';
		$args->statusList = array('PUBLIC');
		if(!$args->module_srl) unset($args->module_srl);
		// Get a list of documents
		$oDocumentModel = getModel('document');

		return $oDocumentModel->getDocumentList($args);
	}
	/**
	 * Search comment
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return BaseObject output comment list
	 */
	public function getComments($target, $module_srls_list, $search_keyword, $page=1, $list_count = 20)
	{
		$args = new stdClass();

		if(is_array($module_srls_list))
		{
			if (count($module_srls_list) > 0) $module_srls = implode(',',$module_srls_list);
		}
		else
		{
			if($module_srls_list)
			{
				$module_srls = $module_srls_list;
			}
		}
		if($target == 'exclude') $args->exclude_module_srl = $module_srls;
		else $args->module_srl = $module_srls;

		$args->page = $page;
		$args->list_count = $list_count;
		$args->page_count = 10;
		$args->search_target = 'content';
		$args->search_keyword = $search_keyword;
		$args->sort_index = 'list_order';
		$args->order_type = 'asc';
		// Get a list of documents
		$oCommentModel = getModel('comment');
		$output = $oCommentModel->getTotalCommentList($args);
		if(!$output->toBool()|| !$output->data) return $output;
		return $output;
	}
	/**
	 * Search for attachments. call function _getFiles().
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return BaseObject
	 */
	public function getFiles($target, $module_srls_list, $search_keyword, $page=1, $list_count = 20)
	{
		return $this->_getFiles($target, $module_srls_list, $search_keyword, $page, $list_count, 'N');
	}

	/**
	 * Search file
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 * @param string $direct_download Y or N
	 *
	 * @return BaseObject output file list
	 */
	private function _getFiles($target, $module_srls_list, $search_keyword, $page, $list_count, $direct_download = 'Y')
	{
		$args = new stdClass();

		if(is_array($module_srls_list)) $module_srls = implode(',',$module_srls_list);
		else $module_srls = $module_srls_list;
		if($target == 'exclude') $args->exclude_module_srl = $module_srls;
		else $args->module_srl = $module_srls;
		$args->page = $page;
		$args->list_count = $list_count;
		$args->page_count = 10;
		$args->search_target = 'filename';
		$args->search_keyword = $search_keyword;
		$args->sort_index = 'files.file_srl';
		$args->order_type = 'desc';
		$args->isvalid = 'Y';
		$args->direct_download = $direct_download=='Y'?'Y':'N';
		// Get a list of documents
		$oFileAdminModel = getAdminModel('file');
		$output = $oFileAdminModel->getFileList($args);
		if(!$output->toBool() || !$output->data) return $output;

		$list = array();
		foreach($output->data as $key => $val)
		{
			$obj = new stdClass;
			$obj->filename = $val->source_filename;
			$obj->download_count = $val->download_count;
			if(substr($val->download_url,0,2)=='./') $val->download_url = substr($val->download_url,2);
			$obj->download_url = Context::getRequestUri().$val->download_url;
			$obj->target_srl = $val->upload_target_srl;
			$obj->file_size = $val->file_size;
			// Images
			if(preg_match('/\.(jpg|jpeg|gif|png)$/i', $val->source_filename))
			{
				$obj->type = 'image';

				$thumbnail_path = sprintf('files/thumbnails/%s',getNumberingPath($val->file_srl, 3));
				if(!is_dir($thumbnail_path)) FileHandler::makeDir($thumbnail_path);
				$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, 120, 120, 'crop');
				$thumbnail_url  = Context::getRequestUri().$thumbnail_file;
				if(!file_exists($thumbnail_file)) FileHandler::createImageFile($val->uploaded_filename, $thumbnail_file, 120, 120, 'jpg', 'crop');
				$obj->src = sprintf('<img src="%s" alt="%s" width="%d" height="%d" />', $thumbnail_url, htmlspecialchars($obj->filename, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), 120, 120);
			}
			else
			{
				$obj->type = 'binary';
				$obj->src = '';
			}

			$list[] = $obj;
			$target_list[] = $val->upload_target_srl;
		}
		$output->data = $list;

		$oDocumentModel = getModel('document');
		$document_list = $oDocumentModel->getDocuments($target_list);
		if($document_list) foreach($document_list as $key => $val)
		{
			foreach($output->data as $k => $v)
			{
				if($v->target_srl== $val->document_srl)
				{
					$output->data[$k]->url = $val->getPermanentUrl();
					$output->data[$k]->regdate = $val->getRegdate("Y-m-d H:i");
					$output->data[$k]->nick_name = $val->getNickName();
				}
			}
		}

		$oCommentModel = getModel('comment');
		$comment_list = $oCommentModel->getComments($target_list);
		if($comment_list) foreach($comment_list as $key => $val)
		{
			foreach($output->data as $k => $v)
			{
				if($v->target_srl== $val->comment_srl)
				{
					$output->data[$k]->url = $val->getPermanentUrl();
					$output->data[$k]->regdate = $val->getRegdate("Y-m-d H:i");
					$output->data[$k]->nick_name = $val->getNickName();
				}
			}
		}

		return $output;
	}
}
/* End of file integration_search.model.php */
/* Location: ./modules/integration_search/integration_search.model.php */
