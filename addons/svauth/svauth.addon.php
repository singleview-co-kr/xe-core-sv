<?php
if(!defined('__XE__')) 
	exit();

//if($_SERVER[REMOTE_ADDR]=='124.49.181.29' || $_SERVER[HTTP_FORWARDED]=='for=124.49.181.29' 
//	|| $_SERVER[REMOTE_ADDR]=='211.40.168.194' || $_SERVER[HTTP_FORWARDED]=='for=211.40.168.194'
//	|| $_SERVER[REMOTE_ADDR]=='115.88.79.83' || $_SERVER[HTTP_FORWARDED]=='for=115.88.79.83' )
//{
	require_once(_XE_PATH_.'addons/svauth/class/svauthaddon.class.php');
	$oSvAuthAddon = new svAuthAddon( $called_position);
	$oSvAuthAddon->doAuthAddonProc();
//}
?>