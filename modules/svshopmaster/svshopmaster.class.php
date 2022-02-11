<?php
 /**
 * @class  svshopmasterClass
 * @author singleview(root@singleview.co.kr)
 * @brief  svshopmasterClass
 */
class svshopmaster extends ModuleObject
{
/**
 * @brief deny if the user is not an administrator
 **/
	public function init($oThis=null)
	{
		$oMemberModel = &getModel('member');
		$logged_info = $oMemberModel->getLoggedInfo();
		if( $logged_info->is_admin != 'Y' )
			return $this->stop( 'msg_is_not_administrator' );

		// change into administration layout
		if($oThis)
		{
			$oThis->setLayoutPath( './modules/svshopmaster/tpl' );
			$oThis->setLayoutFile('common_layout');
		}
		else
		{
			$this->setLayoutPath( './modules/svshopmaster/tpl' );
			$this->setLayoutFile('common_layout');
		}
		
		$oModuleModel = &getModel( 'module' );
		$module_info = $oModuleModel->getModuleInfoXml( 'svshopmaster' );
		Context::set( 'svshopmaster_modinfo', $module_info );
	}
/**
 * @brief 
 **/
	public function moduleInstall()
	{
		return new BaseObject();
	}
/**
 * @brief 
 **/
	public function checkUpdate()
	{
		$oDB = &DB::getInstance();
		return false;
	}
/**
 * @brief 
 **/
	public function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		return new BaseObject();
	}
/**
 * @brief 
 **/
	public function recompileCache()
	{
	}
}