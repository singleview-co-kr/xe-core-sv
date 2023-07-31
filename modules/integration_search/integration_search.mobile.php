<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

require_once(_XE_PATH_.'modules/integration_search/integration_search.view.php');

class integration_searchMobile extends integration_searchView
{
	protected $_g_oConfig = null;
	
	function init()
	{
		// Check permissions
		if(!$this->grant->access) 
			return new BaseObject(-1,'msg_not_permitted');
		$oISModel = getModel('integration_search');
		$this->_g_oConfig = $oISModel->getModuleConfig();
		$sTemplatePath = sprintf("%sm.skins/%s/",$this->module_path, $this->_g_oConfig->mskin);
		if(!is_dir($sTemplatePath)||!$this->_g_oConfig->mskin)
		{
			$this->_g_oConfig->mskin = 'default';
			$sTemplatePath = sprintf("%sm.skins/%s/",$this->module_path, $this->_g_oConfig->mskin);
		}
		$this->setTemplatePath($sTemplatePath);
	}
}
