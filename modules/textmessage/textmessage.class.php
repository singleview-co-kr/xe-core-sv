<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  textmessage
 * @author wiley (wiley@nurigo.net)
 * @brief  base class of textmessage module
 **/

class textmessage extends ModuleObject 
{

	/**
	 * @brief install textmessage module
	 * @return Object
	 **/
	function moduleInstall() 
	{
		return new BaseObject();
	}

	/**
	 * @brief if update is necessary it returns true
	 **/
	function checkUpdate() 
	{
		return false;
	}

	/**
	 * @brief update module
	 * @return Object
	 **/
	function moduleUpdate() 
	{
		return new BaseObject();
	}

	/**
	 * @brief regenerate cache file
	 **/
	function recompileCache() { }
}
/* End of file textmessage.class.php */
/* Location: ./modules/textmessage.class.php */
