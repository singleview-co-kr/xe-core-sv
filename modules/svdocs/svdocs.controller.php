<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svdocsController
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsController
**/ 
//require_once(_XE_PATH_.'modules/svdocs/svdocs.cookie.php');
class svdocsController extends svdocs
{
/**
 * @brief initialization
 **/
	function init()
	{
	}
/**
 * @brief validate phone number
 **/
	function procSvdocsSetAuthCode()
	{
		$nModuleSrl = (int)Context::get('module_srl');
		if( !$nModuleSrl )
			return new BaseObject(-1, 'msg_invalid_module_srl');
		
		$oSvdocsModel = &getModel('svdocs');
		$oDocInfo = $oSvdocsModel->getDocInfo( $nModuleSrl );

		if( $oDocInfo->nRemainingApplicants == 0 )
			return new BaseObject(-1, 'msg_application_closed');
		
		$phonenum = Context::get('applicant_phone_number');
		if(preg_match('/[^0-9]/i', $phonenum))
			return new BaseObject(-1, '숫자만 입력 가능합니다.');
		
		if(!$phonenum)
			return new BaseObject(-1, '휴대폰 번호를 바르게 입력해주세요.');
		
		Context::set('phone_number', $phonenum);
		
		// 모듈 설정 정보에서 svauth plugin 입력되어 있으면 svauth 호출
		$nSvauthPluginSrl = (int)$oDocInfo->svauth_plugin_srl;
		if( $nSvauthPluginSrl )
		{
			Context::set('plugin_srl', $nSvauthPluginSrl);
			$oSvauthController = &getController('svauth');
			$output = $oSvauthController->procSvauthSetAuthCode($nModuleSrl);
			if(!$output->toBool()) 
				return new BaseObject(-1, $output->message);
			$this->setMessage($output->message );
		}
		else
			$this->setMessage('no_svauth_plugin_defined');
	}
	
/**
 * @brief 기존 응모 변경 메소드
 **/
	function procSvdocsUpdate()
	{
		$nModuleSrl = (int)Context::get('module_srl');
		if( !$nModuleSrl )
			return new BaseObject( -1, 'msg_error_invalid_module_srl');

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl( $nModuleSrl );

		if($module_info->is_allow_update != 'Y' )
			return new BaseObject(-1, '정보 변경이 금지되어있습니다.');

		$nSvdocsSrl = (int)Context::get('svdocs_srl');
		if( !$nSvdocsSrl )
			return new BaseObject( -1, 'msg_error_invalid_svdocs_srl');

		// 회원 번호 중복 방지 검사
		$logged_info = Context::get('logged_info');
		if( !$logged_info )
		{
//var_dump( $_COOKIE );
			 //if( $_COOKIE['svdocs_guest_permission'] != $nSvdocsSrl ) //svdocs.view.php::dispSvdocsGuestDetail()에서 설정한 쿠키
				//return new BaseObject( -1, 'msg_error_not_allowed_svdocs_srl');
		}
		
		// valudate mandatory extra vars -> procSvdocsRegistration 공통 영역 시작
		$oSvdocsModel = getModel('svdocs');
		$extra_keys = $oSvdocsModel->getExtraKeys($nModuleSrl);
		if(count($extra_keys))
		{
			$oExtraVars = Context::getRequestVars();
			foreach($extra_keys as $idx => $extra_item)
			{
				if( $extra_item->type == 'hidden' )
					continue;

				if( $extra_item->is_required == 'Y' )
				{
					if( $oDocInfo->svdocs_unique_field[$extra_item->eid] ) // 가상 unique field로 설정 사용자 정의 변수 검사
					{
						$oExtArgs->module_srl = $nModuleSrl;
						$oExtArgs->eid = $extra_item->eid;
						$oExtArgs->value = $oExtraVars->{'extra_vars'.$idx};
						$output = executeQueryArray('svdocs.getDocByExtraVarEid', $oExtArgs);
						if(!$output->toBool() )
							return $output;
						if( count($output->data) > 0 )
							return new BaseObject(-1, sprintf(Context::getLang('msg_value_must_be_unique'), $extra_item->name));
					}

					if( $extra_item->type == 'kr_zip' ) // 주소는 항상 배열로 들어오기 때문에 빈값이어도 isset은 항상 true임
					{
						foreach( $oExtraVars->{'extra_vars'.$idx} as $key=>$val)
						{
							if( strlen( strip_tags( trim($val) ) ) == 0 )
								return new BaseObject(-1, sprintf(Context::getLang('msg_value_must_be_filled'), $extra_item->name));
						}
					}
					else
					{
						if(!isset($oExtraVars->{'extra_vars'.$idx}))
							return new BaseObject(-1, sprintf(Context::getLang('msg_value_must_be_filled'), $extra_item->name));
					}
				}
			}
		}
		// procSvdocsRegistration 공통 영역 종료

		// begin transaction
		//$oDB = DB::getInstance();
		//$oDB->begin();

		// Insert extra variables if the document successfully inserted.
		if(count($extra_keys))
		{
			foreach($extra_keys as $idx => $extra_item)
			{
				$value = NULL;
				if(isset($oExtraVars->{'extra_vars'.$idx}))
				{
					$tmp = $oExtraVars->{'extra_vars'.$idx};
					if(is_array($tmp))
						$value = implode('|@|', $tmp);
					else
						$value = trim($tmp);
				}
				else if(isset($oExtraVars->{$extra_item->name})) 
					$value = trim($oExtraVars->{$extra_item->name});
				
				if( $extra_item->type == 'text' || $extra_item->type == 'textarea' )
					$value = strip_tags($value);

				$args = new stdClass();
				$args->module_srl = $nModuleSrl;
				$args->doc_srl = $nSvdocsSrl;
				$args->eid = $extra_item->eid;
				
				if($value == NULL) 
				{
					if( $extra_item->type != 'hidden' )
						$output = executeQueryArray('svdocs.deleteExtraVar', $args);
				}
				else
				{
					$output = executeQueryArray('svdocs.getExtraVar', $args);
					if( $output->data[0]->count ) 
						$this->_updateExtraVar($nModuleSrl, $nSvdocsSrl, $idx, $value, $extra_item->eid);
					else
						$this->_insertExtraVar($nModuleSrl, $nSvdocsSrl, $idx, $value, $extra_item->eid);
				}
			}
		}
		// commit
		//$oDB->commit();
		//if( (int)$module_info->duplicate_restriction_sec )
		//	$oCookie->setRestricted( (int)$module_info->duplicate_restriction_sec );
		$this->add('result', 'success');
	}
/**
 * @brief 신규 응모 추가 메소드
 **/
	function procSvdocsRegistration()
	{
		$nModuleSrl = (int)Context::get('module_srl');
		if( !$nModuleSrl )
			return new BaseObject( -1, 'msg_error_invalid_module_srl');

		$oSvdocsModel = &getModel('svdocs');
		$oDocInfo = $oSvdocsModel->getDocInfo( $nModuleSrl );

		$nOpenTimestamp = strtotime( $oDocInfo->timeOpendatetime);
		if( time() < $nOpenTimestamp )
			return new BaseObject(1, sprintf(Context::getLang('msg_svodcs_not_opened_yet'), date('Y-m-d h:i:s', $nOpenTimestamp) )); 

		// 회원 번호 중복 방지 검사
		$logged_info = Context::get('logged_info');
		if( $oDocInfo->svdocs_unique_field[member_srl] == 'on' && $logged_info->member_srl )
		{
			$args->member_srl = $logged_info->member_srl;
			$args->module_srl = $nModuleSrl;
			$output = executeQueryArray('svdocs.getDocByMemberSrl', $args);
			if(!$output->toBool() )
				return $output;
			
			if( count($output->data) > 0 )
				return new BaseObject( -1, 'msg_already_registered');
		}

		unset($args); 

		if( $oDocInfo->nRemainingApplicants == 0 )
			return new BaseObject( -1, 'msg_application_closed');

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl( $nModuleSrl );
		
		/*$oCookie = new svdocsCookie( $nModuleSrl );
		if( (int)$module_info->duplicate_restriction_sec )
		{
			if( $oCookie->isRestricted() )
			{
				$oCookie->setRestricted( (int)$module_info->duplicate_restriction_sec );
				return new BaseObject(-1, 'msg_already_registered');
			}
		}*/
		$args->module_srl = Context::get('module_srl');
		
		// valudate mandatory extra vars  -> procSvdocsUpdate 공통 영역 시작
		//$oSvdocsModel = getModel('svdocs');
		$extra_keys = $oSvdocsModel->getExtraKeys($args->module_srl);
		if(count($extra_keys))
		{
			$oExtraVars = Context::getRequestVars();
debugPrint( $oExtraVars );
			foreach($extra_keys as $idx => $extra_item)
			{
				if( $extra_item->is_required == 'Y' )
				{
					if( $oDocInfo->svdocs_unique_field[$extra_item->eid] ) // 가상 unique field로 설정 사용자 정의 변수 검사
					{
						$oExtArgs->module_srl = $nModuleSrl;
						$oExtArgs->eid = $extra_item->eid;
						$oExtArgs->value = $oExtraVars->{'extra_vars'.$idx};
						$output = executeQueryArray('svdocs.getDocByExtraVarEid', $oExtArgs);
						if(!$output->toBool() )
							return $output;
						if( count($output->data) > 0 )
							return new BaseObject(-1, sprintf(Context::getLang('msg_value_must_be_unique'), $extra_item->name));
					}

					if( $extra_item->type == 'kr_zip' ) // 주소는 항상 배열로 들어오기 때문에 빈값이어도 isset은 항상 true임
					{
						foreach( $oExtraVars->{'extra_vars'.$idx} as $key=>$val)
						{
							if( strlen( strip_tags( trim($val) ) ) == 0 )
								return new BaseObject(-1, sprintf(Context::getLang('msg_value_must_be_filled'), $extra_item->name));
						}
					}
					else
					{
						if(!isset($oExtraVars->{'extra_vars'.$idx}))
							return new BaseObject(-1, sprintf(Context::getLang('msg_value_must_be_filled'), $extra_item->name));
					}
				}
			}
		}
		//  -> procSvdocsUpdate 공통 영역 종료

		$sApplicantName = strip_tags( trim( Context::get('applicant_name') ) );
		if ( strlen( $sApplicantName ) == 0 && !$logged_info )
			return new BaseObject(-1, 'msg_no_applicant_name');
		else
			$args->applicant_name = $sApplicantName;
		
		if( $logged_info )
			$args->member_srl = $logged_info->member_srl;

		// 모듈 설정 정보에서 svauth plugin 입력되어 있으면 svauth 호출
		$nSvauthPluginSrl = (int)$module_info->svauth_plugin_srl;
		if( $nSvauthPluginSrl )
		{
			$sApplicantPhoneNumber = str_replace( '-', '', strip_tags( trim( Context::get('applicant_phone_number') ) ) );
			if ( strlen( $sApplicantPhoneNumber ) == 0 )
				return new BaseObject(-1, 'msg_no_applicant_phone_number');

			if( $oDocInfo->svdocs_unique_field[applicant_phone] == 'on' )
			{
				$args->applicant_phone = $sApplicantPhoneNumber;
				$args->module_srl = $nModuleSrl;
				$output = executeQueryArray('svdocs.getDocByApplicantPhone', $args);
				if(!$output->toBool() )
					return $output;
				
				if( count($output->data) > 0 )
					return new BaseObject( -1, 'msg_already_registered');
			}

			Context::set('plugin_srl', $nSvauthPluginSrl);
			Context::set('phone_number', $sApplicantPhoneNumber);
			$oSvauthController = &getController('svauth');
			$output = $oSvauthController->procSvauthValidateAuthCode();
			if(!$output->toBool()) 
				return new BaseObject(-1, $output->message);
			
			$args->applicant_phone = $sApplicantPhoneNumber;
		}
		
		$bPrivacyCollectionAgreement = 0;
		if( Context::get('privacy_collection') == 1 )
			$bPrivacyCollectionAgreement = 1;
		$bPrivacySharingAgreement = 0;
		if( Context::get('privacy_sharing') == 1 )
			$bPrivacySharingAgreement = 1;

		$args->datetimestamp_entry = Context::get('timestamp_entry');
		$args->datetimestamp_final = Context::get('timestamp_final');
		$args->is_mobile = Mobile::isMobileCheckByAgent() ? 'Y' : 'N';
		$args->user_agent = $_SERVER['HTTP_USER_AGENT'];
		
		// /addons/svtracker에 의존
		$sValue = $this->_getSessionValue('HTTP_INIT_REFERER' );
		if( !is_null( $sValue ) )
			$args->init_referral = $sValue;

		$sValue = $this->_getSessionValue('HTTP_INIT_SOURCE' );
		if( !is_null( $sValue ) )
			$args->utm_source = $sValue;

		$sValue = $this->_getSessionValue('HTTP_INIT_MEDIUM' );
		if( !is_null( $sValue ) )
			$args->utm_medium = $sValue;

		$sValue = $this->_getSessionValue('HTTP_INIT_CAMPAIGN' );
		if( !is_null( $sValue ) )
			$args->utm_campaign = $sValue;

		$sValue = $this->_getSessionValue('HTTP_INIT_KEYWORD' );
		if( !is_null( $sValue ) )
			$args->utm_term = $sValue;

		$args->privacy_collection = $bPrivacyCollectionAgreement;
		$args->privacy_sharing = $bPrivacySharingAgreement;

		// begin transaction
		$oDB = DB::getInstance();
		//$oDB->begin();
		$output = executeQuery('svdocs.insertSvdocs', $args);
		if(!$output->toBool())
		{
			//$oDB->rollback();
			return $output;
		}
		$nSvdocSrl = $oDB->db_insert_id();
		// Insert extra variables if the document successfully inserted.
		if(count($extra_keys))
		{
			foreach($extra_keys as $idx => $extra_item)
			{
				$value = NULL;
				if(isset($oExtraVars->{'extra_vars'.$idx}))
				{
					$tmp = $oExtraVars->{'extra_vars'.$idx};
					if(is_array($tmp))
						$value = implode('|@|', $tmp);
					else
						$value = trim($tmp);
				}
				else if(isset($oExtraVars->{$extra_item->name})) 
					$value = trim($oExtraVars->{$extra_item->name});
				else // 일시적으로 사용자 정의 변수를 숨기려고 hidden type으로 설정하고 값이 입력되지 않는 경우 대응
					$value = '';
				
				if( $extra_item->type == 'text' || $extra_item->type == 'textarea' )
					$value = strip_tags($value);

				//if($value == NULL) 
				//	continue;  다운로드시 값이 없는 사용자 정의 필드가 CSV 정렬을 손상하지 않아야 함
				$this->_insertExtraVar($args->module_srl, $nSvdocSrl, $idx, $value, $extra_item->eid);
			}
		}

		// commit
		//$oDB->commit();
		//if( (int)$module_info->duplicate_restriction_sec )
		//	$oCookie->setRestricted( (int)$module_info->duplicate_restriction_sec );

		$this->add('cleee', 'dsddsf');
	}
/**
 * @brief 슬라이드 교육 완료 인증
 **/
	function procSvdocsCertified()
	{
		$nModuleSrl = (int)Context::get('module_srl');
		if( !$nModuleSrl )
			return new BaseObject( -1, 'msg_error_invalid_module_srl');

		$oSvdocsModel = &getModel('svdocs');
		$oDocInfo = $oSvdocsModel->getDocInfo( $nModuleSrl );
		unset($oSvdocsModel);

		$nOpenTimestamp = strtotime( $oDocInfo->timeOpendatetime);
		if( time() < $nOpenTimestamp )
			return new BaseObject(1, sprintf(Context::getLang('msg_svodcs_not_opened_yet'), date('Y-m-d h:i:s', $nOpenTimestamp) )); 

		// 회원 번호 중복 방지 검사
		$oLoggedInfo = Context::get('logged_info');
		if( $oDocInfo->svdocs_unique_field[member_srl] == 'on' && $oLoggedInfo->member_srl )
		{
			$oArgs->member_srl = $oLoggedInfo->member_srl;
			$oArgs->module_srl = $nModuleSrl;
			$oRst = executeQueryArray('svdocs.getDocByMemberSrl', $oArgs);
			unset($oArgs);
			if(!$oRst->toBool() )
				return $oRst;
			
			if( count($oRst->data) > 0 )
				return new BaseObject( -1, 'msg_already_registered');
		}

		if( $oDocInfo->nRemainingApplicants == 0 )
			return new BaseObject( -1, 'msg_application_closed');

		if( $oLoggedInfo )
			$oArgs->member_srl = $oLoggedInfo->member_srl;

		$oArgs->module_srl = Context::get('module_srl');
		$oArgs->datetimestamp_entry = Context::get('timestamp_entry');
		$oArgs->datetimestamp_final = Context::get('timestamp_final');
		$oArgs->is_mobile = Mobile::isMobileCheckByAgent() ? 'Y' : 'N';
		$oArgs->user_agent = $_SERVER['HTTP_USER_AGENT'];
		$sCertifiedId = $this->_generateSerialNumber(); //////////////////////
		$oArgs->certify_id = $sCertifiedId;///////////////
		$oRst = executeQuery('svdocs.insertSvdocs', $oArgs);
		if(!$oRst->toBool())
			return $oRst;
		unset($oRst);
		unset($oLoggedInfo);
		unset($oArgs);

		$oModuleModel = &getModel('module');
		$aSkinVars = $oModuleModel->getModuleSkinVars($nModuleSrl);
		unset($oModuleModel);
		
		$sAuthSessionVarName = $aSkinVars['auth_session_var_name']->value;
		if( $sAuthSessionVarName )
			$_SESSION[$sAuthSessionVarName] = $sCertifiedId;//true; /////////////////////
		$this->add('cleee', 'dsddsf');
	}
/**
 * ./svdocs.admin.controller.php::procSvdocsAdminInsertExtraVar()에서 호출
 * Insert extra variables into the document table
 * @param int $module_srl
 * @param int $var_idx
 * @param string $var_name
 * @param string $var_type
 * @param string $var_is_required
 * @param string $var_search
 * @param string $var_default
 * @param string $var_desc
 * @param int $eid
 * @return object
 */
	public function insertSvdocsExtraKey($module_srl, $var_idx, $var_name, $var_type, $var_is_required = 'N', $var_search = 'N', $var_default = '', $var_desc = '', $eid)
	{
		if(!$module_srl || !$var_idx || !$var_name || !$var_type || !$eid) return new BaseObject(-1,'msg_invalid_request');

		$obj = new stdClass();
		$obj->module_srl = $module_srl;
		$obj->var_idx = $var_idx;
		$obj->var_name = $var_name;
		$obj->var_type = $var_type;
		$obj->var_is_required = $var_is_required=='Y'?'Y':'N';
		$obj->var_search = $var_search=='Y'?'Y':'N';
		$obj->var_default = $var_default;
		$obj->var_desc = $var_desc;
		$obj->eid = $eid;

		$output = executeQuery('document.getDocumentExtraKeys', $obj);
		if(!$output->data)
			$output = executeQuery('document.insertDocumentExtraKey', $obj);
		else
		{
			$output = executeQuery('document.updateDocumentExtraKey', $obj);
			// Update the extra var(eid)
			$output = executeQuery('document.updateDocumentExtraVar', $obj);
		}

		$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheHandler->isSupport())
		{
			$object_key = 'module_svdocs_extra_keys:'.$module_srl;
			$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
			$oCacheHandler->delete($cache_key);
		}
		return $output;
	}
