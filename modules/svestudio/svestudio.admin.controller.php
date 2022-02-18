<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svestudioAdminController
 * @author singleview(root@singleview.co.kr)
 * @brief  svestudioAdminController
**/ 

class svestudioAdminController extends svestudio
{
/**
 * @brief initialization
 **/
	public function init() 
	{
	}
/**
 * @brief ./files/svestudio/ ���� ����
 **/
	/*private function _resetCache()
	{
		FileHandler::removeFilesInDir('./files/svestudio/');
	}*/
/**
 * @brief ��ü ������ ǥ���ϴ� CSV �ο� ������ �ٿ�ε� 
 **/
	public function procSvestudioAdminCSVDownload() 
	{
		$sDataBegin = trim( Context::get('date_begin') );
		$bDateValidation = $this->_validateDateString($sDataBegin, 'Ymd');
		if( !$bDateValidation )
			return new BaseObject(-1, 'msg_invalid_date_begin');

		$sDataEnd = trim( Context::get('date_end') );
		$bDateValidation = $this->_validateDateString($sDataEnd, 'Ymd');
		if( !$bDateValidation )
			return new BaseObject(-1, 'msg_invalid_date_end');

		$dtStart = new DateTime($sDataBegin);
		$dtEnd  = new DateTime($sDataEnd);
		$dtDiff = $dtStart->diff($dtEnd);
		if( $dtDiff->format('%r%a') > 120 )
			return new BaseObject(-1, 'msg_period_exceeds_120_days'); // use for point out relation: smaller/greater
		
		$oPeriodArgs->date_begin = $sDataBegin;
		$oPeriodArgs->date_end = $sDataEnd;
		$oRst = executeQueryArray('svestudio.getMediaPerfLogByPeriod', $oPeriodArgs);
		if( !$oRst->toBool() )
			return $oRst;

		$aRec = $oRst->data;

		header( 'Content-Type: Application/octet-stream; charset=UTF-8' );
		header( "Content-Disposition: attachment; filename=\"PERFORMANCE-RAW-".$sDataBegin.'-'.$sDataEnd.".csv\"");
		echo chr( hexdec( 'EF' ) );
		echo chr( hexdec( 'BB' ) );
		echo chr( hexdec( 'BF' ) );

		echo 'log_srl,media_ua,media_term,media_source,media_rst_type,media_media,media_brd,media_camp1st,media_camp2nd,media_camp3rd,media_raw_cost,media_agency_cost,media_cost_vat,media_gross_cost,media_imp,media_click,media_conv_cnt,media_conv_amnt,in_site_tot_session,in_site_tot_new,in_site_tot_bounce,in_site_tot_duration_sec,in_site_tot_pvs,in_site_trs,in_site_revenue,in_site_registrations,yr,mo,day,weeknum,weekday';
		
		echo "\r\n";
		$aDayName = array('su', 'mo', 'tu', 'we','th','fr', 'sa');

		foreach( $aRec as $nNo => $oRecord )
		{
			echo $oRecord->log_srl.',';
			echo $oRecord->media_ua.',';
			echo '"'.$oRecord->media_term.'",';
			echo $oRecord->media_source.',';
			echo $oRecord->media_rst_type.',';
			echo $oRecord->media_media.',';
			echo $oRecord->media_brd.',';
			echo $oRecord->media_camp1st.',';
			echo $oRecord->media_camp2nd.',';
			echo $oRecord->media_camp3rd.',';
			if( $oRecord->media_raw_cost != -1 )
				echo $oRecord->media_raw_cost.',';
			else
				echo '0,';

			if( $oRecord->media_agency_cost != -1 )
				echo $oRecord->media_agency_cost.',';
			else
				echo '0,';

			if( $oRecord->media_cost_vat != -1 )
				echo $oRecord->media_cost_vat.',';
			else
				echo '0,';
			
			if( $oRecord->media_raw_cost != -1 )
				echo $oRecord->media_raw_cost + $oRecord->media_agency_cost + $oRecord->media_cost_vat.',';
			else
				echo '0,';
			
			echo $oRecord->media_imp.',';
			echo $oRecord->media_click.',';
			echo $oRecord->media_conv_cnt.',';
			echo $oRecord->media_conv_amnt.',';
			echo $oRecord->in_site_tot_session.',';
			echo $oRecord->in_site_tot_new.',';
			echo $oRecord->in_site_tot_bounce.',';
			echo $oRecord->in_site_tot_duration_sec.',';
			echo $oRecord->in_site_tot_pvs.',';
			echo $oRecord->in_site_trs.',';
			echo $oRecord->in_site_revenue.',';
			echo $oRecord->in_site_registrations.',';
			
			if (($timestamp = strtotime($oRecord->logdate)) !== false)
			{
				echo (int)date('Y', $timestamp).',';
				echo (int)date('m', $timestamp).',';
				echo (int)date('d', $timestamp).',';
				echo (int)date('W', $timestamp).',';
				echo $aDayName[(int)date('w', $timestamp)];
			}
			else
				echo 'invalid timestamp!';
				
			echo "\r\n";
		}
		Context::setResponseMethod('JSON'); // display class �۵� ����
	}
/**
 * @brief ��� ȯ�漳���� ����
 **/
	public function procSvestudioAdminInsertModInst() 
	{
		// module ����� model/controller ��ü ����
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// �Խ��� ����� ���� ����
		$args = Context::getRequestVars();
		$args->module = 'svestudio';

		// module_srl�� �Ѿ���� �� ����� �ִ��� Ȯ��
		if($args->module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
				unset($args->module_srl);
		}

		// module_srl�� ���� ���� insert/update
		if(!$args->module_srl) 
		{
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}
		else
		{
			//$output = $oModuleController->updateModule($args);
			$output = $this->_updateMidLevelConfig($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool())
			return $output;

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);
		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvestudioAdminInsertModInst','module_srl',$this->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}
/**
 * @brief �ʴ� �׷��� ��⺰ ���� ����
 **/
	public function procSvestudioAdminGrantActByMid() 
	{
		$oTempArgs = Context::getRequestVars();
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		//$oModuleInfo = $oSvestudioAdminModel->getModuleConfig();
		//$nModuleSrl = Context::get('module_srl');
		//$oMidInfo = $oSvestudioAdminModel->getMidConfig($nModuleSrl);
//var_dump( $oTempArgs->module_srl );
//echo '<BR><BR>';

		//$aPermittedActByMid = unserialize( $oMidInfo->permitted_act_by_mid );
		//unset( $aPermittedActByMid[$oTempArgs->module_name] );
//var_dump( $oMidInfo );
//echo '<BR><BR>';
		foreach( $oTempArgs as $key=>$val)
		{
			if(strpos($key, 'permission_') !== false)
			{
				$sAct = str_replace('permission_', '', $key);
				foreach( $val as $nGrpIdx=>$nGrpSrl)
					$aPermittedActByMid[$sAct][$nGrpSrl] = 'permit';
			}
		}
		$oArgs->permitted_act_by_mid = serialize( $aPermittedActByMid );
		$oArgs->module_srl = $oTempArgs->module_srl;
//var_dump( $oArgs);
//echo '<BR><BR>';
		//$output = $this->_saveModuleConfig($oArgs);
		$oRst = $this->_updateMidLevelConfig($oArgs);
//var_dump( $oRst);
//echo '<BR><BR>';
		if(!$oRst->toBool())
			$this->setMessage( 'error_occured' );
		else
			$this->setMessage( 'success_updated' );
//exit;
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvestudioAdminActGrantByMid', 'module_srl',$oTempArgs->module_srl);
//var_dump( $returnUrl );
//exit;
			$this->setRedirectUrl( $returnUrl );
			return;
		}
	}
/**
* @brief update mid level config
* procSvitemAdminInsertModInst �� �����ؾ� ��
**/
	private function _updateMidLevelConfig($oArgs)
	{
		if( !$oArgs->module_srl )
			return new BaseObject(-1, 'msg_invalid_module_srl');

		unset( $oArgs->module );
		unset( $oArgs->error_return_url );
		unset( $oArgs->success_return_url );
		unset( $oArgs->act );
		unset( $oArgs->ext_script );
		unset( $oArgs->list );

		$oModuleModel = &getModel('module');
		$oConfig = $oModuleModel->getModuleInfoByModuleSrl($oArgs->module_srl);
		foreach( $oArgs as $key=>$val)
			$oConfig->{$key} = $val;
var_dump( $oConfig);
echo '<BR><BR>';		
		$oModuleController = &getController('module');
		$oRst = $oModuleController->updateModule($oConfig);
		return $oRst;
	}
/**
 * @brief �ʴ� �׷� ����
 **/
	public function procSvestudioAdminInvitedMemberGrp() 
	{
		$oArgs = Context::getRequestVars();
		$aParams = array( 'invited_member_group' );	
		foreach( $aParams as $nIdx => $sParamName )
		{
			if( !$oArgs->{$sParamName} )
				$oArgs->{$sParamName} = '';
		}
		$aInvitedGrp = array();
		foreach( $oArgs->invited_member_group as $nIdx => $nGrpSrl )
			$aInvitedGrp[$nGrpSrl] = 'Y';

		$oArgs->invited_member_group = serialize( $aInvitedGrp );
		$oRst = $this->_saveModuleConfig($oArgs);
		if(!$oRst->toBool())
			$this->setMessage( 'error_occured' );
		else
			$this->setMessage( 'success_updated' );

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvestudioAdminInviteInfo');
			$this->setRedirectUrl($returnUrl);
		}
	}
