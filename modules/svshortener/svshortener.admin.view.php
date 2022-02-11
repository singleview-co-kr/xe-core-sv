<?php
/**
 * @class  svshortenerAdminView
 * @author singleview(root@singleview.co.kr)
 * @brief  svshortenerAdminView
**/ 
class svshortenerAdminView extends svshortener
{
/**
 * @brief initialization
 **/
	function init()
	{
		// Get a template path (page in the administrative template tpl putting together)
		$this->setTemplatePath($this->module_path.'tpl');
	}
/**
 * @brief display svshortener shorten URL list
 **/
	function dispSvshortenerAdminIndex() 
	{
		$args->page = Context::get('page');
		$search_target_list = array('s_applicant_name','s_applicant_phone_number');
		$search_target = Context::get('search_target');
		$search_keyword = Context::get('search_keyword');
		if(in_array($search_target,$search_target_list) && $search_keyword) $args->{$search_target} = $search_keyword;
		$output = executeQueryArray('svshortener.getAdminSvshortenerUrls', $args);

		// get svtracker addon info
		$oAddonAdminModel = getAdminModel('addon');
		$svtrackerAddonList = $oAddonAdminModel->getAddonInfoXml('svtracker');
		if( $svtrackerAddonList == NULL )
			return new BaseObject(-1, 'msg_error_svtracker_addon_not_installed');

		foreach( $svtrackerAddonList->extra_vars as $key => $val )
		{
			if( $val->name == 'shortner_query_name' )
			{
				Context::set('shortner_query_name', $val->value );
				break;
			}
		}
		unset( $svtrackerAddonList );
		
		$db_info = Context::getDBInfo();
		Context::set('default_url', $db_info->default_url );
		unset( $db_info );
		
		$oSvshortenerModel = getModel('svshortener');
		$oSvshortenerModel->setBloggerType();
		
		foreach( $output->data as $key=>$val )
		{
			$val->utm_term = $oSvshortenerModel->generateUtmTerm($val->utm_term,$val->blogger_type,$val->blogger_id );
			unset( $val->blogger_type );
			unset( $val->blogger_id );
		}

		Context::set('svshortener_list', $output->data );
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		
		// Set a template file
		$this->setTemplateFile('index');
	}
/**
 * @brief
 **/
	function dispSvshortenerAdminInsert() 
	{
		require_once(_XE_PATH_.'modules/svshortener/blogger_type.php');
		Context::set('blogger_type', $aBloggerType );
		$this->setTemplateFile('svshortner_insert');
	}
}