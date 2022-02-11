<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svdocsAdminController
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsAdminController
**/ 

class svdocsAdminController extends svdocs
{
	/**
	 * @brief initialization
	 **/
	function init() 
	{
	}

	/**
 * @brief
 */
	public function procSvdocsAdminInvalidateSmsAuth()
	{
		$aAuthSrl = Context::get('auth_srl' );
		if( !is_array( $aAuthSrl ) )
			return new BaseObject( -1, 'msg_invalid_auth_srl' );

		if( count($aAuthSrl) > 0 )
		{
			foreach($aAuthSrl as $key=>$val)
			{
				$args->authentication_srl = (int)$val;
				$args->is_valid = 'N';
				$output = executeQuery('svdocs.updateSvdocsAdminSmsAuthInvalidate', $args );
			}
		}
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvdocsAdminSmsAuthList', 'module_srl', Context::get('module_srl'), 'rcpt_no', Context::get('rcpt_no') ) );
	}	
/**
 * @brief
 */
	public function procSvdocsAdminInsertConfidentialApplicant()
	{
		// 쿠폰 리스트 설정
		$nModuleSrl = Context::get('module_srl' );
		if( strlen( $nModuleSrl ) == 0 )
			return new BaseObject( -1, 'msg_invalid_module_srl' );

		$sApplicantList = Context::get('svdocs_confidential_applicant_info' );
		if( strlen( $sApplicantList ) == 0 )
			return new BaseObject( -1, 'msg_invalid_confidential_applicant_info_list' );

		$aApplicantList = explode( PHP_EOL, $sApplicantList );

		if( count( $aApplicantList ) == 0 )
			return new BaseObject( -1, 'msg_invalid_confidential_application_list' );
		
		$args->module_srl = $nModuleSrl;
		$oSvdocsAdminModel = getAdminModel('svdocs');

		$nIdx = $oSvdocsAdminModel->getSvdocsAdminConfidentialMaxIndex();

		foreach( $aApplicantList as $key => $val )
		{
			$args->doc_srl = ++$nIdx;
			$aInfo = explode (',', $val);

			$args->applicant_name = trim( $aInfo[0] );
			$args->applicant_phone_number = trim( str_replace( '-', '', $aInfo[1] ) );
			$output = executeQuery('svdocs.insertAdminConfidentailApplicant', $args );
			if(!$output->toBool())
				return $output;

			unset( $args->doc_srl );
			unset( $args->applicant_name );
			unset( $args->applicant_phone_number );
		}

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvdocsAdminApplicantsBoardConfidential', 'module_srl', $nModuleSrl ) );
	}
/**
 * @brief 
 */
	public function procSvdocsAdminDelete()
	{
		$module_srl = Context::get('module_srl');
		// delete docs belongint to the module
		$output = $this->_deleteAllDocsByModule( $module_srl );

		if( !$output->toBool() )
			return $output;

		// delete designated module
		
		// Get an original
		$oModuleController = getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) 
			return $output;

		$this->add('module','page');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSvdocsAdminIndex');
		$this->setRedirectUrl($returnUrl);
	}
/**
 * @brief 
 */
	public function procSvdocsAdminDeleteDoc()
	{
		$module_srl = Context::get('module_srl');
		$doc_srls = Context::get('doc_srls');
		// delete docs belongint to the module
		$output = $this->_deleteSingleDocsByModule( $module_srl, $doc_srls );

		if( !$output->toBool() )
			return $output;

		$this->add('module','page');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSvdocsAdminApplicantsList', 'module_srl', $module_srl, 'page',Context::get('page'));
		$this->setRedirectUrl($returnUrl);
	}
/**
 * @brief
 */
	private function _deleteSingleDocsByModule( $nModuleSrl, $aDocSrl )
	{
		$args->module_srl = $nModuleSrl;
		foreach( $aDocSrl as $key => $val )
		{
			$args->doc_srl = $val;
			//$output = executeQuery('svdocs.deleteSvdocsSingleDoc', $args ); // 물리적 삭제
			$output = executeQuery('svdocs.updateSingleDocDeleted', $args ); // 삭제 표시
			
			if( !$output->toBool() )
				return new BaseObject(-1, 'msg_error_svdocs_db_query');
		}

		return new BaseObject(0);
	}	
