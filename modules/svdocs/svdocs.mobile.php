<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svdocsMobile
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsMobile class
 */
require_once(_XE_PATH_.'modules/svdocs/svdocs.view.php');
class svdocsMobile extends svdocsView
{
	function init()
	{
		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		if(!is_dir($template_path)||!$this->module_info->mskin) 
		{
			$this->module_info->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);
	}

	/**
	 * @brief General request output
	 */
	function dispSvdocsIndex()
	{
		$nOpenTimestamp = strtotime( $this->module_info->open_datetime);
		if( time() < $nOpenTimestamp )
		{
			if( $this->module_info->use_teaser_mode == 'Y' )
				Context::set('teaser_open', 'Y');
			else
				return new BaseObject(1, sprintf(Context::getLang('msg_svodcs_not_opened_yet'), date('Y-m-d h:i:s', $nOpenTimestamp) )); 
		}

		// 모듈 설정 정보에서 svauth plugin 입력되어 있으면 svauth 호출
		$nSvauthPluginSrl = (int)$this->module_info->svauth_plugin_srl;
		if( $nSvauthPluginSrl )
		{
			$oSvauthModel = &getModel('svauth');
			$oPluginInfo = $oSvauthModel->getPlugin($nSvauthPluginSrl);
			Context::set('svauth_on', 'Y');
			Context::set('sms_auth_agreement', nl2br($oPluginInfo->_g_oPluginInfo->sms_auth_agreement) );
		}

		if($this->module_srl) 
			Context::set('module_srl', $this->module_srl);

		$oSvdocsModel = &getModel('svdocs');
		$oDocInfo = $oSvdocsModel->getDocInfo( $this->module_srl );
		$sPrivacyUsageTerm = $oSvdocsModel->getPrivacyTerm($this->module_srl,'privacy_usage_term');
		$sPrivacyShrTerm = $oSvdocsModel->getPrivacyTerm($this->module_srl,'privacy_shr_term');

		$this->module_info->privacy_usage_term = $sPrivacyUsageTerm;
		$this->module_info->privacy_shr_term = $sPrivacyShrTerm;
		//$this->module_info->slide_img_urls_mob = explode("\n",	$this->module_info->slide_img_urls_mob);

		Context::set('remaining_applicants', $oDocInfo->nRemainingApplicants );
		Context::set('module_info', $this->module_info);

		$extra_keys = $oSvdocsModel->getExtraKeys($this->module_srl);
		foreach($extra_keys as $key=>$val)
		{
			if( $val->type == 'checkbox' )
				$val->name .= Context::getLang('title_multiple_choice');
		}
		Context::set('extra_keys', $extra_keys);
		$oDefaultConfig = $oSvdocsModel->getModuleConfig();
		Context::set('config', $oDefaultConfig);

		$output = $oSvdocsModel->getDocList($this->module_srl);
		Context::set('applicant_list', $output->data);

		$this->setTemplateFile('mobile_add');
	}
}
/* End of file svdocs.mobile.php */
/* Location: ./modules/svdocs/svdocs.mobile.php */