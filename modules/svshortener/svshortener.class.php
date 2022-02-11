<?php
/**
 * @class  shortenerClass
 * @author singleview(root@singleview.co.kr)
 * @brief  shortenerClass
**/ 

class svshortener extends ModuleObject
{
/**
 * @brief 
 **/
	function moduleInstall()
	{
		return new BaseObject();
	}
/**
 * @brief a method to check if successfully installed
 */
	function checkUpdate()
	{
		return false;
	}
/**
 * @brief Execute update
 */
	function moduleUpdate()
	{
		return new BaseObject(0,'success_updated');
	}
/**
 * @brief Re-generate the cache file
 */
	function recompileCache()
	{
	}
}