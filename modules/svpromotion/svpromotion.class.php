<?php
/**
 * @class  svpromotion
 * @author singleview(root@singleview.co.kr)
 * @brief  svpromotion
 */
class svpromotion extends ModuleObject
{
	const RESERVES_REASON_SETTLEMENT = '0';
	const RESERVES_REASON_FULL_CANCEL = '1';
	const RESERVES_REASON_PARTIAL_CANCEL = '2';
	
	const PROMO_INFO_VERS = '1.1';
/**
 * constructor
 * @return void
 */
	function svpromotion()
	{
	}
/**
 * @brief install the module
 **/
	function moduleInstall()
	{
		return FALSE;
	}
/**
 * @brief chgeck module method
 **/
	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		if(!$oModuleModel->getTrigger('point.setPoint', 'svpromotion', 'controller', 'triggerSetPointAfter', 'after'))
			return true;

		return FALSE;
	}
/**
 * @brief update module
 **/
	function moduleUpdate()
	{
		// 회원 포인트 갱신 트리거
		$oModuleController = &getController('module');
		$oModuleController->insertTrigger('point.setPoint', 'svpromotion', 'controller', 'triggerSetPointAfter', 'after');
		return new BaseObject(0, 'success_updated');
	}
/**
 * @brief 
 **/
	function moduleUninstall()
	{
		return FALSE;
	}
}
