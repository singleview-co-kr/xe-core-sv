<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svdocsModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsModel
**/ 
require_once(_XE_PATH_.'modules/svdocs/svdocs.cookie.php');
class svdocsModel extends module
{
/**
 * @brief initialization
 **/
	function init()
	{
	}
/**
 * @brief return module name in sitemap
 **/
    function triggerModuleListInSitemap(&$obj)
    {
        array_push($obj, 'svdocs');
    }
/**
 * @brief 
 **/
	function getErrorMessage($error_code)
	{
		switch($error_code)
		{
			case "InvalidAPIKey":
				$error_message = "문자메시지 모듈의 관리자 설정을 확인해주세요.";
				break;
			case "SignatureDoesNotMatch":
				$error_message = "문자메시지 모듈의 관리자 설정을 확인해주세요.";
				break;
			case "NotEnoughBalance":
				$error_message = "잔액이 부족합니다.";
				break;
			case "InternalError":
				$error_message = "서버오류";
				break;
			default:
				$error_message = "메시지 전송 오류";
				break;
		}
		$error_message = sprintf("%s(%s)", $error_message, $error_code);

		return $error_message;
	}
/**
 * @brief 
 */
	public function getSvdocsExpiration()
	{
		$nModuleSrl = (int)Context::get('module_srl');
		$oDocInfo = $this->getDocInfo( $nModuleSrl );
		$bRestricted = 0;
		$sMsg = '';
		$oCookie = new svdocsCookie( $nModuleSrl );
		if( (int)$oDocInfo->duplicate_restriction_sec )
		{
			if( $oCookie->isRestricted() )
			{
				$oCookie->setRestricted( (int)$oDocInfo->duplicate_restriction_sec );
				$bRestricted = 1;
				$sMsg = Context::getLang('msg_already_registered');
			}
		}
		if( strtotime($oDocInfo->timeOpendatetime) - time() >= 0 )
		{
			$bRestricted = 1;
			$sMsg = Context::getLang('msg_application_not_started');
		}

		if( $oDocInfo->nRemainingApplicants == 0 )
		{
			$bRestricted = 1;
			$sMsg =  Context::getLang('msg_application_closed');
		}
		$this->add('isRestricted', $bRestricted );
		$this->add('msg', $sMsg );
		return new BaseObject();
	}
/**
 * @brief 모듈 default setting 불러오기
 */
	function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		return $oModuleModel->getModuleConfig('svdocs');
	}
/**
 * @brief 
 */
	public function getDocInfo( $nModuleSrl )
	{
		$nCnt = $this->getDocsCount( $nModuleSrl );
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl( $nModuleSrl );
		
		$oDocInfo = new stdClass();
		if( $module_info->max_applicants == 'unlimit' )
			$oDocInfo->nRemainingApplicants = 100;
		else
			$oDocInfo->nRemainingApplicants = (int)$module_info->max_applicants - $nCnt;
		$oDocInfo->timeOpendatetime = $module_info->open_datetime;
		$oDocInfo->is_allow_update = $module_info->is_allow_update;
		$oDocInfo->mid = $module_info->mid;
		//$oDocInfo->timeDuedatetime = $module_info->due_datetime;
		$oDocInfo->duplicate_restriction_sec = $module_info->duplicate_restriction_sec;
		$oDocInfo->svdocs_unique_field = unserialize( $module_info->svdocs_unique_field );
		$oDocInfo->svauth_plugin_srl = $module_info->svauth_plugin_srl;
		if( ( strtotime($module_info->due_datetime) - time() <= 0) || $oDocInfo->nRemainingApplicants <= 0 ) 
			$oDocInfo->nRemainingApplicants = 0;

		return $oDocInfo;
	}
/**
 * @brief 
 */
	function getPrivacyTerm($nModuleSrl, $sTermType)
	{
		if( !(int)$nModuleSrl )
			return 'invalid_module_srl';

		switch($sTermType)
		{
			case 'privacy_usage_term':
			case 'privacy_shr_term':
				break;
			default:
				return null;
		}
		$agreement_file = _XE_PATH_.'files/svdocs/'.$nModuleSrl.'_'.$sTermType.'_' . Context::get('lang_type') . '.txt';
		if(is_readable($agreement_file))
			return FileHandler::readFile($agreement_file);

		$db_info = Context::getDBInfo();
		$agreement_file = _XE_PATH_.'files/svdocs/'.$nModuleSrl.'_'.$sTermType.'_' . $db_info->lang_type . '.txt';
		if(is_readable($agreement_file))
			return FileHandler::readFile($agreement_file);

		$lang_selected = Context::loadLangSelected();
		foreach($lang_selected as $key => $val)
		{
			$agreement_file = _XE_PATH_.'files/svdocs/'.$nModuleSrl.'_'.$sTermType.'_' . $key . '.txt';
			if(is_readable($agreement_file))
				return FileHandler::readFile($agreement_file);
		}
		
		// 최종 실패할 경우 기본 약관 출력
		$agreement_file = _XE_PATH_.'modules/svdocs/tpl/'.$sTermType.'_template.txt';
		if(is_readable($agreement_file))
			return FileHandler::readFile($agreement_file);

		return null;
	}
