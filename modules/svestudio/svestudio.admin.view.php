<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svestudioAdminView
 * @author singleview(root@singleview.co.kr)
 * @brief  svestudioAdminView
**/ 
class svestudioAdminView extends svestudio
{
/**
 * @brief initialization
 **/
	public function init()
	{
		// module이 svshopmaster일때 관리자 레이아웃으로
		if(Context::get('module') == 'svshopmaster')
		{
			$sClassPath = _XE_PATH_ . 'modules/svshopmaster/svshopmaster.class.php';
			if(file_exists($sClassPath))
			{
				require_once($sClassPath);
				$oSvshopmaster = new svshopmaster;
				$oSvshopmaster->init($this);
			}
		}

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

		//if(Context::get('module')=='svshopmaster')
		//{
		//	$this->setLayoutPath('');
		//	$this->setLayoutFile('common_layout');
		//}

		// Get a template path (page in the administrative template tpl putting together)
		$this->setTemplatePath($this->module_path.'tpl');
	}
/**
 * @brief default admin view
 */
	public function dispSvestudioAdminModInstList() 
	{
		$nPage = Context::get('page');
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		$oRst = $oSvestudioAdminModel->getMidList( $nPage );
		/*$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('svestudio.getModInstList', $args);
		$list = $output->data;
		
		$oModuleModel = &getModel('module');
		$list = $oModuleModel->addModuleExtraVars($list);*/

		Context::set('total_count', $oRst->total_count);
		Context::set('total_page', $oRst->total_page);
		Context::set('page', $oRst->page);
		Context::set('page_navigation', $oRst->page_navigation);
		Context::set('list', $oRst->data);

		//$module_category = $oModuleModel->getModuleCategories();
		//Context::set('module_category', $module_category);
		Context::set('module_category', $oRst->get( 'module_category' ));
		$this->setTemplateFile('modinstlist');
	}
/**
 * @brief 
 **/
	function dispSvestudioAdminInsertModInst() 
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

		// svpg plugin list
		//$oSvpgModel = &getModel('svpg');
		//$oSvPgModules = $oSvpgModel->getSvpgList();
		//Context::set('svpg_modules', $oSvPgModules);

		// svorder linked module list
		//$oSvorderAdminModel = &getAdminModel('svorder');
		//$oSvorderModules = $oSvorderAdminModel->getMidList();
		//Context::set( 'svorder_modules', $oSvorderModules );

		//$oSvcartModel = &getModel('svcart');
		//Context::set('delivery_companies', $oSvcartModel->getDeliveryCompanies());

		$oEditorModel = &getModel('editor');
		$config = $oEditorModel->getEditorConfig(0);
		// 에디터 옵션 변수를 미리 설정
		$option->skin = $config->editor_skin;
		$option->content_style = $config->content_style;
		$option->content_font = $config->content_font;
		$option->content_font_size = $config->content_font_size;
		$option->colorset = $config->sel_editor_colorset;
		$option->allow_fileupload = true;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->disable_html = false;
		$option->height = 200;
		$option->enable_autosave = false;
		$option->primary_key_name = 'module_srl';
		$option->content_key_name = 'delivery_info';
		$editor = $oEditorModel->getEditor($this->module_info->module_srl, $option);
		Context::set('editor', $editor);

		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$oModuleAdminModel = &getAdminModel('module');
		$sGrantContent = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $sGrantContent);

		$this->setTemplateFile('insertmodinst');
	}
/**
 * @brief display the grant information
 **/
	public function dispSvestudioAdminGrantInfo() 
	{
		$oModuleAdminModel = &getAdminModel('module');
		$sGrantContent = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $sGrantContent);
		$this->setTemplateFile('grantinfo');
	}