/**
 * ./svdocs.admin.controller.php::procSvdocsAdminDeleteExtraVar()에서 호출
 * Remove the extra variables of the documents
 * @param int $module_srl
 * @param int $var_idx
 * @return Object
 */
	function deleteSvdocsExtraKeys($module_srl, $var_idx = null)
	{
		if(!$module_srl) return new BaseObject(-1,'msg_invalid_request');
		$obj = new stdClass();
		$obj->module_srl = $module_srl;
		if(!is_null($var_idx)) $obj->var_idx = $var_idx;

		$oDB = DB::getInstance();
		$oDB->begin();

		$output = $oDB->executeQuery('document.deleteDocumentExtraKeys', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		if($var_idx != NULL)
		{
			$output = $oDB->executeQuery('document.updateDocumentExtraKeyIdxOrder', $obj);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		$output =  executeQuery('document.deleteDocumentExtraVars', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		if($var_idx != NULL)
		{
			$output = $oDB->executeQuery('document.updateDocumentExtraVarIdxOrder', $obj);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		$oDB->commit();

		$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheHandler->isSupport())
		{
			$object_key = 'module_svdocs_extra_keys:'.$module_srl;
			$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
			$oCacheHandler->delete($cache_key);
		}

		return new BaseObject();
	}
/**
 * 사용자 정의 변수 추가 기능은 document 모듈에 의존하고, HTML form 작성은 svdocs model에서 재정의함
 * Insert extra vaiable to the documents table
 * @param int $module_srl
 * @param int $document_srl
 * @param int $var_idx
 * @param mixed $value
 * @param int $eid
 * @param string $lang_code
 * @return Object|void
 */
	private function _insertExtraVar($module_srl, $svdoc_srl, $var_idx, $value, $eid = null, $lang_code = '')
	{
		if(!$module_srl || !$svdoc_srl || !$var_idx || !isset($value)) return new BaseObject(-1,'msg_invalid_request');
		if(!$lang_code) $lang_code = Context::getLangType();

		$obj = new stdClass;
		$obj->module_srl = $module_srl;
		$obj->doc_srl = $svdoc_srl;
		$obj->var_idx = $var_idx;
		$obj->value = $value;
		$obj->lang_code = $lang_code;
		$obj->eid = $eid;
		$output = executeQuery('svdocs.insertExtraVar', $obj);
	}
/**
 * 사용자 정의 변수 추가 기능은 document 모듈에 의존하고, HTML form 작성은 svdocs model에서 재정의함
 */
	private function _updateExtraVar($module_srl, $svdoc_srl, $var_idx, $value, $eid = null, $lang_code = '')
	{
		if(!$module_srl || !$svdoc_srl || !$var_idx || !isset($value)) return new BaseObject(-1,'msg_invalid_request');
		if(!$lang_code) $lang_code = Context::getLangType();

		$obj = new stdClass;
		$obj->module_srl = $module_srl;
		$obj->doc_srl = $svdoc_srl;
		$obj->value = $value;
		$obj->eid = $eid;
		$output = executeQuery('svdocs.updateExtraVar', $obj);
	}
/**
 * @brief session에 기록된 UTM 값을 가져옴
 **/
	private function _getSessionValue( $sSessionName )
	{
		$sSessionName = trim( $sSessionName );
		$sSessionValue = null;
		if( strlen( $sSessionName ) > 0 )
			$sSessionValue = $_SESSION[$sSessionName];

		return $sSessionValue;
	}
/**
 * @brief $_SESSION[$sAuthSessionVarName]을 위한 고유번호 발행
 * 문자열 기본 길이는 8, 최대 길이는 30
 **/
	private function _generateSerialNumber($nIdLen=null)
	{
		if(!$nIdLen)
			$nIdLen = 8;
		if($nIdLen > 30)
			$nIdLen = 30;
		$sAllowedCharaters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghjklmnopqrstuvwxyz~,.;-=@#%^*()_+';
		return substr(str_shuffle($sAllowedCharaters), 0, $nIdLen);
	}
}