<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svdocsAdminView
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsAdminView
**/ 
class svdocsAdminView extends svdocs 
{
/**
 * @brief initialization
 **/
	public function init()
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
 * @brief Delete svdocs output
 */
	public function dispSvdocsAdminDelete()
	{
		$module_srl = Context::get('module_srl');
		if(!$module_srl) 
			return $this->dispContent();

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module', 'mid');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		Context::set('module_info',$module_info);
		// Set a template file
		$this->setTemplateFile('svdocs_delete');

		$security = new Security();
		$security->encodeHTML('module_info.');
	}
/**
 * @brief display applicants list per each doc
 **/
	public function dispSvdocsAdminApplicantsList() 
	{
		$args->module_srl = Context::get('module_srl');
		$args->page = Context::get('page');
		$search_target_list = array('s_applicant_name');
		$search_target = Context::get('search_target');
		$search_keyword = Context::get('search_keyword');
		if(in_array($search_target,$search_target_list) && $search_keyword) $args->{$search_target} = $search_keyword;
		$output = executeQueryArray('svdocs.getAdminSvdocsByModule', $args);
		
		$oMemberModel = &getModel('member');
		$columnList = array('member_srl', 'user_id', 'email_address', 'user_name', 'nick_name', 'regdate');
		foreach( $output->data as $key => $val )
		{
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($val->member_srl, 0, $columnList);
			$output->data[$key]->member_srl = $member_info->member_srl;
			$output->data[$key]->user_id = $member_info->user_id;
			$to_time = strtotime( $val->datetimestamp_entry );
			$from_time = strtotime( $val->datetimestamp_final );
			$output->data[$key]->duration_sec = round(abs($to_time - $from_time), 1 );
			unset( $output->data[$key]->datetimestamp_entry );
			unset( $output->data[$key]->datetimestamp_final );
		}

		/*$oModuleAdminModel = getAdminModel('module');
		$tabChoice = array('tab1'=>1, 'tab3'=>1);
		$selected_manage_content = $oModuleAdminModel->getSelectedManageHTML($this->xml_info->grant, $tabChoice, $this->module_path);
		Context::set('selected_manage_content', $selected_manage_content);*/

		Context::set('svdocs_list', $output->data );
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('applicant_list');
	}
/**
 * @brief display applicants insert form
 **/
	function dispSvdocsAdminApplicantInsert() 
	{
		$this->setTemplateFile('applicant_insert');
	}
/**
 * @brief display applicants doc detail
 **/
	public function dispSvdocsAdminDocDetail() 
	{
		$nModuleSrl = Context::get('module_srl');
		if(!$nModuleSrl) 
			return new BaseObject(-1, 'msg_invalid_module_srl');

		$nDocSrl = Context::get('doc_srl');
		if(!$nDocSrl) 
			return new BaseObject(-1, 'msg_invalid_doc_srl');
		
		$args->module_srl = $nModuleSrl;
		$args->doc_srl = $nDocSrl;
		$output = executeQuery('svdocs.getSvdocsAdminDocDetail', $args);
		
		$oMemberModel = &getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($output->data->member_srl, 0, $columnList);
		$output->data->user_id = $member_info->user_id ? $member_info->user_id : '탈퇴회원';
		$output->data->privacy_collection = $output->data->privacy_collection ? 'agree' : 'disagree';
		$output->data->privacy_sharing = $output->data->privacy_sharing ? 'agree' : 'disagree';
		Context::set('svdocs_detail', $output->data );
		
		$oSvdocsAdminModel = getAdminModel('svdocs');
		$aExtraVars = $oSvdocsAdminModel->getDocExtraVars($nModuleSrl, $nDocSrl);
		Context::set('svdocs_extra_vars', $aExtraVars );
		$this->setTemplateFile('applicant_detail');
	}
/**
 * @brief display svdocs list
 **/
	public function dispSvdocsAdminIndex() 
	{
		$args = new stdClass();
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 40;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');

		$search_target_list = array('s_mid','s_browser_title');
		$search_target = Context::get('search_target');
		$search_keyword = Context::get('search_keyword');
		if(in_array($search_target,$search_target_list) && $search_keyword) $args->{$search_target} = $search_keyword;

		$output = executeQuery('svdocs.getSvdocsModuleList', $args);

		$oModuleModel = getModel('module');
		$page_list = $oModuleModel->addModuleExtraVars($output->data);
		moduleModel::syncModuleToSite($page_list);

		$oModuleAdminModel = getAdminModel('module'); /* @var $oModuleAdminModel moduleAdminModel */

		$tabChoice = array('tab1'=>1, 'tab3'=>1);
		$selected_manage_content = $oModuleAdminModel->getSelectedManageHTML($this->xml_info->grant, $tabChoice, $this->module_path);
		Context::set('selected_manage_content', $selected_manage_content);

		// To write to a template context:: set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		// Set a template file
		$this->setTemplateFile('index');
	}
/**
* @brief display the selected promotion admin information
**/
	public function dispSvdocsAdminInsertModInst() 
	{
		$this->dispSvdocsAdminInfo();
	}
/**
 * @brief Information output of the selected page
 */
	public function dispSvdocsAdminInfo()
	{
		$nModuleSrl = Context::get('module_srl');
		$oSvdocsAdminModel = &getAdminModel('svdocs');
		$module_info = $oSvdocsAdminModel->getModuleConfig($nModuleSrl);
		if( $module_info->svdocs_unique_field )
			$module_info->svdocs_unique_field = unserialize( $module_info->svdocs_unique_field ); 

		// If the layout is destined to add layout information haejum (layout_title, layout)
		if($module_info->layout_srl)
		{
			$oLayoutModel = getModel('layout');
			$layout_info = $oLayoutModel->getLayout($module_info->layout_srl);
			$module_info->layout = $layout_info->layout;
			$module_info->layout_title = $layout_info->layout_title;
		}
		// Get a layout list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// Set a template file
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		if( getClass('svauth') )
		{
			$oSvauthAdminModel = &getAdminModel('svauth');
			$oSvauthPlugin = $oSvauthAdminModel->getPluginList();
			Context::set('svauth_plugins', $oSvauthPlugin);
		}

		$oSvdocsModel = getModel('svdocs');
		$aTempExtraKey = $oSvdocsModel->getExtraKeys($nModuleSrl);
		$nIdx = 0;
		foreach( $aTempExtraKey as $key=>$val)
		{
			switch($val->type)
			{
				case 'text':
				case 'email':
				case 'phone':
					if( $val->is_required == 'Y' )
					{
						$aEextraKey[$nIdx]->name = $val->name;
						$aEextraKey[$nIdx++]->eid = $val->eid;
					}
					break;
			}
		}
		if( $module_info->svauth_plugin_srl )
		{
			$aEextraKey[$nIdx]->name = '핸드폰번호';
			$aEextraKey[$nIdx++]->eid = 'applicant_phone';
		}
		$aEextraKey[$nIdx]->name = '회원번호';
		$aEextraKey[$nIdx++]->eid = 'member_srl';
		Context::set('extra_keys', $aEextraKey);

		//Security
		$security = new Security();
		$security->encodeHTML('layout_list..layout');
		$security->encodeHTML('layout_list..title');
		$security->encodeHTML('mlayout_list..layout');
		$security->encodeHTML('mlayout_list..title');
		$security->encodeHTML('module_info.');
		Context::set('module_info', $module_info);
		$this->setTemplateFile('svdocs_info');
	}
/**
* @brief display the selected promotion admin information
**/
	public function dispSvdocsAdminTermsPrivacyUsage() 
	{
		$nModuleSrl = (int)$this->module_info->module_srl;
		$oSvdocsAdminModel = &getAdminModel('svdocs');
		Context::set('privacy_usage_term', $oSvdocsAdminModel->getPrivacyTerm($nModuleSrl, 'privacy_usage_term'));
		Context::set('mid', $this->module_info->mid);

		// get a privacy_usage_term editor
		$option = new stdClass();
		$option->primary_key_name = 'temp_srl';
		$option->content_key_name = 'privacy_usage_term';
		$option->allow_fileupload = false;
		$option->enable_autosave = false;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->resizable = true;
		$option->height = 200;
		$oEditorModel = getModel('editor');
		$editor = $oEditorModel->getEditor(0, $option);
		Context::set('editor', $editor);
		$this->setTemplateFile('svdocs_terms_privacy_usage');
	}
/**
* @brief display the selected promotion admin information
**/
	public function dispSvdocsAdminTermsPrivacyShr() 
	{
		$nModuleSrl = (int)$this->module_info->module_srl;
		$oSvdocsAdminModel = &getAdminModel('svdocs');
		Context::set('privacy_shr_term', $oSvdocsAdminModel->getPrivacyTerm($nModuleSrl, 'privacy_shr_term'));
		Context::set('mid', $this->module_info->mid);

		// get a privacy_usage_term editor
		$option = new stdClass();
		$option->primary_key_name = 'temp_srl';
		$option->content_key_name = 'privacy_shr_term';
		$option->allow_fileupload = false;
		$option->enable_autosave = false;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->resizable = true;
		$option->height = 200;
		$oEditorModel = getModel('editor');
		$editor = $oEditorModel->getEditor(0, $option);
		Context::set('editor', $editor);
		$this->setTemplateFile('svdocs_terms_privacy_shr');
	}
/**
 * Display skin setting page
 */
	public function dispSvdocsAdminSkinInfo()
	{
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skin_info');
	}
/**
 * Display mobile skin setting page
 */
	public function dispSvdocsAdminMobileSkinInfo()
	{
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);

		$this->setTemplateFile('skin_info');
	}
/**
 * @brief display the grant information
 **/
	public function dispSvdocsAdminGrantInfo() 
	{
		// get the grant infotmation from admin module
		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);
		$this->setTemplateFile('grant_list');
	}
/**
 * @brief display extra variables
 **/
	public function dispSvdocsAdminExtraVars() 
	{
		$oSvdocsModel = getModel('svdocs');
		$extra_vars_content = $oSvdocsModel->getExtraVarsHTML($this->module_info->module_srl);
		Context::set('extra_vars_content', $extra_vars_content);
		$this->setTemplateFile('extra_vars');
	}
/**
 * @brief display default setting screen
 **/
	public function dispSvdocsAdminConfig()
	{
		$nModuleSrl = Context::get('module_srl');
		$oSvdocsAdminModel = &getAdminModel('svdocs');
		$oModuleInfo = $oSvdocsAdminModel->getModuleConfig($nModuleSrl);
		Context::set('config', $oModuleInfo);
		$this->setTemplateFile('default_setting');
	}
}