/**
 * @brief 
 */
	public function getSvdocsUpdatePermission()
	{
		$nModuleSrl = (int)Context::get('module_srl');
		$oDocInfo = $this->getDocInfo( $nModuleSrl );
		
		if( $oDocInfo->is_allow_update == 'Y' )
		{
			$this->add('is_update_allowed', 1 );
			$this->add('target_mid', $oDocInfo->mid );
		}
		else
			$this->add('is_update_allowed', 0 );
		return new BaseObject();
	}
/**
 * @brief 
 */
	public function getDocsCount( $nModuleSrl )
	{
		$args->module_srl = $nModuleSrl;
		$output = executeQuery('svdocs.getDocsCount', $args );

		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svdocs_db_query');
		else
			return $output->total_count;
	}
/**
 * @brief 
 */
	public function getDocList( $nModuleSrl )
	{
		$args->module_srl = $nModuleSrl;
		$output = executeQueryArray('svdocs.getApplicantList', $args);

		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svdocs_db_query');
		else
			return $output;
	}
/**
 * Common:: Module extensions of variable management
 * Expansion parameter management module in the document module instance, when using all the modules available
 * @param int $module_srl
 * @return string
 */
	function getExtraVarsHTML($module_srl)
	{
		// Bringing existing extra_keys
		$extra_keys = $this->getExtraKeys($module_srl);
		Context::set('extra_keys', $extra_keys);
		$security = new Security();
		$security->encodeHTML('extra_keys..');

		// Get information of module_grants
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($this->module_path.'tpl', 'extra_keys');
	}
/**
 * 사용자 정의 변수 추가 기능은 document 모듈에 의존하고, HTML form 작성은 svdocs model에서 재정의함
 * Function to retrieve the key values of the extended variable document
 * $Form_include: writing articles whether to add the necessary extensions of the variable input form
 * @param int $module_srl
 * @return array
 */
	public function getExtraKeys($module_srl)
	{
		if(!isset($GLOBALS['XE_SVDOCS_EXTRA_KEYS'][$module_srl]))
		{
			require_once(_XE_PATH_.'modules/svdocs/svdocsextravar.class.php');
			$keys = false;
			$oCacheHandler = CacheHandler::getInstance('object', null, true);
			if($oCacheHandler->isSupport())
			{
				$object_key = 'module_svdocs_extra_keys:' . $module_srl;
				$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
				$keys = $oCacheHandler->get($cache_key);
			}
			$oExtraVar = SvdocsExtraVar::getInstance($module_srl);
			if($keys === false)
			{
				$obj = new stdClass();
				$obj->module_srl = $module_srl;
				$obj->sort_index = 'var_idx';
				$obj->order = 'asc';
				$output = executeQueryArray('document.getDocumentExtraKeys', $obj);
				// correcting index order
				$isFixed = FALSE;
				if(is_array($output->data))
				{
					$prevIdx = 0;
					foreach($output->data as $no => $value)
					{
						// case first
						if($prevIdx == 0 && $value->idx != 1)
						{
							$args = new stdClass();
							$args->module_srl = $module_srl;
							$args->var_idx = $value->idx;
							$args->new_idx = 1;
							executeQuery('document.updateDocumentExtraKeyIdx', $args);
							executeQuery('document.updateDocumentExtraVarIdx', $args);
							$prevIdx = 1;
							$isFixed = TRUE;
							continue;
						}

						// case others
						if($prevIdx > 0 && $prevIdx + 1 != $value->idx)
						{
							$args = new stdClass();
							$args->module_srl = $module_srl;
							$args->var_idx = $value->idx;
							$args->new_idx = $prevIdx + 1;
							executeQuery('document.updateDocumentExtraKeyIdx', $args);
							executeQuery('document.updateDocumentExtraVarIdx', $args);
							$prevIdx += 1;
							$isFixed = TRUE;
							continue;
						}

						$prevIdx = $value->idx;
					}
				}

				if($isFixed)
					$output = executeQueryArray('document.getDocumentExtraKeys', $obj);

				$oExtraVar->setExtraVarKeys($output->data);
				$keys = $oExtraVar->getExtraVars();
				if(!$keys)
					$keys = array();

				if($oCacheHandler->isSupport())
					$oCacheHandler->put($cache_key, $keys);
			}
			$GLOBALS['XE_SVDOCS_EXTRA_KEYS'][$module_srl] = $keys;
		}
		return $GLOBALS['XE_SVDOCS_EXTRA_KEYS'][$module_srl];
	}
}