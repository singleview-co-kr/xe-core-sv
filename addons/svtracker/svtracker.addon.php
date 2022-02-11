<?php
if(!defined( '__ZBXE__' ) )
	exit();

if( $addon_info->toggle_action_log == 'on' )
{
	if( $this->module && $this->act )
	{
		require_once(_XE_PATH_ . 'addons/svtracker/class/svaction_logger.class.php');
		new SvActionLogger($this, $addon_info->watch_parital_query );
	}
}

$sModule = Context::get( 'module' );
if( $sModule == 'admin' || $sModule == 'svshopmaster' )
	return;

$sAct = Context::get( 'act' );
if( $sAct == 'dispDocumentManageDocument' || $sAct == 'dispModuleSelectList' )
	return;

if( $this->module == 'svdocs' )
	return;

// Manage callling timing
if( $called_position != 'before_module_proc' )
	return;

require_once(_XE_PATH_ . 'addons/svtracker/class/svtracker.class.php');
$oSvTracker = new SvTracker();

if( strlen( $addon_info->shortner_query_name ) > 0 )
{	
	$oShortnerInfo = $oSvTracker->getShortenerInfo( $addon_info->shortner_query_name );
	if( $oShortnerInfo ) // 최초 진입 시에만 스크립트 출력
	{
		$sShortTrackerScript = "<SCRIPT>setUtmParamsGaectk( '$oShortnerInfo[source]', '$oShortnerInfo[medium]', '$oShortnerInfo[campaign]', '$oShortnerInfo[keyword]', '' ); _sendGaEventWithoutInteraction( 'tracking', 'paid_organic_captured', '$oShortnerInfo[campaign]__$oShortnerInfo[keyword]');</SCRIPT>";
		Context::addHtmlFooter( $sShortTrackerScript );
	}
	if( $addon_info->debug_mode == 'on' )
	{
		$sLoggerMSg = $oSvTracker->getDebugMsg();
		Context::addbodyHeader( "\n\n<!------------------------------------------>\n<!-- ".$sLoggerMSg." --->\n<!------------------------------------------>\n\n" );
	}
}
else
	$oSvTracker->setNormalTracker();

if( $addon_info->use_utm_campaign == 'on' && $_COOKIE['svcampaign'] != 'off' )
{
	if( $addon_info->utm_campaign_close_text == '' )
		$sCloseText = '24시간 OFF';
	else
		$sCloseText = trim( $addon_info->utm_campaign_close_text );

	if( $addon_info->utm_campaign_close_hrs == '' )
		$nCloseHrs = 24;
	else
		$nCloseHrs = (int)$addon_info->utm_campaign_close_hrs;
	

	$aValue = explode( ';', $addon_info->utm_campaign_value );	
	$sUtmCampaign = $oSvTracker->getCampaignInfo();
	foreach( $aValue as $key=>$val)
	{
		if( $val == 'all' || ( strlen($val) && preg_match( "/$val/i", $sUtmCampaign ) ) )
		{
			if( !$addon_info->utm_campaign_inform_message )
				$addon_info->utm_campaign_inform_message = '안내문을 입력해 주세요.';
			$refhead = "<div class='layer'><div class='bg'></div><div id='layer2' class='pop-layer'><div class='pop-container'><div class='pop-conts'>";
			$refhead .= "<p class='ctxt mb20'>".$addon_info->utm_campaign_inform_message."</p><div class='btn-r'><a href='#' class='cbtn'>".$sCloseText."</a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if( $addon_info->utm_campaign_cta_link && $addon_info->utm_campaign_cta_message )
				$refhead .= "<a href='".$addon_info->utm_campaign_cta_link."' class='ccta_btn'>".$addon_info->utm_campaign_cta_message."</a>";
			$refhead .= "</div></div>";
			$refhead .= "</div></div></div>";
			$sCssFile = './addons/svtracker/css/layer_popup_pc.css';

			if(Mobile::isMobileCheckByAgent())
				$sCssFile = './addons/svtracker/css/layer_popup_mob.css';
			Context::addCssFile( $sCssFile );
			Context::addJsFile( './addons/svtracker/js/layer_popup.js' );
			Context::addbodyHeader( $refhead.$refbody.$reffoot );
			Context::addHtmlFooter( "<script>layer_open('layer2',".$nCloseHrs.");</script>" );
			break;
		}
	}
}
/*$refbody = '';
$sRefererSession = $_SESSION['HTTP_INIT_REFERER'];

if( eregi( 'google', $sRefererSession ) > -1 )
	$refbody = $addon_info->google;
if( eregi( 'naver', $sRefererSession ) > -1 )
	$refbody = $addon_info->naver;
if( eregi( 'daum', $sRefererSession ) > -1 )
	$refbody = $addon_info->daum;
if( eregi( 'twitter', $sRefererSession ) >-1 )
	$refbody = $addon_info->twitter;
if( eregi( 'facebook', $sRefererSession ) >-1 )
	$refbody = $addon_info->facebook;

if( $addon_info->type == 'layout' )
{
	if( eregi( 'google', $sRefererSession ) > -1 )
		if( $addon_info->google_image )
			$refbody = $refbody."<img src='".$addon_info->google_image."' />";
	if( eregi( 'naver', $sRefererSession ) >- 1 )
		if( $addon_info->naver_image )
			$refbody = $refbody."<img src='".$addon_info->naver_image."' />";
	if( eregi( 'daum', $sRefererSession ) > -1 )
		if( $addon_info->daum_image )
			$refbody = $refbody."<img src='".$addon_info->daum_image."' />";
	if( eregi( 'twitter', $sRefererSession) > -1 )
		if( $addon_info->twitter_image )
			$refbody = $refbody."<img src='".$addon_info->twitter_image."' />";
	if( eregi( 'facebook', $sRefererSession ) > -1 )
		if( $addon_info->facebook_image )
			$refbody = $refbody."<img src='".$addon_info->facebook_image."' />";
}

if( $refbody != '' )
{
	Context::addbodyHeader( $refhead.$refbody.$reffoot );
	Context::addHtmlFooter( "<script>layer_open('layer2');</script>" );
}*/	
?>