/**
 * @brief display download full performance data form
 **/
	public function dispSvestudioAdminDownloadRawData() 
	{
		$oRst = executeQuery('svestudio.getMediaPerfLogFirst');
		if( !$oRst->toBool() )
			return $oRst;
		$oFirstLog = array_pop($oRst->data);
		$sFirstDate = $oFirstLog->logdate;

		$oRst = executeQuery('svestudio.getMediaPerfLogLatest');
		if( !$oRst->toBool() )
			return $oRst;
		$oLatestLog = array_pop($oRst->data);
		$sLatestDate = $oLatestLog->logdate;

		$aAvailablePeriod['begin'] = $sFirstDate;
		$aAvailablePeriod['end'] = $sLatestDate;
		Context::set('available_period', $aAvailablePeriod);

		$aRecommendedPeriod['begin'] = date('Ymd',strtotime($sLatestDate.' - 1 week'));
		$aRecommendedPeriod['end'] = $sLatestDate;
		Context::set('recommended_period', $aRecommendedPeriod);

		$this->setTemplateFile('frm_rawdata_download');
	}
/**
* @brief get the grant configuration
**/
	public function dispSvestudioAdminActGrantByMid() 
	{
		$oModuleModel = &getModel('module');
		$aSvshopModule = array();
		//foreach( $this->_g_aSvshopModule as $nIdx => $sModuleName )
		//{
		//	$oModuleInfo = $oModuleModel->getModuleInfoXml( $sModuleName );
		//	$aSvshopModule[$sModuleName] = $oModuleInfo->title;
		//}
		//Context::set('svoshop_module_list', $aSvshopModule);
		
		//$sModuleName = Context::get('module_name');
		//if( !$sModuleName )
		//{
		//	$sModuleName = $this->_g_aSvshopModule[0];
		//	Context::set('module_name', $sModuleName);
		//}
		$sModuleName = 'svestudio';
		$oModuleXml = $oModuleModel->getModuleActionXml($sModuleName);
		$aAdminAct = array();
		foreach( $oModuleXml->action as $sAct=>$oInfo )
		{
			if( strpos($sAct, 'Admin') === false )
				$aAdminAct[] = $sAct;
		}
		Context::set('admin_act_list', $aAdminAct);

		$oSvestudioAdminModel = &getAdminModel('svestudio');
		$oModuleInfo = $oSvestudioAdminModel->getModuleConfig();
		$aInvitedGroupSrl = unserialize( $oModuleInfo->invited_member_group );

		$nModuleSrl = Context::get('module_srl');
		$oMidInfo = $oSvestudioAdminModel->getMidConfig($nModuleSrl);
		$oMemberModel = &getModel('member');
		foreach( $aInvitedGroupSrl as $nGrpSrl => $bAllowed )
		{
			$oGrpInfo = $oMemberModel->getGroup( $nGrpSrl );
			$aInvitedGroupSrl[$nGrpSrl] = $oGrpInfo->title;
		}
		Context::set('invited_group_list', $aInvitedGroupSrl);
		Context::set('permitted_act_list', $oMidInfo->permitted_act_by_mid );
		$this->setTemplateFile('grant_act_by_module');
	}
/**
 * @brief 
 **/
	public function dispSvestudioAdminCacheInit()
	{
		// set the template file
		$this->setTemplateFile('init_cache');
	}
/**
 * @brief display invitation group
 **/
	public function dispSvestudioAdminInviteInfo()
	{
		$oMemberModel = getModel('member');
		$oGroups = $oMemberModel->getGroups();

		$aGroupList = array();
		foreach( $oGroups as $key=>$val )
		{
			if(is_null($aGroupList[$key]))
				$aGroupList[$key] = new stdClass();
			$aGroupList[$key]->group_srl = $val->group_srl;
			$aGroupList[$key]->title = $val->title;
		}
		Context::set('group_list', $aGroupList);
		
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		$oModuleInfo = $oSvestudioAdminModel->getModuleConfig();
		$aInvitedGroupSrl = unserialize( $oModuleInfo->invited_member_group );
		Context::set('invited_group_list', $aInvitedGroupSrl);
		$this->setTemplateFile('invited_user_mgmt');
	}

/**
 * @brief display the basic configuration form
 **/
	public function dispSvestudioAdminConfig()
	{
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		$oConfig = $oSvestudioAdminModel->getModuleConfig();

		$sSecrectKeyIniFilePath = _XE_PATH_.'files/svestudio/key.config.ini';
		$aKeyConfig = FileHandler::readIniFile($sSecrectKeyIniFilePath);

		$oConfig->svestudio_sv_secret_key = $aKeyConfig['sv_secret_key'];
		$oConfig->svestudio_sv_iv = $aKeyConfig['sv_iv'];

		Context::set('config',$oConfig);

		// set the template file
		$this->setTemplateFile('config');
	}
