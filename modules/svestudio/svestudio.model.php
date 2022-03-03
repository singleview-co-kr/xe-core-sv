<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svestudioModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svestudioModel
**/ 
class svestudioModel extends module
{
/**
 * @brief initialization
 **/
	function init()
	{
		$sSvestudioMid = Context::get('mid');
		$oModuleModel = &getModel('module');
		$oSvestudioMidConfig = $oModuleModel->getModuleInfoByMid($sSvestudioMid);
		$oLoggedInfo = Context::get('logged_info');
		$oModuleGrant = $oModuleModel->getGrant($oSvestudioMidConfig, $oLoggedInfo);
		if( !$oModuleGrant->shop_staff )
			return $this->stop( 'msg_is_not_allowed' );

		$nModuleSrl = Context::get('module_srl');
		$sAct = Context::get('act');
		$oSvestudioModel = &getModel('svestudio');
		$bAllowed = $oSvestudioModel->isPermittedActByMemberGrp( $oLoggedInfo, $nModuleSrl, $sAct );
		if( !$bAllowed )
			return $this->stop( 'msg_is_not_allowed' );
	}
/**
 * @brief 
 **/
	public function triggerdoLoginAfter(&$oLoggedInfo)
	{
		if(is_null($oLoggedInfo->member_srl))
			return new BaseObject(-1, 'msg_error_not_a_member');
		
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		$oModuleInfo = $this->getModuleConfig();
		$aInvitedGroupSrl = unserialize( $oModuleInfo->invited_member_group );
		$bInvited = false;
		foreach( $aInvitedGroupSrl as $nGrpSrl => $sVal )
		{
			if( isset( $oLoggedInfo->group_list[$nGrpSrl] ) )
			{
				$bInvited = true;
				break;
			}
		}
		if( $bInvited )
		{
			$aPermittedMid = array();
			$oMidList = $oSvestudioAdminModel->getMidList();
			foreach( $oMidList->data as $nIdx => $oMidInfo )
			{
				$oMidConfig = $oSvestudioAdminModel->getMidConfig( $oMidInfo->module_srl );
				foreach( $oMidConfig->permitted_act_by_mid as $sPermittedAct => $aPermittedGrp )
				{
					foreach( $aPermittedGrp as $nPermittedGrpSrl => $sFoo )
					{
						if( isset( $oLoggedInfo->group_list[$nPermittedGrpSrl] ) )
						{
							$aPermittedMid[] = $oMidInfo->mid;
							break;
						}
					}
				}
			}
		}
		if( count( (array)$aPermittedMid ) > 0 )
			Context::set('success_return_url', '/'.$aPermittedMid[0]);
	}
/**
 * @brief 
 **/
	public function getSvestudioRegisterShippingInvoice()
	{
		$nModuleSrl = Context::get('module_srl');
		$oMidConfig = $this->getMidConfig($nModuleSrl);
		Context::set('mid', $oMidConfig->mid );
		$sSkinPath = $this->module_path.'tpl/';
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($sSkinPath, 'form_register_shipping_invoice');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
/**
 * @brief 
 **/
	public function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		return $oModuleModel->getModuleConfig('svestudio');
	}
/**
 * @brief ./svestudio.admin.model.php::getMidConfig()와 동일한 내용이어야 함
 **/
	public function getMidConfig($nModuleSrl)
	{
		$oModuleModel = &getModel('module');
		$oMidInfo = $oModuleModel->getModuleInfoByModuleSrl($nModuleSrl);
		$oMidInfo->permitted_act_by_mid = unserialize( $oMidInfo->permitted_act_by_mid );
		return $oMidInfo;
	}
/**
 * @brief 
 **/
	public function isPermittedActByMemberGrp( $oLoggedInfo, $nModuleSrl, $sAct )
	{
		$oMidInfo = $this->getMidConfig($nModuleSrl);
		$aPermittedActByMid = $oMidInfo->permitted_act_by_mid;
		foreach( $oLoggedInfo->group_list as $nGrpSrl => $sGrpTitle)
		{
			if( $aPermittedActByMid[$sAct][$nGrpSrl] == 'permit' )
				return true;
		}
		return false;
	}
/**
 * @brief will be deprecated
 **/
	public function getPermittedModuleListByMemberGrp( $aMemberGrpList )
	{
		$aPermittedModule = array();
		if( count( $aMemberGrpList ) == 0 )
			return $aPermittedModule;

		$oSvestudioConfig = $this->getModuleConfig();
		foreach( $aMemberGrpList as $nGrpSrl => $sGrpTitle )
		{
			foreach( $oSvestudioConfig->permitted_act_by_module as $sModuleName => $oPermitActInfo )
			{
				if( $aPermittedModule[$sModuleName] != 'permitted' )
				{
//var_dump( $sModuleName );
//echo "<BR>";
					foreach( $oPermitActInfo as $sActName => $aPermitGrpInfo )
					{
//var_dump( $sActName );
//echo "<BR>";
						if( isset( $aPermitGrpInfo[$nGrpSrl] ) )
						{
							$aPermittedModule[$sModuleName] = 'permitted';
//var_dump( $aPermittedModule );
//echo "<BR>";
							break;
						}
					}
				}
			}
		}
		ksort( $aPermittedModule );
		return $aPermittedModule;
	}
}