/**
 * @brief
 */
	private function _deleteAllDocsByModule( $nModuleSrl )
	{
		$args->module_srl = $nModuleSrl;
		$output = executeQuery('svdocs.deleteSvdocsGroup', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svdocs_db_query');

		return new BaseObject(0);
	}
/**
 * @brief 
 **/
	public function procSvdocsAdminInsertConfig()
	{
		$sGaV3TrackingId = Context::get('ga_v3_tracking_id');
		if( strlen( $sGaV3TrackingId ) )
			$oArgs->ga_v3_tracking_id = $sGaV3TrackingId;

		$sGaV4TrackingId = Context::get('ga_v4_tracking_id');
		if( strlen( $sGaV4TrackingId ) )
			$oArgs->ga_v4_tracking_id = $sGaV4TrackingId;

		$sNvadConvId = Context::get('nvad_conv_id');
		if( strlen( $sNvadConvId ) )
			$oArgs->nvad_conv_id = $sNvadConvId;

		$sFbPixelId = Context::get('facebook_pixel_id');
		if( strlen( $sFbPixelId ) )
			$oArgs->facebook_pixel_id = $sFbPixelId;
		
		$sFbAppId = Context::get('facebook_app_id');
		if( strlen( $sFbAppId ) )
			$oArgs->facebook_app_id = $sFbAppId;
		
		$sKakaoAppJsKey = Context::get('kakao_app_js_key');
		if( strlen( $sKakaoAppJsKey ) )
			$oArgs->kakao_app_js_key = $sKakaoAppJsKey;

		$output = $this->_saveModuleConfig($oArgs);
		if(!$output->toBool())
			$this->setMessage( 'error_occured' );
		else
			$this->setMessage( 'success_updated' );

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvdocsAdminConfig' ));
	}
/**
 * @brief arrange and save module config
 **/
	private function _saveModuleConfig($oArgs)
	{
		$oSvdocsAdminModel = &getAdminModel('svdocs');
		//$oConfig = $oSvdocsAdminModel->getModuleConfig();
		//foreach( $oArgs as $key=>$val)
		//	$oConfig->{$key} = $val;

		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('svdocs', $oArgs);
		return $output;
	}
/**
 * @brief module module
 */
	public function procSvdocsAdminUpdate()
	{
		$this->procSvdocsAdminInsert();
	}
/**
 * @brief add module
 */
	public function procSvdocsAdminInsert()
	{
		$args = Context::getRequestVars();
		if( $args->svdocs_unique_field )
		{
			foreach( $args->svdocs_unique_field as $key=>$val)
				$aUniqueField[$val] = 'on';
		}
		unset($args->svdocs_unique_field );
		$args->svdocs_unique_field = serialize($aUniqueField);

		$args->module = 'svdocs';
		$args->mid = $args->page_name;	//because if mid is empty in context, set start page mid
	
		unset($args->page_name);

		if($args->use_teaser_mode != 'Y') 
			$args->use_teaser_mode = '';

		if($args->is_allow_closed != 'Y') 
			$args->is_allow_closed = '';

		if($args->is_allow_update != 'Y') 
			$args->is_allow_update = '';

		if($args->use_mobile != 'Y') 
			$args->use_mobile = '';
		$output = $this->_saveConfigByMid($args);
		if(!$output->toBool()) 
			return $output;

		$this->add("page", Context::get('page'));
		$this->add('module_srl',$output->get('module_srl'));

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'module_srl', $output->get('module_srl'), 'act', 'dispSvdocsAdminInfo');
		$this->setRedirectUrl($returnUrl);
	}