/**
 * @brief 
 **/
	public function dispSvestudioAdminReportMediaCost()
	{
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		$oSvestudioAdminModel->init();
		
		$sMonth = Context::get('month');
		$aMonthlyPeriod = $oSvestudioAdminModel->getMontlyPeriod($sMonth);
		if( $sMonth )
		{
			$sStartDate = $aMonthlyPeriod['first_day_of_this_month'];
			$sFormattedStartDate = substr($sStartDate,0,4).'-'.substr($sStartDate,4,2).'-'.substr($sStartDate,6,2);
			Context::set( 'start_date', $sFormattedStartDate );
			$sEndDate = $aMonthlyPeriod['last_day_of_this_month'];
			$sFormattedEndDate = substr($sEndDate,0,4).'-'.substr($sEndDate,4,2).'-'.substr($sEndDate,6,2);
			Context::set( 'end_date', $sFormattedEndDate );
		}
		else
		{
			Context::set( 'start_date', date('Y-m-01') );
			Context::set( 'end_date', date('Y-m-d') );
		}
		
		$aPerformanceInfoLastMonth = $oSvestudioAdminModel->getMediaPerformanceInfo($aMonthlyPeriod['first_day_of_last_month'],$aMonthlyPeriod['last_day_of_last_month']);
		Context::set( 'lm_mtd_cost', $aPerformanceInfoLastMonth->mtd_cost );
		Context::set( 'lm_mtd_agency_cost', $aPerformanceInfoLastMonth->mtd_agency_cost ); //////////////////////
		Context::set( 'lm_mtd_impression', $aPerformanceInfoLastMonth->mtd_impression );
		Context::set( 'lm_mtd_session', $aPerformanceInfoLastMonth->mtd_session );
		Context::set( 'lm_mtd_new_session', $aPerformanceInfoLastMonth->mtd_new_session );
		Context::set( 'lm_mtd_revenue', $aPerformanceInfoLastMonth->mtd_revenue );

		Context::set( 'lm_source_medium_gross_impression', $aPerformanceInfoLastMonth->source_medium_gross_impression );
		Context::set( 'lm_source_medium_gross_cost', $aPerformanceInfoLastMonth->source_medium_gross_cost );
		Context::set( 'lm_source_medium_gross_agency_cost', $aPerformanceInfoLastMonth->source_medium_gross_agency_cost ); ///////////////////////
		Context::set( 'lm_source_medium_gross_session', $aPerformanceInfoLastMonth->source_medium_gross_session );
		Context::set( 'lm_source_medium_gross_new_session', $aPerformanceInfoLastMonth->source_medium_gross_new_session );
		Context::set( 'lm_source_medium_gross_pvs', $aPerformanceInfoLastMonth->source_medium_gross_pvs );
		Context::set( 'lm_source_medium_gross_dur_sec', $aPerformanceInfoLastMonth->source_medium_gross_dur_sec );
		Context::set( 'lm_source_medium_gross_cpc', $aPerformanceInfoLastMonth->source_medium_gross_cpc );
		Context::set( 'lm_source_medium_gross_revenue', $aPerformanceInfoLastMonth->source_medium_gross_revenue );
		Context::set( 'lm_source_medium_gross_roas', $aPerformanceInfoLastMonth->source_medium_gross_roas );
		Context::set( 'lm_source_medium_gross_conv_rate', $aPerformanceInfoLastMonth->source_medium_gross_conv_rate );

		Context::set( 'lm_source_medium_mtd_impression', $aPerformanceInfoLastMonth->source_medium_mtd_impression );
		Context::set( 'lm_source_medium_mtd_cost', $aPerformanceInfoLastMonth->source_medium_mtd_cost );
		Context::set( 'lm_source_medium_mtd_agency_cost', $aPerformanceInfoLastMonth->source_medium_mtd_agency_cost ); ////////////////

		Context::set( 'lm_source_medium_mtd_session', $aPerformanceInfoLastMonth->source_medium_mtd_session );
		Context::set( 'lm_source_medium_mtd_new_session', $aPerformanceInfoLastMonth->source_medium_mtd_new_session );
		Context::set( 'lm_source_medium_mtd_pvs', $aPerformanceInfoLastMonth->source_medium_mtd_pvs );
		Context::set( 'lm_source_medium_mtd_dur_sec', $aPerformanceInfoLastMonth->source_medium_mtd_dur_sec );
		Context::set( 'lm_source_medium_mtd_cpc', $aPerformanceInfoLastMonth->source_medium_mtd_cpc );
		Context::set( 'lm_source_medium_mtd_revenue', $aPerformanceInfoLastMonth->source_medium_mtd_revenue );
		Context::set( 'lm_source_medium_mtd_roas', $aPerformanceInfoLastMonth->source_medium_mtd_roas );
		Context::set( 'lm_source_medium_mtd_conv_rate', $aPerformanceInfoLastMonth->source_medium_mtd_conv_rate );
		Context::set( 'lm_daily', $aPerformanceInfoLastMonth->period_status );

		$aPerformanceInfoCurMonth = $oSvestudioAdminModel->getMediaPerformanceInfo($aMonthlyPeriod['first_day_of_this_month'],$aMonthlyPeriod['last_day_of_this_month']);
		Context::set( 'tm_mtd_cost', $aPerformanceInfoCurMonth->mtd_cost );
		Context::set( 'tm_mtd_agency_cost', $aPerformanceInfoCurMonth->mtd_agency_cost ); //////////////////////
		Context::set( 'tm_mtd_impression', $aPerformanceInfoCurMonth->mtd_impression );
		Context::set( 'tm_mtd_session', $aPerformanceInfoCurMonth->mtd_session );
		Context::set( 'tm_mtd_new_session', $aPerformanceInfoCurMonth->mtd_new_session );
		Context::set( 'tm_mtd_revenue', $aPerformanceInfoCurMonth->mtd_revenue );

		Context::set( 'tm_source_medium_gross_impression', $aPerformanceInfoCurMonth->source_medium_gross_impression );
		Context::set( 'tm_source_medium_gross_cost', $aPerformanceInfoCurMonth->source_medium_gross_cost );
		Context::set( 'tm_source_medium_gross_agency_cost', $aPerformanceInfoCurMonth->source_medium_gross_agency_cost ); ///////////////////////
		Context::set( 'tm_source_medium_gross_session', $aPerformanceInfoCurMonth->source_medium_gross_session );
		Context::set( 'tm_source_medium_gross_new_session', $aPerformanceInfoCurMonth->source_medium_gross_new_session );
		Context::set( 'tm_source_medium_gross_pvs', $aPerformanceInfoCurMonth->source_medium_gross_pvs );
		Context::set( 'tm_source_medium_gross_dur_sec', $aPerformanceInfoCurMonth->source_medium_gross_dur_sec );
		Context::set( 'tm_source_medium_gross_cpc', $aPerformanceInfoCurMonth->source_medium_gross_cpc );
		Context::set( 'tm_source_medium_gross_revenue', $aPerformanceInfoCurMonth->source_medium_gross_revenue );
		Context::set( 'tm_source_medium_gross_roas', $aPerformanceInfoCurMonth->source_medium_gross_roas );
		Context::set( 'tm_source_medium_gross_conv_rate', $aPerformanceInfoCurMonth->source_medium_gross_conv_rate );
		
		Context::set( 'tm_source_medium_mtd_impression', $aPerformanceInfoCurMonth->source_medium_mtd_impression );
		Context::set( 'tm_source_medium_mtd_cost', $aPerformanceInfoCurMonth->source_medium_mtd_cost );
		Context::set( 'tm_source_medium_mtd_agency_cost', $aPerformanceInfoCurMonth->source_medium_mtd_agency_cost ); ////////////////
		Context::set( 'tm_source_medium_mtd_session', $aPerformanceInfoCurMonth->source_medium_mtd_session );
		Context::set( 'tm_source_medium_mtd_new_session', $aPerformanceInfoCurMonth->source_medium_mtd_new_session );
		Context::set( 'tm_source_medium_mtd_pvs', $aPerformanceInfoCurMonth->source_medium_mtd_pvs );
		Context::set( 'tm_source_medium_mtd_dur_sec', $aPerformanceInfoCurMonth->source_medium_mtd_dur_sec );
		Context::set( 'tm_source_medium_mtd_cpc', $aPerformanceInfoCurMonth->source_medium_mtd_cpc );
		Context::set( 'tm_source_medium_mtd_revenue', $aPerformanceInfoCurMonth->source_medium_mtd_revenue );
		Context::set( 'tm_source_medium_mtd_roas', $aPerformanceInfoCurMonth->source_medium_mtd_roas );
		Context::set( 'tm_source_medium_mtd_conv_rate', $aPerformanceInfoCurMonth->source_medium_mtd_conv_rate );
		Context::set( 'tm_daily', $aPerformanceInfoCurMonth->period_status );
		
		$aTotalSourceMediumArray = Array();
		foreach( $aPerformanceInfoLastMonth->source_medium_gross_cost as $key=>$val )
			$aTotalSourceMediumArray[$key] = 0;
		foreach( $aPerformanceInfoCurMonth->source_medium_gross_cost as $key=>$val )
			$aTotalSourceMediumArray[$key] = 0;
		Context::set( 'total_source_medium', $aTotalSourceMediumArray );
		
		$this->setTemplateFile('rpt_media_perf');
	}
