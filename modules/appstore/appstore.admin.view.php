<?php
/* Copyright (C) singleview.co.kr <http://singleview.co.kr> */
/**
 * @class  appstoreAdminView
 * @author singleview.co.kr (root@singleview.co.kr)
 * @brief module admin view class
 */
class appstoreAdminView extends appstore
{
	var $module_srl = 0;
	var $list_count = 20;
	var $page_count = 10;

	/**
	 * @brief Initialization
	 */
	function init()
	{
		// Pre-check if module_srl exists. Set module_info if exists
		$module_srl = Context::get('module_srl');
		// Create module model object
		$oModuleModel = getModel('module');
		// module_srl two come over to save the module, putting the information in advance
		if($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info)
			{
				Context::set('module_srl','');
				$this->act = 'list';
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}
		// Get a list of module categories
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		//Security
		$security = new Security();
		$security->encodeHTML('module_category..title');

		// Get a template path (page in the administrative template tpl putting together)
		$this->setTemplatePath($this->module_path.'tpl');
	}
	/**
	 * @brief default admin view
	 */
	public function dispAppstoreAdminModInstList() 
	{
		$oModuleModel = &getModel('module');
		$oArgs = new stdClass();
		$oArgs->sort_index = "module_srl";
		$oArgs->page = Context::get('page');
		$oArgs->list_count = 20;
		$oArgs->page_count = 10;
		$oArgs->s_module_category_srl = Context::get('module_category_srl');
		$oRst = executeQueryArray('appstore.getAppList', $oArgs);
		$aList = $oRst->data;
		$aList = $oModuleModel->addModuleExtraVars($aList);
		Context::set('total_count', $oRst->total_count);
		Context::set('total_page', $oRst->total_page);
		Context::set('page', $oRst->page);
		Context::set('page_navigation', $oRst->page_navigation);
		Context::set('list', $aList);
		$oModuleModel = &getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		$this->setTemplateFile('modinstlist');
	}
	/**
	 * @brief 
	 */
	public function dispAppstoreAdminInsertModInst() 
	{
		// 스킨 목록을 구해옴
		$oModuleModel = &getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);
		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);
		// 레이아웃 목록을 구해옴
		$oLayoutModel = &getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);
		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);
		
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		// Set a template file
		$this->setTemplateFile('insertmodinst');
	}

	



	/**
	 * @brief Additional settings page showing
	 * For additional settings in a service module in order to establish links with other modules peyijiim
	 */
	function dispAppstoreAdminPageAdditionSetup()
	{
		// call by reference content from other modules to come take a year in advance for putting the variable declaration
		$content = '';

		$oEditorView = getView('editor');
		$oEditorView->triggerDispEditorAdditionSetup($content);
		Context::set('setup_content', $content);
		// Set a template file
		$this->setTemplateFile('addition_setup');

		$security = new Security();
		$security->encodeHTML('module_info.');
	}

	function dispAppstoreAdminMobileContent()
	{
		if($this->module_info->page_type == 'OUTSIDE')
		{
			return $this->stop(-1, 'msg_invalid_request');
		}

		if($this->module_srl)
		{
			Context::set('module_srl',$this->module_srl);
		}

		$oPageMobile = &getMobile('page');
		$oPageMobile->module_info = $this->module_info;
		$page_type_name = strtolower($this->module_info->page_type);
		$method = '_get' . ucfirst($page_type_name) . 'Content';
		if(method_exists($oPageMobile, $method))
		{
			if($method == '_getArticleContent' && $this->module_info->is_mskin_fix == 'N')
			{
				$oModuleModel = getModel('module');
				$oPageMobile->module_info->mskin = $oModuleModel->getModuleDefaultSkin('page', 'M');
			}
			$page_content = $oPageMobile->{$method}();
		}
		else
		{
			return new BaseObject(-1, sprintf('%s method is not exists', $method));
		}

		Context::set('module_info', $this->module_info);
		Context::set('page_content', $page_content);

		$this->setTemplateFile('mcontent');
	}

	function dispAppstoreAdminMobileContentModify()
	{
		Context::set('module_info', $this->module_info);

		if ($this->module_info->page_type == 'WIDGET')
		{
			$this->_setWidgetTypeContentModify(true);
		}
		else if ($this->module_info->page_type == 'ARTICLE')
		{
			$this->_setArticleTypeContentModify(true);
		}
	}

	/**
	 * @brief Edit App Content
	 */
	function dispAppstoreAdminContentModify()
	{
		// Set the module information
		Context::set('module_info', $this->module_info);

		if ($this->module_info->page_type == 'WIDGET')
		{
			$this->_setWidgetTypeContentModify();
		}
		else if ($this->module_info->page_type == 'ARTICLE')
		{
			$this->_setArticleTypeContentModify();
		}
	}

	function _setWidgetTypeContentModify($isMobile = false)
	{
		// Setting contents
		if($isMobile)
		{
			$content = Context::get('mcontent');
			if(!$content) $content = $this->module_info->mcontent;
			$templateFile = 'page_mobile_content_modify';
		}
		else
		{
			$content = Context::get('content');
			if(!$content) $content = $this->module_info->content;
			$templateFile = 'page_content_modify';
		}

		Context::set('content', $content);
		// Convert them to teach the widget
		$oWidgetController = getController('widget');
		$content = $oWidgetController->transWidgetCode($content, true, !$isMobile);
		// $content = str_replace('$', '&#36;', $content);
		Context::set('page_content', $content);
		// Set widget list
		$oWidgetModel = getModel('widget');
		$widget_list = $oWidgetModel->getDownloadedWidgetList();
		Context::set('widget_list', $widget_list);

		//Security
		$security = new Security();
		$security->encodeHTML('widget_list..title','module_info.mid');

		// Set a template file
		$this->setTemplateFile($templateFile);
	}

	function _setArticleTypeContentModify($isMobile = false)
	{
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument(0, true);

		if($isMobile)
		{
			Context::set('isMobile', 'Y');
			$target = 'mdocument_srl';
		}
		else
		{
			Context::set('isMobile', 'N');
			$target = 'document_srl';
		}

		if($this->module_info->{$target})
		{
			$document_srl = $this->module_info->{$target};
			$oDocument->setDocument($document_srl);
			Context::set('document_srl', $document_srl);
		} 
		else if(Context::get('document_srl'))
		{
			$document_srl = Context::get('document_srl');
			$oDocument->setDocument($document_srl);
			Context::set('document_srl', $document_srl);
		}
		else
		{
			$oDocument->add('module_srl', $this->module_info->module_srl);
		}

		Context::addJsFilter($this->module_path.'tpl/filter', 'insert_article.xml');
		Context::set('oDocument', $oDocument);
		Context::set('mid', $this->module_info->mid);
		$this->setTemplateFile('article_content_modify');
	}

	/**
	 * @brief Delete page output
	 */
	function dispAppstoreAdminDelete()
	{
		$module_srl = Context::get('module_srl');
		if(!$module_srl) return $this->dispContent();

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module', 'mid');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		Context::set('module_info',$module_info);
		// Set a template file
		$this->setTemplateFile('page_delete');

		$security = new Security();
		$security->encodeHTML('module_info.');
	}

	/**
	 * @brief Rights Listing
	 */
	function dispAppstoreAdminGrantInfo()
	{
		// Common module settings page, call rights
		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('grant_list');

		$security = new Security();
		$security->encodeHTML('module_info.');
	}

	/**
	 * Display skin setting page
	 */
	function dispAppstoreAdminSkinInfo()
	{
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);

		$this->setTemplateFile('skin_info');
	}

	/**
	 * Display mobile skin setting page
	 */
	function dispAppstoreAdminMobileSkinInfo()
	{
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);

		$this->setTemplateFile('skin_info');
	}
}
/* End of file appstore.class.php */
/* Location: ./modules/appstore/appstore.class.php */
