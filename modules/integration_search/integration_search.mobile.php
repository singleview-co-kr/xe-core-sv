<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

require_once(_XE_PATH_.'modules/integration_search/integration_search.view.php');

class integration_searchMobile extends integration_searchView
{
	function init()
	{
		// Check permissions
		if(!$this->grant->access) return new BaseObject(-1,'msg_not_permitted');

		$oModuleModel = getModel('module');
		$oConfig = $oModuleModel->getModuleConfig('integration_search');
		unset($oModuleModel);
		if(!$oConfig)
			$oConfig = new stdClass;
		$sTemplatePath = sprintf("%sm.skins/%s/",$this->module_path, $oConfig->mskin);
		if(!is_dir($sTemplatePath)||!$oConfig->mskin)
		{
			$oConfig->mskin = 'default';
			$sTemplatePath = sprintf("%sm.skins/%s/",$this->module_path, $oConfig->mskin);
		}
		unset($oConfig);
		unset($oModuleModel);
		$this->setTemplatePath($sTemplatePath);
	}
}