/**
 * @brief 
 **/
	public function dispSvestudioAdminReportItem()
	{
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		$aMonthlyPeriod = $oSvestudioAdminModel->getMontlyPeriod();

		// retrieve last month
		$oLmSkuPerformance = $oSvestudioAdminModel->getSkuPerfInfoDaily($aMonthlyPeriod['first_day_of_last_month'],$aMonthlyPeriod['last_day_of_last_month']);
		if (!$oLmSkuPerformance->toBool()) 
			return $oLmSkuPerformance;
		
		$aLmDataPeriod = $oLmSkuPerformance->get( 'aDatePeriod' );
		Context::set( 'lm_by_item', $aLmDataPeriod['by_item'] );
		unset( $aLmDataPeriod );
		unset( $oLmSkuPerformance );

		// retrieve current month
		$oTmSkuPerformance = $oSvestudioAdminModel->getSkuPerfInfoDaily($aMonthlyPeriod['first_day_of_this_month'],$aMonthlyPeriod['last_day_of_this_month']);
		if (!$oTmSkuPerformance->toBool()) 
			return $oTmSkuPerformance;
		
		$aTmDataPeriod = $oTmSkuPerformance->get( 'aDatePeriod' );
		Context::set( 'tm_by_item', $aTmDataPeriod['by_item'] );
		unset( $aTmDataPeriod );
		unset( $oTmSkuPerformance );
		// set the template file
		$this->setTemplateFile('rpt_item');
	}
