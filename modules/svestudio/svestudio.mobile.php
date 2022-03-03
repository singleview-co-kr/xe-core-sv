<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svestudioMobile
 * @author singleview(root@singleview.co.kr)
 * @brief  svestudioMobile class
 */
require_once(_XE_PATH_.'modules/svestudio/svestudio.view.php');
class svestudioMobile extends svestudioView
{
	function init()
	{
		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		if(!is_dir($template_path)||!$this->module_info->mskin) 
		{
			$this->module_info->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);
	}
}
/* End of file svestudio.mobile.php */
/* Location: ./modules/svestudio/svestudio.mobile.php */