<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svdocsView
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsView
**/ 
class svdocsCookie
{
	var $_g_nModuleSrl = 0;
	var $_g_sSvdocsCookieName = '';
	/**
	 * @brief Initialization
	 */
	public function svdocsCookie( $nModuleSrl )
	{
		$this->_g_nModuleSrl = $nModuleSrl;
		$this->_g_sSvdocsCookieName = 'svdocs_restriction_'.$nModuleSrl;
	}
	/**
	 * @brief General request output
	 */
	public function setRestricted( $nSec )
	{
		//$sCookieValue = $_COOKIE[$this->_g_sSvdocsCookieName];
		//if( !$sCookieValue )
			setcookie( $this->_g_sSvdocsCookieName, 'restricted', time()+$nSec /*, '/~rasmus/', $_SERVER['SERVER_NAME']*/ );
	}
	public function isRestricted()
	{
		if( $_COOKIE[$this->_g_sSvdocsCookieName] == 'restricted' )
		{
			//setcookie( $this->_g_sSvdocsCookieName, 'restricted', time()-3600 ); // delete restriction cookie
			return true;
		}
		else
			return false;
	}		
}
/* End of file svdocs.cookie.php */
/* Location: ./modules/svdocs/svdocs.cookie.php */
