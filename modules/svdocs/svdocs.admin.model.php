<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svdocsAdminModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsAdminModel
**/ 
class svdocsAdminModel extends svdocs
{
/**
 * Initialization
 * @return void
 */
	function init()
	{
	}
	
	function getModuleConfig($nModuleSrl)
	{
		$oModuleModel = &getModel('module');
		if( $nModuleSrl )
		{
			if (!$GLOBALS['__svdocs_module_config__'])
			{
				$config = $oModuleModel->getModuleInfoByModuleSrl($nModuleSrl);
				$GLOBALS['__svdocs_module_config__'] = $config;
			}
			return $GLOBALS['__svdocs_module_config__'];
		}
		else
			return $oModuleModel->getModuleConfig('svdocs');
	}

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

		$agreement_file = _XE_PATH_.'files/svdocs/'.$nModuleSrl.'_'.$sTermType.'_'.Context::get('lang_type').'.txt';
		if(is_readable($agreement_file))
			return nl2br(FileHandler::readFile($agreement_file));

		$db_info = Context::getDBInfo();
		$agreement_file = _XE_PATH_.'files/svdocs/'.$nModuleSrl.'_'.$sTermType.'_'.$db_info->lang_type.'.txt';
		if(is_readable($agreement_file))
			return nl2br(FileHandler::readFile($agreement_file));

		$lang_selected = Context::loadLangSelected();
		foreach($lang_selected as $key => $val)
		{
			$agreement_file = _XE_PATH_.'files/svdocs/'.$nModuleSrl.'_'.$sTermType.'_'.$key.'.txt';
			if(is_readable($agreement_file))
				return nl2br(FileHandler::readFile($agreement_file));
		}
		
		// 최종 실패할 경우 기본 약관 출력
		$agreement_file = _XE_PATH_.'modules/svdocs/tpl/'.$sTermType.'_template.txt';
		if(is_readable($agreement_file))
			return nl2br(FileHandler::readFile($agreement_file));

		return null;
	}
/**
 * 응모자 삭제 호출 callback 함수
 * @return void
 */
	function getSvdocsAdminDeleteDoc() 
	{
		$doc_srls = Context::get('doc_srls');
		$doc_names = Context::get('doc_names');
		$doc_phones = Context::get('doc_phones');

		foreach( $doc_names as $key => $val )
			$doc_name_to_be_deleted[$key] = $val;

		foreach( $doc_phones as $key => $val )
			$doc_phone_to_be_deleted[$key] = $val;

		Context::set('doc_srls_to_be_deleted', $doc_srls);
		Context::set('doc_names_to_be_deleted', $doc_name_to_be_deleted);
		Context::set('doc_phones_to_be_deleted', $doc_phone_to_be_deleted);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_doc');

		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
/**
 * Extra variables for each article will not be processed bulk select and apply the macro city
 * @return void
 */
	public function getDocExtraVars($nModuleSrl, $nDocSrl)
	{
		if( !$nModuleSrl || !$nDocSrl )
			return new BaseObject(-1, 'msg_invalid_request');

		$oSvdocsModel = getModel('svdocs');
		$oExtraVars = $oSvdocsModel->getExtraKeys($nModuleSrl);
		$aExtraVarInfo = array();
		foreach( $oExtraVars as $key => $val )
			$aExtraVarInfo[$val->eid] = $val;
		
		$args = new stdClass();
		$args->module_srl = $nModuleSrl;
		$args->doc_srl = $nDocSrl;
		$output = executeQueryArray('svdocs.getDocExtraVars', $args);
		if($output->toBool() && $output->data)
		{
			foreach($output->data as $key => $val)
			{
				$output->data[$key]->name = $aExtraVarInfo[$val->eid]->name;
				//if( $aExtraVarInfo[$val->eid]->type == 'checkbox' )
				//	$output->data[$key]->value = str_replace("|@|", ";", $output->data[$key]->value);
				//else if( $aExtraVarInfo[$val->eid]->type == 'kr_zip' )
				//	$output->data[$key]->value = str_replace("|@|", " ", $output->data[$key]->value);
			}
		}
		return $output->data;
	}
/**
 * to be deleted
 */
	public function getSvdocsAdminConfidentialMaxIndex()
	{
		$output = executeQueryArray('svdocs.getDocsConfidentialMaxIndex' );

		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svdocs_confidential_db_query');
		
		if( count( $output->data ) == 0 )  // 최초 입력시 ++인덱스가 0이 되도록
			return -1;

		foreach( $output->data as $key => $val )
			return $val->doc_srl;
	}
}
/* End of file board.admin.model.php */
/* Location: ./modules/board/board.admin.model.php */