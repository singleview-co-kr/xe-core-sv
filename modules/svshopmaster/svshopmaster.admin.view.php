<?php
/**
 * @class  svshopmasterAdminView
 * @author singleview(root@singleview.co.kr)
 * @brief  svshopmasterAdminView
 */
class svshopmasterAdminView extends svshopmaster 
{
/**
 * @brief 
 **/
	public function dispSvshopmasterAdminIndex() 
	{
		// change into administration layout
		$this->setLayoutPath( './modules/svshopmaster/tpl' );
		$this->setLayoutFile( 'common_layout.html' );

		$this->setTemplatePath( $this->module_path.'tpl' );
		$this->setTemplateFile( 'dashboard' );
		
		$oLoggedInfo = Context::get('logged_info');
		Context::set( 'member_id', $oLoggedInfo->user_id );

		// retrieve dashboard report
		$oSvestudioAdminModel = &getAdminModel('svestudio');
		
		$aElapsedTime = array();
		$tStart = $this->_getTime();

		$oSvestudioAdminModel->init();

		$oOrderStatus = $oSvestudioAdminModel->getOrderStatus();
		Context::set( 'order_status', $oOrderStatus);
		
		$tEnd = $this->_getTime();
		$aElapsedTime['after_order_status'] = $tEnd - $tStart;

		$oHistoricalStatus = $oSvestudioAdminModel->getHistoricalStatus();
		Context::set( 'historical_status', $oHistoricalStatus);

		$tEnd = $this->_getTime();
		$aElapsedTime['after_historical_status'] = $tEnd - $tStart;
/////////////////////////		
		$oSvestudioConfig = $oSvestudioAdminModel->getModuleConfig();
		if( $oSvestudioConfig->revenue_referrence == 'insite' )
			Context::set( 'data_referrence', 'InSite');
		else
			Context::set( 'data_referrence', 'GA');
//////////////////////////
		$aMonthlyPeriod = $oSvestudioAdminModel->getMontlyPeriod();
		
		$aPerformanceInfoPrevMonth = $oSvestudioAdminModel->getPerformanceInfo($aMonthlyPeriod['first_day_of_last_month'],$aMonthlyPeriod['last_day_of_last_month']);
		Context::set( 'performance_daily_prev_month', $aPerformanceInfoPrevMonth->period_status );
		Context::set( 'performance_gross_prev_month', $aPerformanceInfoPrevMonth->gross_status );

		$tEnd = $this->_getTime();
		$aElapsedTime['after_perf_info_prev_month'] = $tEnd - $tStart;
		
		$aPerformanceInfoCurMonth = $oSvestudioAdminModel->getPerformanceInfo($aMonthlyPeriod['first_day_of_this_month'],$aMonthlyPeriod['last_day_of_this_month']);
		$tEnd = $this->_getTime();
		$aElapsedTime['after_perf_info_cur_month'] = $tEnd - $tStart;

		Context::set( 'performance_daily_cur_month', $aPerformanceInfoCurMonth->period_status );
		Context::set( 'performance_brd_keyword_cur_month', $aPerformanceInfoCurMonth->brd_term_revenue_rank );
		Context::set( 'performance_general_keyword_cur_month', $aPerformanceInfoCurMonth->general_term_revenue_rank );
		Context::set( 'performance_gross_cur_month', $aPerformanceInfoCurMonth->gross_status );
		
		$oSkuPerfRst = $oSvestudioAdminModel->getSkuPerfInfoDaily($aMonthlyPeriod['first_day_of_this_month'],$aMonthlyPeriod['last_day_of_this_month']);
		if (!$oSkuPerfRst->toBool()) 
			return $oSkuPerfRst;
		$aSkuPerformance = $oSkuPerfRst->get( 'aDatePeriod' );
		Context::set( 'sku_perf_gross_cur_month', $aSkuPerformance['sku_gross'] );
		
		// retrieve annual status
		$sToday = date('Ymd');
		$sYearAgo = date('Ymd', strtotime($sToday.' -1 year'));
		
		$aSessionStatus = $oSvestudioAdminModel->getTotalSessionPeriod($sYearAgo,$sToday);
		Context::set( 'session_monthly', $aSessionStatus['monthly'] );

		$tEnd = $this->_getTime();
		$aElapsedTime['after_ttl_session_period'] = $tEnd - $tStart;

		$aMemberStatus = $oSvestudioAdminModel->getMemberStatusPeriod($sYearAgo,$sToday);
		Context::set( 'member_monthly', $aMemberStatus['monthly'] );

		$tEnd = $this->_getTime();
		$aElapsedTime['after_ttl_member_status_period'] = $tEnd - $tStart;

		$aDocStatus = $oSvestudioAdminModel->getDocStatusPeriod($sYearAgo,$sToday);
		Context::set( 'doc_monthly', $aDocStatus['monthly'] );

		$tEnd = $this->_getTime();
		$aElapsedTime['after_ttl_doc_stutus_period'] = $tEnd - $tStart;

		$aCommentStatus = $oSvestudioAdminModel->getCommentStatusPeriod($sYearAgo,$sToday);
		Context::set( 'comment_monthly', $aCommentStatus['monthly'] );

		$tEnd = $this->_getTime();
		$aElapsedTime['after_ttl_com_stutus_period'] = $tEnd - $tStart;

		$aInsiteSalesStatus = $oSvestudioAdminModel->getInsiteSalesStatusPeriod($sYearAgo,$sToday);
		Context::set( 'insite_sales_monthly', $aInsiteSalesStatus['sales_monthly'] );
		Context::set( 'insite_cp_monthly', $aInsiteSalesStatus['cp_monthly'] );

		$tEnd = $this->_getTime();
		$aElapsedTime['after_insite_sales_status_period'] = $tEnd - $tStart;

		Context::set( 'elapsed_info', $aElapsedTime );
	}
/**
 * @brief 
 **/    
    function getNotice()
	{
		//Retrieve recent news and set them into context
		$newest_news_url = sprintf("http://singleview.co.kr/?module=broadcast&act=getNewsagencyArticle&inst=notice&top=6&loc=%s", _XE_LOCATION_);
		$cache_file = sprintf("%sfiles/cache/svec_notice.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
		if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time())
		{
			// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing textmessageistration page
			// Ensure to access the textmessageistration page even though news cannot be displayed
			FileHandler::writeFile($cache_file,'');
			FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
		}

		if(file_exists($cache_file)) 
		{
			$oXml = new XeXmlParser();
			$buff = $oXml->parse(FileHandler::readFile($cache_file));

			$item = $buff->zbxe_news->item;
			if($item) 
			{
				if(!is_array($item)) 
				{
					$item = array($item);
				}

				foreach($item as $key => $val) {
					$obj = null;
					$obj->title = $val->body;
					$obj->date = $val->attrs->date;
					$obj->url = $val->attrs->url;
					$news[] = $obj;
				}
				Context::set('news', $news);
			}
			Context::set('released_version', $buff->zbxe_news->attrs->released_version);
			Context::set('download_link', $buff->zbxe_news->attrs->download_link);
		}
	}
/**
 * @brief 
 **/
	private function _getTime() 
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
}