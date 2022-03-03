<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svestudioClass
 * @author singleview(root@singleview.co.kr)
 * @brief  svestudioClass
**/ 

class svestudio extends ModuleObject
{
	protected $_g_aSvshopModule = array( 'svcart', 'svcrm', 'svitem', 'svorder', 'svpg', 'svpromotion' );

	const MID_TYPE_CRONTAB = '1'; // 0으로하면 mid config에 저장이 안됨
	const MID_TYPE_SHIPPING = '2'; 
	protected $_g_aMidType = array(
		svestudio::MID_TYPE_CRONTAB=>'crontab',
		svestudio::MID_TYPE_SHIPPING=>'shipping' );
/**
 * constructor
 * @return void
 */
	function svestudio()
	{
	}
/**
 * @brief install the module
 **/
	function moduleInstall()
	{
	}
/**
 * @brief chgeck module method
 **/
	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		if(!$oModuleModel->getTrigger('member.doLogin', 'svestudio', 'model', 'triggerdoLoginAfter', 'after'))
			return true;
	}
/**
 * @brief update module
 **/
	function moduleUpdate()
	{
		$oModuleController = &getController('module');
		$oModuleController->insertTrigger('member.doLogin', 'svestudio', 'model', 'triggerdoLoginAfter', 'after');
	}
/**
 * @brief update module
 **/
	function moduleUninstall()
	{
		return FALSE;
	}
}