/**
 * @brief 
 */
	public function procSvdocsAdminApproveApplicant()
	{
		$nModuleSrl = (int)Context::get('module_srl');
		if( !$nModuleSrl )
			return new BaseObject( -1, 'msg_error_invalid_module_srl');

		$nDocSrl = (int)Context::get('doc_srl');
		if( !$nDocSrl )
			return new BaseObject( -1, 'msg_error_invalid_doc_srl');
		
		$args->module_srl = $nModuleSrl;
		$args->doc_srl = $nDocSrl;
		$sApproveCode = Context::get('approve');
		$args->is_accepted = $sApproveCode;

		$output = executeQuery('svdocs.updateSvdocsApproval', $args);
		if(!$output->toBool()) 
			return $output;

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'module_srl', $nModuleSrl,'doc_srl', $nDocSrl, 'act', 'dispSvdocsAdminDocDetail');
		$this->setRedirectUrl($returnUrl);
	}
/**
 * @brief 
 */
	public function procSvdocsAdminInsertApplicant()
	{
		$nModuleSrl = (int)Context::get('module_srl');
		if( !$nModuleSrl )
			return new BaseObject( -1, 'msg_error_invalid_module_srl');
		
		$sApplicantList = Context::get('applicant_registration');
		if( strlen( $sApplicantList)==0 )
			return new BaseObject( -1, 'msg_invalid_applicant_list' );

		$aApplicantList = preg_split('/\n|\r\n?/', $sApplicantList);
		if( count( $aApplicantList ) == 0 )
			return new BaseObject( -1, 'msg_invalid_applicant_list' );
		
		$oSvdocsModel = &getModel('svdocs');
		$oDocInfo = $oSvdocsModel->getDocInfo( $nModuleSrl );
		$oDB = DB::getInstance();

		foreach( $aApplicantList as $key => $val )
		{
			$aApplicantInfo = preg_split('/,/', $val);
			
			$args->module_srl = $nModuleSrl;
			$args->member_srl = (int)$aApplicantInfo[0];
			$args->applicant_name = trim($aApplicantInfo[1]);
			$args->applicant_phone = preg_replace('/\n|\r\n?/', '', $aApplicantInfo[2]);
			$output = executeQuery('svdocs.insertSvdocs', $args);
		}
		$this->add('cleee', 'dsddsf');
	}
/**
 * @brief add module
 */
	private function _saveConfigByMid($oArgs)
	{
		$args = $oArgs;
		if($args->module_srl)
		{
			$oModuleModel = getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			unset($module_info->is_skin_fix); // 기본 스킨 고정을 해제함
			unset($module_info->is_mskin_fix); // 기본 스킨 고정을 해제함
			if($module_info->module_srl != $args->module_srl)
				unset($args->module_srl);
			else
			{
				foreach($args as $key=>$val)
					$module_info->{$key} = $val;
				$args = $module_info;
			}
		}
		$oModuleController = getController('module');
		// Insert/update depending on module_srl

		if(!$args->module_srl)
			$output = $oModuleController->insertModule($args);
		else
			$output = $oModuleController->updateModule($args);
		return $output;
	}
/**
 * @brief update privacy usage term
 */
	function procSvdocsAdminTermPrivacyUsageUpdate()
	{
		$nModuleSrl = Context::get('module_srl');
		if( !$nModuleSrl )
			return new BaseObject(-1, 'msg_invalid_module_srl');

		$sTerm = trim(strip_tags(Context::get('privacy_usage_term')));
		$agreement_file = _XE_PATH_.'files/svdocs/'.$nModuleSrl.'_privacy_usage_term_' . Context::get('lang_type') . '.txt';
		if(!$sTerm)
			FileHandler::removeFile($agreement_file);

		// check agreement value exist
		if($sTerm)
			$output = FileHandler::writeFile($agreement_file, $sTerm);

		$this->setRedirectUrl(Context::get('success_return_url'));
	}
/**
 * @brief update privacy share term
 */
	function procSvdocsAdminTermPrivacyShrUpdate()
	{
		$nModuleSrl = Context::get('module_srl');
		if( !$nModuleSrl )
			return new BaseObject(-1, 'msg_invalid_module_srl');
		
		$bShowTerm = Context::get('is_hide_privacy_shr_term');
		$args->module = 'svdocs';
		$args->mid = Context::get('mid');
		$args->module_srl = $nModuleSrl;
		$args->is_hide_privacy_shr_term = $bShowTerm;
		$output = $this->_saveConfigByMid($args);
		if(!$output->toBool()) 
			return $output;

		$sTerm = trim(strip_tags(Context::get('privacy_shr_term')));
		$agreement_file = _XE_PATH_.'files/svdocs/'.$nModuleSrl.'_privacy_shr_term_' . Context::get('lang_type') . '.txt';
		if(!$sTerm)
			FileHandler::removeFile($agreement_file);

		// check agreement value exist
		if($sTerm)
			$output = FileHandler::writeFile($agreement_file, $sTerm);

		$this->setRedirectUrl(Context::get('success_return_url'));
	}
