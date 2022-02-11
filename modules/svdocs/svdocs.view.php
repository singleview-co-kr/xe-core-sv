<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svdocsView
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsView
**/ 
class svdocsView extends svdocs
{
	var $module_srl = 0;
/**
 * @brief Initialization
 */
	public function init()
	{
		$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		if(!is_dir($template_path)||!$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		}
		$this->setTemplatePath($template_path);
	}
/**
 * @brief General request output
 */
	public function dispSvdocsIndex()
	{
		if( $this->module_info->is_allow_closed == 'Y' )
		{
			$oRst = $this->dispSvdocsUpdate();
			return $oRst;
		}
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
//var_dump($oDocInfo->nRemainingApplicants );
		$sPrivacyUsageTerm = $oSvdocsModel->getPrivacyTerm($this->module_srl,'privacy_usage_term');
		$sPrivacyShrTerm = $oSvdocsModel->getPrivacyTerm($this->module_srl,'privacy_shr_term');

		$this->module_info->privacy_usage_term = $sPrivacyUsageTerm;
		$this->module_info->privacy_shr_term = $sPrivacyShrTerm;
		
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
		Context::set('kakao_link', "http://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]);
	
		$this->setTemplateFile('add');
	}
/**
 * @brief 응모자 정보 관리 화면
 **/
	public function dispSvdocsUpdate() 
	{
		$logged_info = Context::get('logged_info');
		if($logged_info)
		{
			$args->module_srl = $this->module_info->module_srl;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQueryArray('svdocs.getDocByModuleMember', $args);
			$nDocCnt = count( $output->data );
			switch( $nDocCnt  )
			{
				case 0:
					return new BaseObject(-1, 'msg_error_no_docs_registered');
				case 1:
					Context::set('doc_info', $output->data);
					Context::set('module_srl', $this->module_info->module_srl);
					Context::set('svdocs_srl', (int)$output->data[0]->doc_srl);
					$this->_setModificationForm();
					$this->setTemplateFile('update');
					return new BaseObject();
				default:
					return new BaseObject(-1, 'msg_error_multiple_registration');
			}
		}
		else
		{
			$this->dispSvdocsGuestLogin();
			return new BaseObject();
		}
	}
/**
 * @brief 비회원 자격 검사
 **/
	public function dispSvdocsGuestLogin()
	{
		Context::set('module_srl', $this->module_info->module_srl);
		$this->setTemplateFile('login');
	}
/**
 * @brief 비회원 응모 수정
 **/
	public function dispSvdocsGuestDetail() 
	{
		$sApplicantName = str_replace( '-', '', strip_tags( trim( Context::get('applicant_name') ) ) );
		if ( strlen( $sApplicantName ) == 0 )
			return new BaseObject(-1, 'msg_no_applicant_name');

		$sApplicantPhoneNumber = str_replace( '-', '', strip_tags( trim( Context::get('applicant_phone_number') ) ) );
		if ( strlen( $sApplicantPhoneNumber ) == 0 )
			return new BaseObject(-1, 'msg_no_applicant_phone_number');

		$nModuleSrl = (int)Context::get('module_srl');
		$args->module_srl = $nModuleSrl;
		$args->applicant_name = $sApplicantName;
		$args->applicant_phone = $sApplicantPhoneNumber;
		$output = executeQuery('svdocs.getDocByModuleGuest', $args);
		
		$nRecCnt = count( $output->data );
		if( $nRecCnt == 0 ) 
		{
			$returnUrl = Context::get('error_return_url') ? Context::get('error_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispSvdocsUpdate');
			$this->setRedirectUrl($returnUrl);
			return new BaseObject(-1, 'msg_error_unregistered_phone_number');
		}
		else if( $nRecCnt > 1 ) 
		{
			$returnUrl = Context::get('error_return_url') ? Context::get('error_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispSvdocsUpdate');
			$this->setRedirectUrl($returnUrl);
			return new BaseObject(-1, 'msg_error_weird_phone_number');
		}

		$nAllowedDocSrl = (int)$output->data->doc_srl;
		//setCookie('svdocs_guest_permission', $nAllowedDocSrl);
		Context::set('module_srl', $nModuleSrl);
		Context::set('svdocs_srl', $nAllowedDocSrl);

		$this->_setModificationForm();
		$this->setTemplateFile('update');
	}
/**
 * @brief 응모양식 호출
 **/
	private function _setModificationForm()
	{
		$oSvdocsModel = &getModel('svdocs');
		$oDocInfo = $oSvdocsModel->getDocInfo( $this->module_srl );
		Context::set('remaining_applicants', $oDocInfo->nRemainingApplicants );
		Context::set('module_info', $this->module_info);
		Context::set('doc_srl', $nAllowedDocSrl);
		$extra_keys = $oSvdocsModel->getExtraKeys($this->module_srl);
		Context::set('extra_keys', $extra_keys);
		$oDefaultConfig = $oSvdocsModel->getModuleConfig();
		Context::set('config', $oDefaultConfig);
	}
}
/* End of file svdocs.view.php */
/* Location: ./modules/svdocs/svdocs.view.php */