/**
* @brief get the user & property infotmation from admin module
**/
	public function dispSvestudioAdminAcctMgmt() 
	{
		$nModuleSrl = Context::get('module_srl');
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		$oModuleInfo = $oSvestudioAdminModel->getClientConfig($nModuleSrl);

		$aAcctSetTitle = unserialize( $oModuleInfo->svestudio_acct_set_title );
		$aNvrApiKey = unserialize( $oModuleInfo->svestudio_nvrsearchad_api_key );
		$aNvrSecretKey = unserialize( $oModuleInfo->svestudio_nvrsearchad_secret_key );
		$aNvrCustomerId = unserialize( $oModuleInfo->svestudio_nvrsearchad_customer_id );

		$oConfig->svestudio_acct_set_title = $aAcctSetTitle;
		$oConfig->svestudio_nvrsearchad_api_key = $aNvrApiKey;
		$oConfig->svestudio_nvrsearchad_secret_key = $aNvrSecretKey;
		$oConfig->svestudio_nvrsearchad_customer_id = $aNvrCustomerId;

		Context::set('config',$oConfig);
		
		$aMasterCampaign = unserialize( $oModuleInfo->svestudio_nvr_master_campaign );
		Context::set('svestudio_nvr_master_campaign_list', $aMasterCampaign);
		$aMasterCampaignBudget = unserialize( $oModuleInfo->svestudio_nvr_master_campaign_budget );
		Context::set('svestudio_nvr_master_campaign_budget_list', $aMasterCampaignBudget);
		$aMasterAdgrp = unserialize( $oModuleInfo->svestudio_nvr_master_adgrp );
		Context::set('svestudio_nvr_master_adgrp_list', $aMasterAdgrp);
		$aMasterAdgrpBudget = unserialize( $oModuleInfo->svestudio_nvr_master_adgrp_budget );
		Context::set('svestudio_nvr_master_adgrp_budget_list', $aMasterAdgrpBudget);
		$aMasterKw = unserialize( $oModuleInfo->svestudio_nvr_master_kw );
		Context::set('svestudio_nvr_master_kw_list', $aMasterKw);
		$aMasterAd = unserialize( $oModuleInfo->svestudio_nvr_master_ad );
		Context::set('svestudio_nvr_master_ad_list', $aMasterAd);
		$aMasterAdExt = unserialize( $oModuleInfo->svestudio_nvr_master_ad_ext );
		Context::set('svestudio_nvr_master_ad_ext_list', $aMasterAdExt);
		$aMasterQi = unserialize( $oModuleInfo->svestudio_nvr_master_qi );
		Context::set('svestudio_nvr_master_qi_list', $aMasterQi);

		$aStatAd = unserialize( $oModuleInfo->svestudio_nvr_stat_ad );
		Context::set('svestudio_nvr_stat_ad_list', $aStatAd);
		$aStatAdDetail = unserialize( $oModuleInfo->svestudio_nvr_stat_ad_detail );
		Context::set('svestudio_nvr_stat_ad_detail_list', $aStatAdDetail);
		$aStatAdConv = unserialize( $oModuleInfo->svestudio_nvr_stat_ad_conv );
		Context::set('svestudio_nvr_stat_ad_conv_list', $aStatAdConv);
		$aStatAdConvDetail = unserialize( $oModuleInfo->svestudio_nvr_stat_ad_conv_detail );
		Context::set('svestudio_nvr_stat_ad_conv_detail_list', $aStatAdConvDetail);
		$aStatAdExt = unserialize( $oModuleInfo->svestudio_nvr_stat_ad_ext );
		Context::set('svestudio_nvr_stat_ad_ext_list', $aStatAdExt);
		$aStatAdExtConv = unserialize( $oModuleInfo->svestudio_nvr_stat_ad_ext_conv );
		Context::set('svestudio_nvr_stat_ad_ext_conv_list', $aStatAdExtConv);
		$aStatAdNpayConv = unserialize( $oModuleInfo->svestudio_nvr_stat_npay_conv );
		Context::set('svestudio_nvr_stat_npay_conv_list', $aStatAdNpayConv);
		$aStatAdExpkw = unserialize( $oModuleInfo->svestudio_nvr_stat_expkw );
		Context::set('svestudio_nvr_stat_expkw_list', $aStatAdExpkw);

//var_dump( $aMasterCampaign );
		$this->setTemplateFile('acct_mgmt');
	}
/**
 * @brief 폐지 예정 Information output of the selected page
 */
	/*function dispSvestudioAdminModifyClientInfo()
	{
		// Get module_srl by GET parameter
		$module_srl = Context::get('module_srl');
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		$module_info = $oSvestudioAdminModel->getClientConfig($module_srl);

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

		Context::set('module_info', $module_info);
		$this->setTemplateFile('insert_client');
	}*/
/**
 * @brief 폐지 예정 display the selected promotion admin information
 **/
	/*function dispSvestudioAdminInsertClient() 
	{
		//$this->dispSvestudioAdminModuleInfo();
		// get the layouts path
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);
		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// get the skins path
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);
		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);
		$this->setTemplateFile('insert_client');
	}*/

/**
 * @brief 폐지 예정 Delete Svestudio module
 */
	/*function dispSvestudioAdminDelete()
	{
		$module_srl = Context::get('module_srl');
		if(!$module_srl) 
			return $this->dispContent();

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module', 'mid');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		Context::set('module_info',$module_info);
		// Set a template file
		$this->setTemplateFile('svestudio_delete');
	}*/
}