/**
 * @brief mask multibyte string
 * param 원본문자열, 마스킹하지 않는 전단부 글자수, 마스킹하지 않는 후단부 글자수, 마스킹 마크 최대 표시수, 마스킹마크
 * echo _maskMbString('abc12234pro', 3, 2); => abc******ro
 */	
	private function _maskMbString($str, $len1, $len2=0, $limit=0, $mark='*')
	{
		$arr_str = preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
		$str_len = count($arr_str);

		$len1 = abs($len1);
		$len2 = abs($len2);
		if($str_len <= ($len1 + $len2))
			return $str;

		$str_head = '';
		$str_body = '';
		$str_tail = '';

		$str_head = join('', array_slice($arr_str, 0, $len1));
		if($len2 > 0)
			$str_tail = join('', array_slice($arr_str, $len2 * -1));

		$arr_body = array_slice($arr_str, $len1, ($str_len - $len1 - $len2));

		if(!empty($arr_body)) 
		{
			$len_body = count($arr_body);
			$limit = abs($limit);

			if($limit > 0 && $len_body > $limit)
				$len_body = $limit;

			$str_body = str_pad('', $len_body, $mark);
		}

		return $str_head.$str_body.$str_tail;
	}
/**
 * @brief 
 */	
	function procSvdocsAdminCSVDownloadByModule() 
	{
		$nModuleSrl = Context::get('module_srl');
		if(!$nModuleSrl) 
			return new BaseObject(-1, 'msg_invalid_module_srl');
		
		//header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'Content-Type: Application/octet-stream; charset=UTF-8' );
		header( "Content-Disposition: attachment; filename=\"svdocs_raw-".date('Ymd').".csv\"");
		echo chr( hexdec( 'EF' ) );
		echo chr( hexdec( 'BB' ) );
		echo chr( hexdec( 'BF' ) );
		
		// 기본 컬럼 제목 설정 시작
		$oDataConfig = Array( 'doc_srl','module_srl','member_srl','applicant_name','applicant_name_secured','applicant_phone','applicant_phone_secured','ipaddress','privacy_collection','privacy_sharing','user_agent','datetimestamp_entry','datetimestamp_final','duration_sec','init_referral','utm_source', 'utm_medium', 'utm_campaign', 'utm_term','is_mobile','is_accepted','regdate','member_id' );

		foreach( $oDataConfig as $key => $val )
			echo "\"".$val."\",";
		// 기본 컬럼 제목 설정 끝
		
		// extra_vars의 컬럼 제목 설정 시작
		$oSvdocsModel = getModel('svdocs');
		$extra_keys = $oSvdocsModel->getExtraKeys($nModuleSrl);
		
		$aAddrEid = array();
		$aCheckBoxEid = array();
		$aCheckBoxAnswers = array();
		$aTempAddrValue = array();
		$nPhoneNumberLegnth = 0;
		foreach( $extra_keys as $key=>$val)
		{
			switch( $val->type )
			{
				case 'kr_zip':
					echo "\"post_code\",\"address\",";
					$aAddrEid[] = $val->eid;
					break;
				case 'checkbox':
					$aMultipleAnswer = explode( ',', $val->default);
					foreach( $aMultipleAnswer as $key1=>$val1)
						echo "\"".$val->name.'_'.$val1."\",";

					$aCheckBoxAnswers[$val->eid] = $aMultipleAnswer;
					$aCheckBoxEid[] = $val->eid;
					break;
				default:
					echo "\"".$val->name."\",";
					break;
			}
		}
		// extra_vars의 컬럼 제목 설정 끝
		// svauth 모듈이 있다면 실명인증 정보 추출
		if( getClass('svauth') )
		{
			$oSvauthAdminModel = getAdminModel('svauth');
			$oSvauthDataConfig = Array( '인증일시', '인증실명','인증생일','인증성별','인증국적','인증통신사','인증핸드폰' );
			if( getClass('svcrm') )
			{
				$oSvcrmAdminModel = &getAdminModel('svcrm');
				$oSvcrmConfig = $oSvcrmAdminModel->getModuleConfig();
				$aPrivacyAccessPolicy = $oSvcrmConfig->privacy_access_policy;
				unset($oSvcrmConfig);
			}
		}
		// svauth 컬렘 제목 설정 시작
		foreach( $oSvauthDataConfig as $key => $val )
			echo "\"".$val."\",";
		// svauth 컬렘 제목 설정 끝

		echo "\r\n";
		$oMemberModel = &getModel('member');
		$oSvdocsAdminModel = getAdminModel('svdocs');

		$args->module_srl = $nModuleSrl;
		$args->is_deleted_doc = 0;
		$args->is_accepted = 'Y';
		$args->list_count = 99999;
		$output = executeQueryArray('svdocs.getAdminSvdocsByModule', $args);
		if( !$output->toBool() )
			return $output;

		$data = $output->data;

		// extra_vars의 컬럼 데이터 설정
		if( count( $data ) )
		{
			$to_time = 0;
			$from_time = 0;

			foreach( $data as $key1 => $val1 )
			{
				$nMemberSrl = (int)$val1->member_srl;
				$nDocSrl = (int)$val1->doc_srl;
				foreach( $val1 as $key2 => $val2 )
				{
					if( $key2 == 'is_deleted_doc' )
						continue;

					if( $key2 == 'datetimestamp_entry' )
						$to_time = strtotime( $val2 );
						
					if( $key2 == 'datetimestamp_final' )
					{
						echo "\"".$val2."\",";
						$from_time = strtotime( $val2 );
						$duration_sec = round(abs($to_time - $from_time), 2 );
						echo "\"".$duration_sec."\",";
						continue;
					}

					if( $key2 == 'applicant_name' )
						echo "\"".$val2."\",\"".$this->_maskMbString($val2,1,1)."\",";
					else if( $key2 == 'applicant_phone' )
					{
						$nPhoneNumberLegnth = strlen( $val2 );
						switch( $nPhoneNumberLegnth )
						{
							case 10:
								echo "\"".substr($val2, 0, 3).'-'.substr($val2, 3, 3).'-'.substr($val2, 6, 4)."\","; // original number
								echo "\"".$this->_maskMbString(substr($val2, 0, 3),2,0).'-'.$this->_maskMbString(substr($val2, 3, 3),0,0).'-'.substr($val2, 6, 4)."\","; // masked number
								break;
							case 11:
								echo "\"".substr($val2, 0, 3).'-'.substr($val2, 3, 4).'-'.substr($val2, 7, 4)."\","; // original number
								echo "\"".$this->_maskMbString(substr($val2, 0, 3),2,0).'-'.$this->_maskMbString(substr($val2, 3, 4),0,0).'-'.substr($val2, 7, 4)."\","; // masked number
								break;
							default:
								echo "\"".$val2."\","; // original number
								echo "\"".$this->_maskMbString($val2,2,4)."\","; // masked number
						}
					}
					else if( $key2 == 'regdate' )
					{
						$dtTemp = strtotime($val2);
						echo "\"". date("Y-m-d H:i:s",$dtTemp)."\",";
					}
					else
						echo "\"".$val2."\",";
				}

				// member_id
				$oMemberInfo = $oMemberModel->getMemberInfoByMemberSrl($nMemberSrl, 0, $columnList);
				if($oMemberInfo)
					echo "\"".$oMemberInfo->user_id."\",";
				else
					echo "\"withdraw\",";
				// extra_vars
				$aExtraVars = $oSvdocsAdminModel->getDocExtraVars($nModuleSrl, $nDocSrl);
				
				foreach( $aExtraVars as $key=>$val)
				{
					if (in_array($val->eid, $aAddrEid)) 
					{
						$aTempAddrValue = explode( "|@|", $val->value );
						echo "\"".$aTempAddrValue[0]."\",\"".$aTempAddrValue[1]." ".$aTempAddrValue[2]." ".$aTempAddrValue[3]."\",";
					}
					else if (in_array($val->eid, $aCheckBoxEid)) 
					{
						$sMultipleChoices = null;
						$aTempCbValue = explode( "|@|", $val->value );
						$naTempCbValue = count( $aTempCbValue );
						foreach( $aCheckBoxAnswers[$val->eid] as $key1 => $val1 )
						{
							if (in_array($val1, $aTempCbValue))
								$sMultipleChoices .= "1,";
							else
							{
								$sLastElem = $aTempCbValue[$naTempCbValue -1];
								if( $val1 == '기타' && strlen($sLastElem) && !in_array($sLastElem, $aCheckBoxAnswers[$val->eid]) )
									$sMultipleChoices .= $sLastElem.",";
								else
									$sMultipleChoices .= "0,";
							}
						}
						echo $sMultipleChoices;
					}
					else
						echo "\"".$val->value."\",";
				}

				if( $oSvauthAdminModel )
				{
					$oSvauthData = $oSvauthAdminModel->getMemberAuthInfo($nMemberSrl,$aPrivacyAccessPolicy);
					foreach( $oSvauthDataConfig as $authkey=>$authval)
					{
						if( $authval == '인증일시' )
						{
							if( $oSvauthData->{$authval} == NULL )
								echo "\"\",";
							else
							{
								$dtTemp = strtotime($oSvauthData->{$authval});
								echo "\"". date("Y-m-d H:i:s",$dtTemp)."\",";
							}
						}
						else if( $authval == '인증생일' )
						{
							if( $oSvauthData->{$authval} == NULL )
								echo "\"\",";
							else if( $oSvauthData->{$authval} == '열람권한없음' )
								echo "\"".$oSvauthData->{$authval}."\",";
							else
							{
								$dtTemp = strtotime($oSvauthData->{$authval});
								echo "\"". date("Y-m-d",$dtTemp)."\",";
							}
						}
						else if(  $authval == '인증핸드폰' )
						{
							$sAuthPhoneNumber = $oSvauthData->{$authval};
							$nPhoneNumberLegnth = strlen( $sAuthPhoneNumber );
							switch( $nPhoneNumberLegnth )
							{
								case 10:
									echo "\"".substr($sAuthPhoneNumber, 0, 3).'-'.substr($sAuthPhoneNumber, 3, 3).'-'.substr($sAuthPhoneNumber, 6, 4)."\",";
									break;
								case 11:
									echo "\"".substr($sAuthPhoneNumber, 0, 3).'-'.substr($sAuthPhoneNumber, 3, 4).'-'.substr($sAuthPhoneNumber, 7, 4)."\",";
									break;
								default:
									echo "\"".$sAuthPhoneNumber."\",";
							}
						}
						else
							echo "\"".$oSvauthData->{$authval}."\",";
					}
				}
				echo "\r\n";
			}
		}
		exit(0);
	}