/**
 * @brief ������ ĳ�� �����
 * /svorder/ext_class/npay/npay_api.class.php::getLatestOrder()���� ȣ��
 * /svorder/ext_class/npay/npay_api.class.php::resetOrderInfo()���� ȣ��
 **/
	public function procSvestudioAdminRemoveCache() 
	{
		$sCachePath = _XE_PATH_.'files/svestudio';
		$aRemoveCache = Context::get('remove_cache');
		
		foreach( $aRemoveCache as $key=>$val )
		{
			if( $val == 'ga_session' )
				FileHandler::removeDir( $sCachePath.'/session' );
			else if( $val == 'ga_sales' )
				FileHandler::removeDir( $sCachePath.'/sales' );
		}
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvestudioAdminCacheInit');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}
/**
 * @brief ��� ȯ�漳���� ����
 **/
	public function procSvestudioAdminConfig() 
	{
		if(!FileHandler::makeDir(_XE_PATH_.'files/svestudio/'))
			return FALSE;

		$oArgs = Context::getRequestVars();
		$sSecrectKeyIniFilePath = _XE_PATH_.'files/svestudio/key.config.ini';
		$aConfig['sv_secret_key'] = $oArgs->svestudio_sv_secret_key;
		$aConfig['sv_iv'] = $oArgs->svestudio_sv_iv;
		unset( $oArgs->svestudio_sv_secret_key );
		unset( $oArgs->svestudio_sv_iv );

		FileHandler::writeIniFile($sSecrectKeyIniFilePath, $aConfig);
		
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('svestudio', $oArgs);

		if(!$output->toBool()) 
			$this->setMessage('failure_updated');
		else
			$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvestudioAdminConfig');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}
/**
 * @brief arrange and save module config
 **/
	private function _saveModuleConfig($oArgs)
	{
		$oSvorderAdminModel = &getAdminModel('svestudio');
		$oConfig = $oSvorderAdminModel->getModuleConfig();
		if(is_null($oConfig))
				$oConfig = new stdClass();
		foreach( $oArgs as $key=>$val)
			$oConfig->{$key} = $val;

		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('svestudio', $oConfig);
		return $output;
	}
/**
 * @brief 
 **/
	private function _validateDateString($sDate, $sFormat = 'Y-m-d H:i:s')
	{
		$oDataValidation = DateTime::createFromFormat($sFormat, $sDate);
		return $oDataValidation && $oDataValidation->format($sFormat) == $sDate;
	}
/**
 * @brief 
 */
	/*public function procSvestudioAdminDelete()
	{
		$module_srl = Context::get('module_srl');
		// delete designated module
		
		// Get an original
		$oModuleController = getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) 
			return $output;

		$this->add('module','page');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSvestudioAdminIndex');
		$this->setRedirectUrl($returnUrl);
	}*/
}