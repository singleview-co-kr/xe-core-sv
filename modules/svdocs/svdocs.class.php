<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svdocsClass
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsClass
**/ 

class svdocs extends ModuleObject
{
	/**
	 * constructor
	 *
	 * @return void
	 */
	function svdocs()
	{
	}

	function installTriggers()
	{
		$oModuleModel = &getModel('module');
		 $oModuleController = &getController('module');
		// display menu in sitemap, custom menu add
		if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'svdocs', 'model', 'triggerModuleListInSitemap', 'after'))
			$oModuleController->insertTrigger('menu.getModuleListInSitemap', 'svdocs', 'model', 'triggerModuleListInSitemap', 'after');
	}

	/**
	 * @brief install the module
	 **/
	function moduleInstall()
	{
		$this->installTriggers();
	}

	/**
	 * @brief chgeck module method
	 **/
	function checkUpdate()
	{
		$this->installTriggers();
	}

	/**
	 * @brief update module
	 **/
	function moduleUpdate()
	{
		$this->installTriggers();
	}

	function moduleUninstall()
	{
		return FALSE;
	}
}