/**
 * Add or modify extra variables of the module 
 * document 모듈에 종속됨
 * @return void|object
 */
	function procSvdocsAdminInsertExtraVar()
	{
		$module_srl = Context::get('module_srl');
		$var_idx = Context::get('var_idx');
		$name = Context::get('name');
		$type = Context::get('type');
		$is_required = Context::get('is_required');
		$default = Context::get('default');
		$desc = Context::get('desc') ? Context::get('desc') : '';
		$search = Context::get('search');
		$eid = Context::get('eid');
		$obj = new stdClass();

		if(!$module_srl || !$name || !$eid) return new BaseObject(-1,'msg_invalid_request');
		// set the max value if idx is not specified
		if(!$var_idx)
		{
			$obj->module_srl = $module_srl;
			$output = executeQuery('document.getDocumentMaxExtraKeyIdx', $obj);
			$var_idx = $output->data->var_idx+1;
		}

		// Check if the module name already exists
		$obj->module_srl = $module_srl;
		$obj->var_idx = $var_idx;
		$obj->eid = $eid;
		$output = executeQuery('document.isExistsExtraKey', $obj);
		if(!$output->toBool() || $output->data->count)
		{
			return new BaseObject(-1, 'msg_extra_name_exists');
		}

		// insert or update
		$oSvdocsController = getController('svdocs');
		$output = $oSvdocsController->insertSvdocsExtraKey($module_srl, $var_idx, $name, $type, $is_required, $search, $default, $desc, $eid);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_registed');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispDocumentAdminAlias', 'document_srl', $args->document_srl);
		$this->setRedirectUrl($returnUrl);
	}
