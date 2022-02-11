<?php
/**
 * @class  svshortenerController
 * @author singleview(root@singleview.co.kr)
 * @brief  svshortenerController
**/ 
class svshortenerController extends svshortener
{
	/**
	 * @brief initialization
	 **/
	function init()
	{
	}

	public function increaseCounter($sQueryValue)
	{
		$oArgs->shorten_uri_value = $sQueryValue;
		$output = executeQuery('svshortener.updateHitCount', $oArgs );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svshortener_db_query');

		// user agent information
		$oArgs->is_mobile_access = $_COOKIE['mobile'] == 'false' ? 'N' : 'Y';
		$oArgs->user_agent = trim( $_SERVER['HTTP_USER_AGENT'] );
		$output = executeQuery('svshortener.insertHitLog', $oArgs );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svshortener_db_query');
	}
}