/**
 * Delete extra variables of the module
 * @return void|object
 */
	function procSvdocsAdminDeleteExtraVar()
	{
		$module_srl = Context::get('module_srl');
		$var_idx = Context::get('var_idx');
		if(!$module_srl || !$var_idx) return new BaseObject(-1,'msg_invalid_request');

		$oSvdocsController = getController('svdocs');
		$output = $oSvdocsController->deleteSvdocsExtraKeys($module_srl, $var_idx);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_deleted');
	}
/**
 * Control the order of extra variables
 * @return void|object
 */
	function procSvdocsAdminMoveExtraVar()
	{
		$type = Context::get('type');
		$module_srl = Context::get('module_srl');
		$var_idx = Context::get('var_idx');

		if(!$type || !$module_srl || !$var_idx) return new BaseObject(-1,'msg_invalid_request');

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		if(!$module_info->module_srl) return new BaseObject(-1,'msg_invalid_request');
		
		$oSvdocsModel = getModel('svdocs');
		$extra_keys = $oSvdocsModel->getExtraKeys($module_srl);
		//$oDocumentModel = getModel('document');
		//$extra_keys = $oDocumentModel->getExtraKeys($module_srl);
		if(!$extra_keys[$var_idx]) return new BaseObject(-1,'msg_invalid_request');

		if($type == 'up') $new_idx = $var_idx-1;
		else $new_idx = $var_idx+1;
		if($new_idx<1) return new BaseObject(-1,'msg_invalid_request');

		$args = new stdClass();
		$args->module_srl = $module_srl;
		$args->var_idx = $new_idx;
		$output = executeQuery('document.getDocumentExtraKeys', $args);
		if (!$output->toBool()) return $output;
		if (!$output->data) return new BaseObject(-1, 'msg_invalid_request');
		unset($args);

		// update immediately if there is no idx to change
		if(!$extra_keys[$new_idx])
		{
			$args = new stdClass();
			$args->module_srl = $module_srl;
			$args->var_idx = $var_idx;
			$args->new_idx = $new_idx;
			$output = executeQuery('document.updateDocumentExtraKeyIdx', $args);
			if(!$output->toBool()) return $output;
			$output = executeQuery('document.updateDocumentExtraVarIdx', $args);
			if(!$output->toBool()) return $output;
			// replace if exists
		}
		else
		{
			$args = new stdClass();
			$args->module_srl = $module_srl;
			$args->var_idx = $new_idx;
			$args->new_idx = -10000;
			$output = executeQuery('document.updateDocumentExtraKeyIdx', $args);
			if(!$output->toBool()) return $output;
			$output = executeQuery('document.updateDocumentExtraVarIdx', $args);
			if(!$output->toBool()) return $output;

			$args->var_idx = $var_idx;
			$args->new_idx = $new_idx;
			$output = executeQuery('document.updateDocumentExtraKeyIdx', $args);
			if(!$output->toBool()) return $output;
			$output = executeQuery('document.updateDocumentExtraVarIdx', $args);
			if(!$output->toBool()) return $output;

			$args->var_idx = -10000;
			$args->new_idx = $var_idx;
			$output = executeQuery('document.updateDocumentExtraKeyIdx', $args);
			if(!$output->toBool()) return $output;
			$output = executeQuery('document.updateDocumentExtraVarIdx', $args);
			if(!$output->toBool()) return $output;
		}

		$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheHandler->isSupport())
		{
			$object_key = 'module_svdocs_extra_keys:'.$module_srl;
			$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
			$oCacheHandler->delete($cache_key);
		}
	}
}