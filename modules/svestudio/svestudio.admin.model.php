<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svestudioAdminModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svestudioAdminModel
**/ 
class svestudioAdminModel extends svestudio
{
	var $_g_sThisMonth;
	var $_g_nElapsedDaysOfThisMonth;
	var $_g_nDaysOfMonth;
/**
 * @brief Initialization
 **/
	public function init()
	{
		$this->_g_sToday = date('Ymd');
		$this->_g_sThisMonth = date('Ym');
		if( (int)date('d') == 1 )
			$this->_g_nElapsedDaysOfThisMonth = date('Ymd', strtotime('last day of last month'));
		else
			$this->_g_nElapsedDaysOfThisMonth = (int)date('d');
		$this->_g_nDaysOfMonth = $this->_getDaysInMonth((int)date('m'), (int)date('Y'));
	}
/**
 * @brief 
 **/
	public function getMidList($nPage)
	{
		$oModuleModel = &getModel('module');
		$oArgs = new stdClass();
		$oArgs->sort_index = "module_srl";
		$oArgs->page = $nPage;
		$oArgs->list_count = 20;
		$oArgs->page_count = 10;
		$oArgs->s_module_category_srl = Context::get('module_category_srl');
		$oRst = executeQueryArray('svestudio.getModInstList', $oArgs);
		$oRst->data = $oModuleModel->addModuleExtraVars($oRst->data);
		$oModuleCategory = $oModuleModel->getModuleCategories();
		$oRst->add( 'module_category', $oModuleCategory ); 
		return $oRst;
	}
/**
 * @brief ./svestudio.model.php::getMidConfig()와 동일한 내용이어야 함
 **/
	public function getMidConfig($nModuleSrl)
	{
		$oModuleModel = &getModel('module');
		$oMidInfo = $oModuleModel->getModuleInfoByModuleSrl($nModuleSrl);
		$oMidInfo->permitted_act_by_mid = unserialize( $oMidInfo->permitted_act_by_mid );
		return $oMidInfo;
	}
/**
 * @brief 
 **/
	public function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		return $oModuleModel->getModuleConfig('svestudio');
	}
/**
 * @brief 
 **/
	public function getOrderStatus()
	{
		$oSvorderAdminModel = &getAdminModel('svorder');
		if($oSvorderAdminModel)
			return $oSvorderAdminModel->getTotalStatus();
		else
			return null;
	}
/**
 * @brief 
 **/
	public function getHistoricalStatus()
	{
		$oGrossStatus = new stdClass();
		
		// sessions from google
		$oSessioArgs = new stdClass();
		$oSessioArgs->logdate = date('Ymd', strtotime('-1 day'));
		$oSessionRst = executeQuery('svestudio.getSessionLogDaily', $oSessioArgs );

		$oGrossStatus->session = new stdClass();
		$oGrossStatus->session->yesterdayCnt = $oSessionRst->data->tot_session;
		$oSessionRst = executeQuery('svestudio.getSessionLogDaily' );
		$oGrossStatus->session->totalCnt = $oSessionRst->data->tot_session;
		
		$sToday = date('Ymd');
		// Member Status
		$oMemberAdminModel = &getAdminModel('member');
		
		$oGrossStatus->member = new stdClass();
		$oGrossStatus->member->todayCnt = $oMemberAdminModel->getMemberCountByDate($sToday);
		$oGrossStatus->member->totalCnt = $oMemberAdminModel->getMemberCountByDate();
		// Document Status
		$oDocumentAdminModel = &getAdminModel('document');
		$aStatus = ['PUBLIC', 'SECRET'];

		$oGrossStatus->document = new stdClass();
		$oGrossStatus->document->todayCnt = $oDocumentAdminModel->getDocumentCountByDate($sToday, [], $aStatus);
		$oGrossStatus->document->totalCnt = $oDocumentAdminModel->getDocumentCountByDate('', [], $aStatus);
		// Comment Status
		$oCommentModel = &getModel('comment');
		$oGrossStatus->comment = new stdClass();
		$oGrossStatus->comment->todayCnt = $oCommentModel->getCommentCountByDate($sToday);
		$oGrossStatus->comment->totalCnt = $oCommentModel->getCommentCountByDate();
		// svshop
		$oSvorderAdminModel = &getAdminModel('svorder');
		if($oSvorderAdminModel)
		{
			$oGrossStatus->sales = new stdClass();
			$oGrossSalesInfo = $oSvorderAdminModel->getTodaySalesInfo();
			$oGrossStatus->sales->todayTrs = $oGrossSalesInfo->count;
			$oGrossStatus->sales->todayRev = $oGrossSalesInfo->amount;
			$oGrossSalesInfo = $oSvorderAdminModel->getGrossSalesInfo();
			$oGrossStatus->sales->totalTrs = $oGrossSalesInfo->count;
			$oGrossStatus->sales->totalRev = $oGrossSalesInfo->amount;
		}
		return $oGrossStatus;
	}
/**
 * @brief 
 **/
	public function getMontlyPeriod($sYearMonth=null)
	{
		if( $sYearMonth )
		{
			$aParsedDate = date_parse_from_format('Ym', $sYearMonth);
			$timestamp = mktime(0,0,0,$aParsedDate['month'], '01', $aParsedDate['year']);

			$sFirstDayLastMonth = date('Ymd', strtotime('-1 months', $timestamp));
			$sLatDayLastMonth = date('Ymt', strtotime('-1 months', $timestamp));
			$sFirstDayThisMonth = date('Ym01', $timestamp); // hard-coded '01' for first day
			$sLatDayThisMonth  = date('Ymt', $timestamp);
		}
		else
		{
			// retrieve last month period
			if( (int)date('d') == 1 )
			{
				$sFirstDayLastMonth = date('Ymd', strtotime('-2 months'));
				$sLatDayLastMonth = date('Ymt', strtotime('-2 months'));
			}
			else
			{
				$sFirstDayLastMonth = date('Ymd', strtotime('first day of last month'));
				$sLatDayLastMonth = date('Ymd', strtotime('last day of last month'));
			}

			// retrieve current month period
			if( (int)date('d') == 1 )
			{
				$sFirstDayThisMonth = date('Ymd', strtotime('first day of last month'));
				$sLatDayThisMonth = date('Ymd', strtotime('last day of last month'));
			}
			else
			{
				$sFirstDayThisMonth = date('Ym01'); // hard-coded '01' for first day
				$sLatDayThisMonth  = date('Ymt');
			}
		}
		return ['first_day_of_last_month'=>$sFirstDayLastMonth, 'last_day_of_last_month'=>$sLatDayLastMonth, 
				'first_day_of_this_month'=>$sFirstDayThisMonth, 'last_day_of_this_month'=>$sLatDayThisMonth];
	}
/**
 * @brief 
 **/
	public function getSkuPerfInfoDaily($sDateFrom,$sDateTo)
	{
		$aDatePeriod = $this->_getDateRangeArray($sDateFrom,$sDateTo);
		$oSvorderAdminModel = &getAdminModel('svorder');
		$oSvorderModel = &getModel('svorder');
		if($oSvorderAdminModel)
		{
			$nTotalSales = 0;
			$aWeeklyGrossSale = [];
			$aMonthlySkuPerformance = [];
			$aAllItemList = [];
			$aDailyPerformanceByItem = [];

			$oConfig = $this->getModuleConfig();
			$aRemoveWord = explode(',', $oConfig->svestudio_sku_remove_word);
			
			$oConfig = $oSvorderModel->getModuleConfig();
			require_once(_XE_PATH_.'modules/svorder/svorder.order_update.php');
			$oParams = new stdClass();
			$oParams->oSvorderConfig = $oConfig;
			$oOrder = new svorderUpdateOrder($oParams );
			foreach( $aDatePeriod as $date=>$val )
			{
				$aSingledayItemPerformance = [];
				$oRst = $oSvorderAdminModel->getOrderInfoDaily($date);
				foreach( $oRst as $rst_key=>$rst_val )
				{
					$oLoadRst = $oOrder->loadSvOrder($rst_val->order_srl);
					if (!$oLoadRst->toBool()) 
						return $oLoadRst;
					unset( $oLoadRst );
					
					$aCartList = $oOrder->getCartItemList();
					foreach( $aCartList as $item_key => $item_val )
					{
						foreach( $aRemoveWord as $nWordIdx=>$sRemoveWord )
							$item_val->item_name = trim(str_replace($sRemoveWord, '', $item_val->item_name));
						
						$nSingleRevenue = (int)$item_val->quantity * (int)$item_val->discounted_price;
                        
                        if(is_null($aSingledayItemPerformance[$item_val->item_name]))
                            $aSingledayItemPerformance[$item_val->item_name] = new stdClass();
						$aSingledayItemPerformance[$item_val->item_name]->amnt += $nSingleRevenue;
						$aSingledayItemPerformance[$item_val->item_name]->qty += (int)$item_val->quantity;
						$nTotalSales += $nSingleRevenue;
						
						// price and qty info must be maintained to estimate monthly revneue
                        if(is_null($aMonthlySkuPerformance[$item_val->item_name]))
                            $aMonthlySkuPerformance[$item_val->item_name] = new stdClass();
						$aMonthlySkuPerformance[$item_val->item_name]->amnt += $nSingleRevenue;//(int)$item_val->discounted_price;
						$aMonthlySkuPerformance[$item_val->item_name]->price = (int)$item_val->price;
						$aMonthlySkuPerformance[$item_val->item_name]->qty += (int)$item_val->quantity;

						$aWeeklyGrossSales[$val->wknum][$sItemName] += $nSingleRevenue;
						$aWeekdailyGrossSales[$val->dayname][$sItemName] += $nSingleRevenue;

						// array for listing all items
						$aAllItemList[$item_val->item_name] = 1;
					}
				}
				$val->sku_perf = $aSingledayItemPerformance;
			}
			unset( $oOrder );
			$nToday = (int)date('Ymd');

			// rearrange array into item centered period data
			foreach( $aAllItemList as $sItemName=>$dummy )
			{
				$aDays = [];
				foreach( $aDatePeriod as $nDateIdx=>$oDateVal )
				{
					if( $nToday < $nDateIdx )
						continue;
                    if(is_null($aDays[$nDateIdx]))
                        $aDays[$nDateIdx] = new stdClass();
                    $aDays[$nDateIdx]->rev = 0;
					$aDays[$nDateIdx]->qty = 0;
					foreach( $oDateVal->sku_perf as $sItemNamePerf=>$oDateVal )//$nRevenue)
					{
						if( $sItemName == $sItemNamePerf )
						{
							$aDays[$nDateIdx]->rev = $oDateVal->amnt;//$nRevenue;
							$aDays[$nDateIdx]->qty = $oDateVal->qty;;
						}
					}
				}
				$aDailyPerformanceByItem[$sItemName] = $aDays;
			}
			$aDatePeriod['gross_sales'] = $nTotalSales;
			$aRevenueRankedDailyPerformanceByItem = [];

			// estimate this month fullfillment & monthly revenue rank sort
			$aRevenueRankedMonthlySkuPerformance = [];
			if( count( $aMonthlySkuPerformance ) )
			{
				$aRevenueRank = [];
				foreach( $aMonthlySkuPerformance as $sItemName => $oItemInfo )
				{
					//$oItemInfo->revenue = (int)$oItemInfo->qty * $oItemInfo->discounted_price;
					$aRevenueRank[$sItemName]=$oItemInfo->amnt;
					$oItemInfo->estimated_revenue = (int)($oItemInfo->qty / $this->_g_nElapsedDaysOfThisMonth * $this->_g_nDaysOfMonth) * $oItemInfo->price;
				}
				arsort($aRevenueRank);
				foreach( $aRevenueRank as $sItemName => $oNullage )
				{
					$aRevenueRankedMonthlySkuPerformance[$sItemName] = $aMonthlySkuPerformance[$sItemName];
					$aRevenueRankedDailyPerformanceByItem[$sItemName] = $aDailyPerformanceByItem[$sItemName];
				}
			}
			$aDatePeriod['by_item'] = $aRevenueRankedDailyPerformanceByItem;
			$aDatePeriod['sku_weekly'] = $aWeeklyGrossSales;
			$aDatePeriod['sku_weekdaily'] = $aWeekdailyGrossSales;
			$aDatePeriod['sku_gross'] = $aRevenueRankedMonthlySkuPerformance;
		}
		$oRst = new BaseObject();
		$oRst->add( 'aDatePeriod', $aDatePeriod );
		return $oRst;
	}
/**
 * @brief 
 **/
	public function getMediaPerformanceInfo($sDateFrom,$sDateTo)
	{
		$aDatePeriod = $this->_getDateRangeArray($sDateFrom,$sDateTo);

		$aSourceMediumGrossImpression = [];
		$aSourceMediumGrossCost = [];
		$aSourceMediumGrossAgencyCost = [];
		$aSourceMediumGrossSession = [];
		$aSourceMediumGrossNewSession = [];
		$aSourceMediumGrossClick = [];
		$aSourceMediumGrossPvs = [];
		$aSourceMediumGrossDurSec = [];
		$aSourceMediumGrossPvsHidden = [];
		$aSourceMediumGrossDurSecHidden = [];
		$aSourceMediumGrossCpc = [];
		$aSourceMediumGrossRevenue = [];
		$aSourceMediumGrossRoas = [];
		$aSourceMediumGrossTrs = [];
		$aSourceMediumGrossConvRate = [];

		$aSourceMediumMtdImpression = [];
		$aSourceMediumMtdCost = [];
		$aSourceMediumMtdAgencyCost = [];
		$aSourceMediumMtdSession = [];
		$aSourceMediumMtdNewSession = [];
		$aSourceMediumMtdClick = [];
		$aSourceMediumMtdPvs = [];
		$aSourceMediumMtdDurSec = [];
		$aSourceMediumMtdPvsHidden = [];
		$aSourceMediumMtdDurSecHidden = [];
		$aSourceMediumMtdCpc = [];
		$aSourceMediumMtdRevenue = [];
		$aSourceMediumMtdRoas = [];
		$aSourceMediumMtdTrs = [];
		$aSourceMediumMtdConvRate = [];

		foreach( $aDatePeriod as $sDate=>$PeriodVal )
		{
			$args->logdate = $sDate;
			$output = executeQueryArray('svestudio.getMediaPerformanceLogDaily', $args);
			foreach($output->data as $nDataKey=>$oRow)
			{
				$sUa = $oRow->media_ua;
				$sRstType = $oRow->media_rst_type;
				$bBrd = (int)$oRow->media_brd;
				$sTerm = $oRow->media_term;

				$nMediaRawCost = (int)$oRow->media_raw_cost;
				$nMediaAgencyCost = (int)$oRow->media_agency_cost;
				$nMediaCostVat = (int)$oRow->media_cost_vat;
				$nMediaGrossCost = $nMediaRawCost + $nMediaAgencyCost +	$nMediaCostVat;

				$nMediaImp = (int)$oRow->media_imp;
				$nMediaClk = (int)$oRow->media_click;
				$nSession = (int)$oRow->in_site_tot_session;
				$nBounce = (int)$oRow->in_site_tot_bounce;
				
				$nDurationSec = (int)$oRow->in_site_tot_duration_sec;
				$nPvs = (int)$oRow->in_site_tot_pvs;

				$nNew = (int)$oRow->in_site_tot_new;
				$nRevenue = (int)$oRow->in_site_revenue;
				$nTransactions = (int)$oRow->in_site_trs;
				
				$bProcWithoutCost = false;
				$sSourceMediumId = $oRow->media_source.'_'.$oRow->media_media;
				if( $sSourceMediumId == 'naver_display' || $sSourceMediumId == 'facebook_cpc' )
					$bProcWithoutCost = true;

				if( $nMediaGrossCost > 0 || $bProcWithoutCost )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$aSourceMediumGrossCost[$sSourceMediumId] += $nMediaGrossCost; // sum to sort
						$PeriodVal->media_gross_cost['gross'] += $nMediaGrossCost;
						$PeriodVal->media_gross_cost[$oRow->media_source]['gross'] += $nMediaGrossCost;
						$PeriodVal->media_gross_cost[$oRow->media_source][$oRow->media_media] += $nMediaGrossCost;
						////// 대행 수수료 분리 계산 시작 ///////////////////
						$nSepatedMediaAgencyCost = (int)($nMediaAgencyCost*1.1);
						$aSourceMediumGrossAgencyCost[$sSourceMediumId] += $nSepatedMediaAgencyCost;
						$PeriodVal->media_gross_agency_cost['gross'] += $nSepatedMediaAgencyCost;
						$PeriodVal->media_gross_agency_cost[$oRow->media_source]['gross'] += $nSepatedMediaAgencyCost;
						$PeriodVal->media_gross_agency_cost[$oRow->media_source][$oRow->media_media] += $nSepatedMediaAgencyCost;
						////// 대행 수수료 분리 계산 끝 ///////////////////
						if( $sUa == 'M' )
						{
							$PeriodVal->media_gross_cost_mob['gross'] += $nMediaGrossCost;
							$PeriodVal->media_gross_cost_mob[$oRow->media_source]['gross'] += $nMediaGrossCost;
							$PeriodVal->media_gross_cost_mob[$oRow->media_source][$oRow->media_media] += $nMediaGrossCost;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->media_gross_cost_pc['gross'] += $nMediaGrossCost;
							$PeriodVal->media_gross_cost_pc[$oRow->media_source]['gross'] += $nMediaGrossCost;
							$PeriodVal->media_gross_cost_pc[$oRow->media_source][$oRow->media_media] += $nMediaGrossCost;
						}

						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
						{
							$aSourceMediumMtdCost[$sSourceMediumId] += $nMediaGrossCost;
							$aSourceMediumMtdAgencyCost[$sSourceMediumId] += $nSepatedMediaAgencyCost; ////////////////////
						}
					}
				}

				if( ( $nMediaImp > 0 && $nMediaGrossCost > 0 ) || $bProcWithoutCost )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$aSourceMediumGrossImpression[$sSourceMediumId] += $nMediaImp;
						$PeriodVal->media_gross_imp['gross'] += $nMediaImp;
						$PeriodVal->media_gross_imp[$oRow->media_source]['gross'] += $nMediaImp;
						$PeriodVal->media_gross_imp[$oRow->media_source][$oRow->media_media] += $nMediaImp;
						if( $sUa == 'M' )
						{
							$PeriodVal->media_gross_imp_mob['gross'] += $nMediaImp;
							$PeriodVal->media_gross_imp_mob[$oRow->media_source]['gross'] += $nMediaImp;
							$PeriodVal->media_gross_imp_mob[$oRow->media_source][$oRow->media_media] += $nMediaImp;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->media_gross_imp_pc['gross'] += $nMediaImp;
							$PeriodVal->media_gross_imp_pc[$oRow->media_source]['gross'] += $nMediaImp;
							$PeriodVal->media_gross_imp_pc[$oRow->media_source][$oRow->media_media] += $nMediaImp;
						}

						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$aSourceMediumMtdImpression[$sSourceMediumId] += $nMediaImp; 
					}
				}

				if( ( $nMediaClk > 0 && $nMediaGrossCost > 0 ) || $bProcWithoutCost  )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$aSourceMediumGrossClick[$sSourceMediumId] += $nMediaClk;
						$PeriodVal->media_gross_click['gross'] += $nMediaClk;
						$PeriodVal->media_gross_click[$oRow->media_source]['gross'] += $nMediaClk;
						$PeriodVal->media_gross_click[$oRow->media_source][$oRow->media_media] += $nMediaClk;

						if( $sUa == 'M' )
						{
							$PeriodVal->media_gross_click_mob['gross'] += $nMediaClk;
							$PeriodVal->media_gross_click_mob[$oRow->media_source]['gross'] += $nMediaClk;
							$PeriodVal->media_gross_click_mob[$oRow->media_source][$oRow->media_media] += $nMediaClk;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->media_gross_click_pc['gross'] += $nMediaClk;
							$PeriodVal->media_gross_click_pc[$oRow->media_source]['gross'] += $nMediaClk;
							$PeriodVal->media_gross_click_pc[$oRow->media_source][$oRow->media_media] += $nMediaClk;
						}

						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$aSourceMediumMtdClick[$sSourceMediumId] += $nMediaClk; 
					}
				}

				if( $nSession > 0 || $bProcWithoutCost )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$aSourceMediumGrossSession[$sSourceMediumId] += $nSession;
						$PeriodVal->in_site_tot_session['gross'] += $nSession;
						$PeriodVal->in_site_tot_session[$oRow->media_source]['gross'] += $nSession;
						$PeriodVal->in_site_tot_session[$oRow->media_source][$oRow->media_media] += $nSession;

						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$nMtdSession += $nSession;

						if( $sUa == 'M' )
						{
							$PeriodVal->in_site_tot_session_mob['gross'] += $nSession;
							$PeriodVal->in_site_tot_session_mob[$oRow->media_source]['gross'] += $nSession;
							$PeriodVal->in_site_tot_session_mob[$oRow->media_source][$oRow->media_media] += $nSession;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->in_site_tot_session_pc['gross'] += $nSession;
							$PeriodVal->in_site_tot_session_pc[$oRow->media_source]['gross'] += $nSession;
							$PeriodVal->in_site_tot_session_pc[$oRow->media_source][$oRow->media_media] += $nSession;
						}

						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$aSourceMediumMtdSession[$sSourceMediumId] += $nSession; 
					}
				}

				if( ( $nBounce > 0 && $nMediaGrossCost > 0 ) || $bProcWithoutCost )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$PeriodVal->in_site_tot_bounce['gross'] += $nBounce;
						$PeriodVal->in_site_tot_bounce[$oRow->media_source]['gross'] += $nBounce;
						$PeriodVal->in_site_tot_bounce[$oRow->media_source][$oRow->media_media] += $nBounce;
						
						if( $sUa == 'M' )
						{
							$PeriodVal->in_site_tot_bounce_mob['gross'] += $nBounce;
							$PeriodVal->in_site_tot_bounce_mob[$oRow->media_source]['gross'] += $nBounce;
							$PeriodVal->in_site_tot_bounce_mob[$oRow->media_source][$oRow->media_media] += $nBounce;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->in_site_tot_bounce_pc['gross'] += $nBounce;
							$PeriodVal->in_site_tot_bounce_pc[$oRow->media_source]['gross'] += $nBounce;
							$PeriodVal->in_site_tot_bounce_pc[$oRow->media_source][$oRow->media_media] += $nBounce;
						}
					}
				}

				if( ( $nNew > 0 && $nMediaGrossCost > 0 ) || $bProcWithoutCost )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$PeriodVal->in_site_tot_new['gross'] += $nNew;
						$aSourceMediumGrossNewSession[$sSourceMediumId] += $nNew;
						$PeriodVal->in_site_tot_new[$oRow->media_source]['gross'] += $nNew;
						$PeriodVal->in_site_tot_new[$oRow->media_source][$oRow->media_media] += $nNew;
						if( $sUa == 'M' )
						{
							$PeriodVal->in_site_tot_new_mob['gross'] += $nNew;
							$PeriodVal->in_site_tot_new_mob[$oRow->media_source]['gross'] += $nNew;
							$PeriodVal->in_site_tot_new_mob[$oRow->media_source][$oRow->media_media] += $nNew;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->in_site_tot_new_pc['gross'] += $nNew;
							$PeriodVal->in_site_tot_new_pc[$oRow->media_source]['gross'] += $nNew;
							$PeriodVal->in_site_tot_new_pc[$oRow->media_source][$oRow->media_media] += $nNew;
						}
						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$aSourceMediumMtdNewSession[$sSourceMediumId] += $nNew; 
					}
				}

				if( ( $nPvs > 0 && $nMediaGrossCost > 0 ) || $bProcWithoutCost )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$aSourceMediumGrossPvsHidden[$sSourceMediumId] += $nPvs;
						$PeriodVal->in_site_gross_pvs['gross'] += $nPvs;
						$PeriodVal->in_site_gross_pvs[$oRow->media_source]['gross'] += $nPvs;
						$PeriodVal->in_site_gross_pvs[$oRow->media_source][$oRow->media_media] += $nPvs;

						if( $sUa == 'M' )
						{
							$PeriodVal->in_site_gross_pvs_mob['gross'] += $nPvs;
							$PeriodVal->in_site_gross_pvs_mob[$oRow->media_source]['gross'] += $nPvs;
							$PeriodVal->in_site_gross_pvs_mob[$oRow->media_source][$oRow->media_media] += $nPvs;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->in_site_gross_pvs_pc['gross'] += $nPvs;
							$PeriodVal->in_site_gross_pvs_pc[$oRow->media_source]['gross'] += $nPvs;
							$PeriodVal->in_site_gross_pvs_pc[$oRow->media_source][$oRow->media_media] += $nPvs;
						}
						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$aSourceMediumMtdPvsHidden[$sSourceMediumId] += $nPvs; 
					}
				}

				if( ( $nDurationSec > 0 && $nMediaGrossCost > 0 ) || $bProcWithoutCost )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$aSourceMediumGrossDurSecHidden[$sSourceMediumId] += $nDurationSec;
						$PeriodVal->in_site_gross_dur_sec['gross'] += $nDurationSec;
						$PeriodVal->in_site_gross_dur_sec[$oRow->media_source]['gross'] += $nDurationSec;
						$PeriodVal->in_site_gross_dur_sec[$oRow->media_source][$oRow->media_media] += $nDurationSec;

						if( $sUa == 'M' )
						{
							$PeriodVal->in_site_gross_dur_sec_mob['gross'] += $nDurationSec;
							$PeriodVal->in_site_gross_dur_sec_mob[$oRow->media_source]['gross'] += $nDurationSec;
							$PeriodVal->in_site_gross_dur_sec_mob[$oRow->media_source][$oRow->media_media] += $nDurationSec;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->in_site_gross_dur_sec_pc['gross'] += $nDurationSec;
							$PeriodVal->in_site_gross_dur_sec_pc[$oRow->media_source]['gross'] += $nDurationSec;
							$PeriodVal->in_site_gross_dur_sec_pc[$oRow->media_source][$oRow->media_media] += $nDurationSec;
						}
						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$aSourceMediumMtdDurSecHidden[$sSourceMediumId] += $nDurationSec; 
					}
				}
					
				if( ( $nRevenue > 0 && $nMediaGrossCost > 0 ) || $bProcWithoutCost )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$aSourceMediumGrossRevenue[$sSourceMediumId] += $nRevenue;
						$PeriodVal->in_site_revenue['gross'] += $nRevenue;
						$PeriodVal->in_site_revenue[$oRow->media_source]['gross'] += $nRevenue;
						$PeriodVal->in_site_revenue[$oRow->media_source][$oRow->media_media] += $nRevenue;
						
						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$nMtdRevenue += $nRevenue;

						if( $sUa == 'M' )
						{
							$PeriodVal->in_site_revenue_mob['gross'] += $nRevenue;
							$PeriodVal->in_site_revenue_mob[$oRow->media_source]['gross'] += $nRevenue;
							$PeriodVal->in_site_revenue_mob[$oRow->media_source][$oRow->media_media] += $nRevenue;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->in_site_revenue_pc['gross'] += $nRevenue;
							$PeriodVal->in_site_revenue_pc[$oRow->media_source]['gross'] += $nRevenue;
							$PeriodVal->in_site_revenue_pc[$oRow->media_source][$oRow->media_media] += $nRevenue;
						}
						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$aSourceMediumMtdRevenue[$sSourceMediumId] += $nRevenue; 
					}
				}

				if( ( $nTransactions > 0 && $nMediaGrossCost > 0 ) || $bProcWithoutCost )
				{
					if( $sRstType == 'PS' || $sRstType == 'PNS' )
					{
						$aSourceMediumGrossTrs[$sSourceMediumId] += $nTransactions;
						$PeriodVal->in_site_trs['gross'] += $nTransactions;
						$PeriodVal->in_site_trs[$oRow->media_source]['gross'] += $nTransactions;
						$PeriodVal->in_site_trs[$oRow->media_source][$oRow->media_media] += $nTransactions;
						
						if( $sUa == 'M' )
						{
							$PeriodVal->in_site_trs_mob['gross'] += $nTransactions;
							$PeriodVal->in_site_trs_mob[$oRow->media_source]['gross'] += $nTransactions;
							$PeriodVal->in_site_trs_mob[$oRow->media_source][$oRow->media_media] += $nTransactions;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->in_site_trs_pc['gross'] += $nTransactions;
							$PeriodVal->in_site_trs_pc[$oRow->media_source]['gross'] += $nTransactions;
							$PeriodVal->in_site_trs_pc[$oRow->media_source][$oRow->media_media] += $nTransactions;
						}
						if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
							$aSourceMediumMtdTrs[$sSourceMediumId] += $nTransactions; 
					}
				}
			}
		}

		arsort($aSourceMediumGrossCost);
		// calculate roas and conv. %
		foreach( $aDatePeriod as $sDate=>$PeriodVal )
		{
			foreach( $PeriodVal->in_site_revenue as $sKey=>$aVal )
			{
				if( $sKey == 'gross' )
					continue;
			}
			foreach($aSourceMediumGrossCost as $sSourceMedia=>$nMediaGrossCost)
			{
				$aSm = explode('_', $sSourceMedia);
				$sSource = $aSm[0];
				$sMedia = $aSm[1];
				
				$PeriodVal->in_site_gross_pvs[$sSource]['gross'] = sprintf('%0.2f', $PeriodVal->in_site_gross_pvs[$sSource]['gross']/$PeriodVal->in_site_tot_session[$sSource]['gross']);
				$PeriodVal->in_site_gross_pvs[$sSource][$sMedia] = sprintf('%0.2f', $PeriodVal->in_site_gross_pvs[$sSource][$sMedia]/$PeriodVal->in_site_tot_session[$sSource][$sMedia]);

				$PeriodVal->in_site_gross_dur_sec[$sSource]['gross'] = sprintf('%0.2f', $PeriodVal->in_site_gross_dur_sec[$sSource]['gross']/$PeriodVal->in_site_tot_session[$sSource]['gross']);
				$PeriodVal->in_site_gross_dur_sec[$sSource][$sMedia] = sprintf('%0.2f', $PeriodVal->in_site_gross_dur_sec[$sSource][$sMedia]/$PeriodVal->in_site_tot_session[$sSource][$sMedia]);

				$PeriodVal->roas[$sSource]['gross'] = (int)($PeriodVal->in_site_revenue[$sSource]['gross']/$PeriodVal->media_gross_cost[$sSource]['gross']*100);
				$PeriodVal->roas[$sSource][$sMedia] = (int)($PeriodVal->in_site_revenue[$sSource][$sMedia]/$PeriodVal->media_gross_cost[$sSource][$sMedia]*100);

				$PeriodVal->conv_rate[$sSource]['gross'] = $PeriodVal->in_site_trs[$sSource]['gross']/$PeriodVal->in_site_tot_session[$sSource]['gross']*100;
				$PeriodVal->conv_rate[$sSource][$sMedia] = $PeriodVal->in_site_trs[$sSource][$sMedia]/$PeriodVal->in_site_tot_session[$sSource][$sMedia]*100;
				
				if( $sMedia == 'organic' )
				{
					$PeriodVal->media_cpc[$sSource]['gross'] = (int)($PeriodVal->media_gross_cost[$sSource]['gross'] / $PeriodVal->in_site_tot_session[$sSource]['gross']);
					$PeriodVal->media_cpc[$sSource][$sMedia] = (int)($PeriodVal->media_gross_cost[$sSource][$sMedia] / $PeriodVal->in_site_tot_session[$sSource][$sMedia]);
				}
				else
				{
					$PeriodVal->media_cpc[$sSource]['gross'] = (int)($PeriodVal->media_gross_cost[$sSource]['gross'] / $PeriodVal->media_gross_click[$sSource]['gross']);
					$PeriodVal->media_cpc[$sSource][$sMedia] = (int)($PeriodVal->media_gross_cost[$sSource][$sMedia] / $PeriodVal->media_gross_click[$sSource][$sMedia]);
				}
			}
		}

		// 당월의 gross 지표는 월마감 추정치로 대체함
		if( substr($sDateFrom, 0, 6) == $this->_g_sThisMonth && substr($sDateTo, 0, 6) == $this->_g_sThisMonth )
		{
			$nElapsedDays = $this->_g_nElapsedDaysOfThisMonth-1;
			foreach( $aSourceMediumGrossCost as $sSourceMedium=>$nCost )
				$aSourceMediumGrossCost[$sSourceMedium] = (int)($nCost / $nElapsedDays * $this->_g_nDaysOfMonth);

			foreach( $aSourceMediumGrossAgencyCost as $sSourceMedium=>$nCost )
				$aSourceMediumGrossAgencyCost[$sSourceMedium] = (int)($nCost / $nElapsedDays * $this->_g_nDaysOfMonth);

			foreach( $aSourceMediumGrossImpression as $sSourceMedium=>$nImp )
				$aSourceMediumGrossImpression[$sSourceMedium] = (int)($nImp / $nElapsedDays * $this->_g_nDaysOfMonth);
			
			foreach( $aSourceMediumGrossSession as $sSourceMedium=>$nSession )
				$aSourceMediumGrossSession[$sSourceMedium] = (int)($nSession / $nElapsedDays * $this->_g_nDaysOfMonth);

			foreach( $aSourceMediumGrossNewSession as $sSourceMedium=>$nSession )
				$aSourceMediumGrossNewSession[$sSourceMedium] = (int)($nSession / $nElapsedDays * $this->_g_nDaysOfMonth);

			foreach( $aSourceMediumGrossRevenue as $sSourceMedium=>$nRevenue )
				$aSourceMediumGrossRevenue[$sSourceMedium] = (int)($nRevenue / $nElapsedDays * $this->_g_nDaysOfMonth);
		}
		// to calculate a base number of progress bar by each souce medium
		$nMtdCost = 0;
		$nMtdAgencyCost = 0;
		$nMtdImpression = 0;
		$nMtdSession = 0;
		$nMtdNewSession = 0;
		$nMtdRevenue = 0;

		foreach($aSourceMediumGrossCost as $sSourceMedia=>$nMediaGrossCost)
		{
			$nMtdCost += $aSourceMediumMtdCost[$sSourceMedia];
			$nMtdAgencyCost += $aSourceMediumMtdAgencyCost[$sSourceMedia];
			$nMtdImpression += $aSourceMediumMtdImpression[$sSourceMedia];
			$nMtdSession +=  $aSourceMediumMtdSession[$sSourceMedia];
			$nMtdNewSession +=  $aSourceMediumMtdNewSession[$sSourceMedia];
			$nMtdRevenue +=  $aSourceMediumMtdRevenue[$sSourceMedia];
			
			if( $sSourceMedia == 'naver_organic' )
				$aSourceMediumGrossCpc[$sSourceMedia] = (int)($aSourceMediumGrossCost[$sSourceMedia]/$aSourceMediumGrossSession[$sSourceMedia]);
			else
				$aSourceMediumGrossCpc[$sSourceMedia] = (int)($aSourceMediumGrossCost[$sSourceMedia]/$aSourceMediumGrossClick[$sSourceMedia]);
			
			$aSourceMediumGrossRoas[$sSourceMedia] = sprintf('%0.2f%%', $aSourceMediumGrossRevenue[$sSourceMedia]/$aSourceMediumGrossCost[$sSourceMedia]*100);
			$aSourceMediumGrossConvRate[$sSourceMedia] = sprintf('%0.2f%%', $aSourceMediumGrossTrs[$sSourceMedia]/$aSourceMediumGrossSession[$sSourceMedia]*100);
			$aSourceMediumGrossPvs[$sSourceMedia] = sprintf('%0.2f', $aSourceMediumGrossPvsHidden[$sSourceMedia]/$aSourceMediumGrossSession[$sSourceMedia]);
			$aSourceMediumGrossDurSec[$sSourceMedia] = sprintf('%0.2f', $aSourceMediumGrossDurSecHidden[$sSourceMedia]/$aSourceMediumGrossSession[$sSourceMedia]);
			
			if( $sSourceMedia == 'naver_organic' )
				$aSourceMediumMtdCpc[$sSourceMedia] = (int)($aSourceMediumMtdCost[$sSourceMedia]/$aSourceMediumMtdSession[$sSourceMedia]);
			else
				$aSourceMediumMtdCpc[$sSourceMedia] = (int)($aSourceMediumMtdCost[$sSourceMedia]/$aSourceMediumMtdClick[$sSourceMedia]);

			$aSourceMediumMtdRoas[$sSourceMedia] = sprintf('%0.2f%%', $aSourceMediumMtdRevenue[$sSourceMedia]/$aSourceMediumMtdCost[$sSourceMedia]*100);
			$aSourceMediumMtdConvRate[$sSourceMedia] = sprintf('%0.2f%%', $aSourceMediumMtdTrs[$sSourceMedia]/$aSourceMediumMtdSession[$sSourceMedia]*100);
			$aSourceMediumMtdPvs[$sSourceMedia] = sprintf('%0.2f', $aSourceMediumMtdPvsHidden[$sSourceMedia]/$aSourceMediumMtdSession[$sSourceMedia]);
			$aSourceMediumMtdDurSec[$sSourceMedia] = sprintf('%0.2f', $aSourceMediumMtdDurSecHidden[$sSourceMedia]/$aSourceMediumMtdSession[$sSourceMedia]);
		}

		$oRst = new stdClass();

		$oRst->mtd_cost = $nMtdCost;
		$oRst->mtd_agency_cost = $nMtdAgencyCost;
		$oRst->mtd_impression = $nMtdImpression;
		$oRst->mtd_session = $nMtdSession;
		$oRst->mtd_new_session = $nMtdNewSession;
		$oRst->mtd_revenue = $nMtdRevenue;

		$oRst->source_medium_gross_impression = $aSourceMediumGrossImpression;
		$oRst->source_medium_gross_cost = $aSourceMediumGrossCost;
		$oRst->source_medium_gross_agency_cost = $aSourceMediumGrossAgencyCost;
		$oRst->source_medium_gross_session = $aSourceMediumGrossSession;
		$oRst->source_medium_gross_new_session = $aSourceMediumGrossNewSession;
		$oRst->source_medium_gross_pvs = $aSourceMediumGrossPvs;
		$oRst->source_medium_gross_dur_sec = $aSourceMediumGrossDurSec;
		$oRst->source_medium_gross_cpc = $aSourceMediumGrossCpc;
		$oRst->source_medium_gross_revenue = $aSourceMediumGrossRevenue;
		$oRst->source_medium_gross_roas = $aSourceMediumGrossRoas;
		$oRst->source_medium_gross_conv_rate = $aSourceMediumGrossConvRate;

		$oRst->source_medium_mtd_impression = $aSourceMediumMtdImpression;
		$oRst->source_medium_mtd_cost = $aSourceMediumMtdCost;
		$oRst->source_medium_mtd_agency_cost = $aSourceMediumMtdAgencyCost;
		$oRst->source_medium_mtd_session = $aSourceMediumMtdSession;
		$oRst->source_medium_mtd_new_session = $aSourceMediumMtdNewSession;
		$oRst->source_medium_mtd_pvs = $aSourceMediumMtdPvs;
		$oRst->source_medium_mtd_dur_sec = $aSourceMediumMtdDurSec;
		$oRst->source_medium_mtd_cpc = $aSourceMediumMtdCpc;
		$oRst->source_medium_mtd_revenue = $aSourceMediumMtdRevenue;
		$oRst->source_medium_mtd_roas = $aSourceMediumMtdRoas;
		$oRst->source_medium_mtd_conv_rate = $aSourceMediumMtdConvRate;

		$oRst->period_status = $aDatePeriod;
		return $oRst;
	}
/**
 * @brief 특정 기간 GA 기준 성과 데이터 가져오기
 **/
	public function getPerformanceInfo($sDateFrom,$sDateTo)
	{
		$aDatePeriod = $this->_getDateRangeArray($sDateFrom,$sDateTo);
		
		// MTD aggregation
		$nMtdMediCost = 0;
		$nMtdSession = 0;
		$nMtdRevenue = 0;
		$nMtdTrs = 0;
		$nMtdInsiteRevenue = 0;
		$nMtdInsiteTrs = 0;

		// gross aggregation
		$nTotalMediaCost = 0;
		$nTotalImp = 0;
		$nTotalClick = 0;
		$nTotalSession = 0;
		$nTotalEffSession = 0;
		$nTotalNewSession = 0;
		$nTotalRevenue = 0;
		$nTotalTrs = 0;
		$nTotalInsiteRevenue = 0;
		$nTotalInsiteTrs = 0;

		// mob aggregation
		$nTotalImpMob = 0;
		$nTotalClickMob = 0;
		$nTotalSessionMob = 0;
		$nTotalEffSessionMob = 0;
		$nTotalNewSessionMob = 0;

		// pc aggregation
		$nTotalImpPc = 0;
		$nTotalClickPc = 0;
		$nTotalSessionPc = 0;
		$nTotalEffSessionPc = 0;
		$nTotalNewSessionPc = 0;

		$aBrdedTermPerformance = [];
		$aGeneralTermPerformance = [];
		$oConfig = $this->getModuleConfig(); // 매출 데이터를 GA아니고 insite에서 가져오는 설정
		$oSvorderAdminModel = &getAdminModel('svorder'); // 매출 데이터를 GA아니고 insite에서 가져오는 설정
		foreach( $aDatePeriod as $sDate=>$PeriodVal )
		{
			$args = new stdClass();
			$args->logdate = $sDate;
			$output = executeQueryArray('svestudio.getPerformanceLogDaily', $args);
			foreach($output->data as $nDataKey=>$oRow)
			{
				$sUa = $oRow->media_ua;
				$sRstType = $oRow->media_rst_type;
				$bBrd = (int)$oRow->media_brd;
				$sTerm = $oRow->media_term;

				$nMediaRawCost = (int)$oRow->media_raw_cost;
				$nMediaAgencyCost = (int)$oRow->media_agency_cost;
				$nMediaCostVat = (int)$oRow->media_cost_vat;
				$nMediaGrossCost = $nMediaRawCost + $nMediaAgencyCost +	$nMediaCostVat;

				$nMediaImp = (int)$oRow->media_imp;
				$nMediaClk = (int)$oRow->media_click;
				$nSession = (int)$oRow->in_site_tot_session;
				$nBounce = (int)$oRow->in_site_tot_bounce;
				$nNew = (int)$oRow->in_site_tot_new;
				$nRevenue = (int)$oRow->in_site_revenue;
				$nTransactions = (int)$oRow->in_site_trs;

				if( $nMediaGrossCost > 0 )
				{
					$nTotalMediaCost += $nMediaGrossCost;
					$PeriodVal->media_gross_cost += $nMediaGrossCost;

					if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
						$nMtdMediCost += $nMediaGrossCost;
				}

				if( $nMediaImp > 0 )
				{
					$PeriodVal->media_imp += $nMediaImp;
					$nTotalImp += $nMediaImp;
					if( $sRstType == 'PS' )
					{
						if( $sUa == 'M' )
						{
							$PeriodVal->media_imp_mob += $nMediaImp;
							$nTotalImpMob += $nMediaImp;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->media_imp_pc += $nMediaImp;
							$nTotalImpPc += $nMediaImp;
						}
					}
				}

				if( $nMediaClk > 0 )
				{
					$PeriodVal->media_click += $nMediaClk;
					$nTotalClick += $nMediaClk;
					if( $sRstType == 'PS' )
					{
						if( $sUa == 'M' )
						{
							$PeriodVal->media_click_mob += $nMediaClk;
							$nTotalClickMob += $nMediaClk;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->media_click_pc += $nMediaClk;
							$nTotalClickPc += $nMediaClk;
						}
					}
				}

				if( $nSession > 0 )
				{
					$PeriodVal->in_site_tot_session += $nSession;
					$nTotalSession += $nSession;

					if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
						$nMtdSession += $nSession;

					if( $sRstType == 'PS' )
					{
						if( $sUa == 'M' )
						{
							$PeriodVal->in_site_tot_session_mob += $nSession;
							$nTotalSessionMob += $nSession;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->in_site_tot_session_pc += $nSession;
							$nTotalSessionPc += $nSession;
						}
					}
				}

				if( $nBounce > 0 )
				{
					$PeriodVal->in_site_tot_bounce += $nBounce;
					if( $sRstType == 'PS' )
					{
						if( $sUa == 'M' )
							$PeriodVal->in_site_tot_bounce_mob += $nBounce;
						else if( $sUa == 'P' )
							$PeriodVal->in_site_tot_bounce_pc += $nBounce;
					}
				}

				if( $nNew > 0 )
				{
					$PeriodVal->in_site_tot_new += $nNew;
					$nTotalNew += $nNew;
					if( $sRstType == 'PS' )
					{
						if( $sUa == 'M' )
						{
							$PeriodVal->in_site_tot_new_mob += $nNew;
							$nTotalNewSessionMob += $nNew;
						}
						else if( $sUa == 'P' )
						{
							$PeriodVal->in_site_tot_new_pc += $nNew;
							$nTotalNewSessionPc += $nNew;
						}
					}
				}
					
				if( $nRevenue > 0 )
				{
					$PeriodVal->in_site_revenue += $nRevenue;
					$nTotalRevenue += $nRevenue;
					if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
						$nMtdRevenue += $nRevenue;

					if( $sTerm != '|@|sv' && $sTerm != '(notset)' && $sTerm != '(notprovided)' && $sTerm != '(contenttargeting)' )
					{
						if( $bBrd == 1 )
							$aBrdedTermPerformance[$sTerm] += $nRevenue;
						elseif( $bBrd == 0 )
							$aGeneralTermPerformance[$sTerm] += $nRevenue;
					}
				}

				if( $nTransactions > 0 )
				{
					$PeriodVal->in_site_trs += $nTransactions;
					$nTotalTrs += $nTransactions;
					if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
						$nMtdTrs += $nTransactions;
				}

				if( $nSession > 0 && $nTransactions > 0 )
					$PeriodVal->conv_rate = $PeriodVal->in_site_trs / $PeriodVal->in_site_tot_session * 100;

				if( $nMediaClk > 0 && $nMediaImp > 0 )
				{
					$PeriodVal->media_ctr = $PeriodVal->media_click / $PeriodVal->media_imp * 100;
					$PeriodVal->media_ctr_mob = $PeriodVal->media_click_mob / $PeriodVal->media_imp_mob * 100;
					$PeriodVal->media_ctr_pc = $PeriodVal->media_click_pc / $PeriodVal->media_imp_pc * 100;
				}

				$PeriodVal->tot_eff_session = $PeriodVal->in_site_tot_session - $PeriodVal->in_site_tot_bounce;
				$PeriodVal->tot_eff_session_mob = $PeriodVal->in_site_tot_session_mob - $PeriodVal->in_site_tot_bounce_mob;
				$PeriodVal->tot_eff_session_pc = $PeriodVal->in_site_tot_session_pc - $PeriodVal->in_site_tot_bounce_pc;
			}
			if( $oConfig->revenue_referrence == 'insite' )
			{
				$oInsiteRevRst = $this->_getDailyInsiteRevenue($oSvorderAdminModel,$sDate);
				$PeriodVal->in_site_revenue = $oInsiteRevRst->nAmount;
				$PeriodVal->in_site_trs = $oInsiteRevRst->nTrs; // # of transactions

				$nTotalInsiteRevenue += $oInsiteRevRst->nAmount;
				if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
					$nMtdInsiteRevenue += $oInsiteRevRst->nAmount;
				
				$nTotalInsiteTrs += $oInsiteRevRst->nTrs;
				if( $this->_g_nElapsedDaysOfThisMonth > (int)substr($sDate,6,2) )
					$nMtdInsiteTrs += $oInsiteRevRst->nTrs;
			}
			if($PeriodVal->in_site_revenue && $PeriodVal->media_gross_cost)
				$nRoas = (int)($PeriodVal->in_site_revenue / $PeriodVal->media_gross_cost*100);
			else
				$nRoas = 0;
			$PeriodVal->roas = $nRoas;

			if($PeriodVal->in_site_trs && $in_site_tot_session->media_gross_cost)
				$fConvRate = $PeriodVal->in_site_trs / $PeriodVal->in_site_tot_session * 100;
			else
				$fConvRate = 0;
			$PeriodVal->conv_rate = $fConvRate;
			
			$nTotalEffSession += $PeriodVal->tot_eff_session; 
			$nTotalEffSessionMob += $PeriodVal->tot_eff_session_mob;
			$nTotalEffSessionPc += $PeriodVal->tot_eff_session_pc;
		}

		$aGrossStatus = [];
		$aGrossStatus['mtd_cost'] = $nMtdMediCost;
		$aGrossStatus['mtd_session'] = $nMtdSession;
		
		if( $oConfig->revenue_referrence == 'insite' )
		{
			$aGrossStatus['mtd_revenue'] = $nMtdInsiteRevenue;
			if($nMtdInsiteTrs && $nMtdSession)
				$aGrossStatus['mtd_conv_rate'] = sprintf("%.4f",$nMtdInsiteTrs/$nMtdSession);
			else
				$aGrossStatus['mtd_conv_rate'] = '0.0';
			if($nMtdInsiteRevenue && $nMtdMediCost)
				$aGrossStatus['mtd_roas'] = sprintf("%.4f",$nMtdInsiteRevenue/$nMtdMediCost);
			else
				$aGrossStatus['mtd_roas'] = '0.0';
		}
		else
		{
			$aGrossStatus['mtd_revenue'] = $nMtdRevenue;
			if($nMtdTrs && $nMtdSession)
				$aGrossStatus['mtd_conv_rate'] = sprintf("%.4f",$nMtdTrs/$nMtdSession);
			else
				$aGrossStatus['mtd_conv_rate'] = '0.0';
			if($nMtdRevenue && $nMtdMediCost)
				$aGrossStatus['mtd_roas'] = sprintf("%.4f",$nMtdRevenue/$nMtdMediCost);
			else
				$aGrossStatus['mtd_roas'] = '0.0';
		}
		$aGrossStatus['gross_cost'] = $nTotalMediaCost;
		$aGrossStatus['gross_imp'] = $nTotalImp;
		$aGrossStatus['gross_click'] = $nTotalClick;
		if($nTotalClick && $nTotalImp)
			$aGrossStatus['gross_ctr'] = sprintf("%.4f",$nTotalClick/$nTotalImp);
		else
			$aGrossStatus['gross_ctr'] = '0.0';
		$aGrossStatus['gross_session'] = $nTotalSession;
		$aGrossStatus['gross_eff_session'] = $nTotalEffSession;
		$aGrossStatus['gross_new_session'] = $nTotalNew;
		
		if( $oConfig->revenue_referrence == 'insite' )
		{
			$aGrossStatus['gross_revenue'] = $nTotalInsiteRevenue;
			if($nTotalInsiteTrs && $nTotalSession)
				$aGrossStatus['gross_conv_rate'] = sprintf("%.4f",$nTotalInsiteTrs/$nTotalSession);
			else
				$aGrossStatus['gross_conv_rate'] = '0.0';
			if($nTotalInsiteRevenue && $nTotalMediaCost)
				$aGrossStatus['gross_roas'] = sprintf("%.4f",$nTotalInsiteRevenue/$nTotalMediaCost);
			else
				$aGrossStatus['gross_roas'] = '0.0';
		}
		else
		{
			$aGrossStatus['gross_revenue'] = $nTotalRevenue;
			if($nTotalTrs && $nTotalSession)
				$aGrossStatus['gross_conv_rate'] = sprintf("%.4f",$nTotalTrs/$nTotalSession);
			else
				$aGrossStatus['gross_conv_rate'] = '0.0';
			if($nTotalRevenue && $nTotalMediaCost)
				$aGrossStatus['gross_roas'] = sprintf("%.4f",$nTotalRevenue/$nTotalMediaCost);
			else
				$aGrossStatus['gross_roas'] = '0.0';
		}

		$aGrossStatus['gross_imp_mob'] = $nTotalImpMob;
		$aGrossStatus['gross_click_mob'] = $nTotalClickMob;

		if($nTotalImpMob)
			$aGrossStatus['gross_ctr_mob'] = sprintf("%.4f",$nTotalClickMob/$nTotalImpMob);
		else
			$aGrossStatus['gross_ctr_mob'] = sprintf("%.4f",0);

		$aGrossStatus['gross_session_mob'] = $nTotalSessionMob;
		$aGrossStatus['gross_eff_session_mob'] = $nTotalEffSessionMob;
		$aGrossStatus['gross_new_session_mob'] = $nTotalNewSessionMob;

		$aGrossStatus['gross_imp_pc'] = $nTotalImpPc;
		$aGrossStatus['gross_click_pc'] = $nTotalClickPc;
		if($nTotalImpPc)
			$aGrossStatus['gross_ctr_pc'] = sprintf("%.4f",$nTotalClickPc/$nTotalImpPc);
		else
			$aGrossStatus['gross_ctr_pc'] = sprintf("%.4f",0);
		$aGrossStatus['gross_session_pc'] = $nTotalSessionPc;
		$aGrossStatus['gross_eff_session_pc'] = $nTotalEffSessionPc;
		$aGrossStatus['gross_new_session_pc'] = $nTotalNewSessionPc;
		
		arsort($aBrdedTermPerformance);
		arsort($aGeneralTermPerformance);
		$oRst = new stdClass();
		$oRst->period_status = $aDatePeriod;
		$oRst->gross_status = $aGrossStatus;
		$oRst->brd_term_revenue_rank = $aBrdedTermPerformance;
		$oRst->general_term_revenue_rank = $aGeneralTermPerformance;
		return $oRst;
	}
/**
 * @brief exclusive for retrieving monthly session
 **/
	public function getTotalSessionPeriod($sDateFrom,$sDateTo)
	{
		$aMonthlyPeriod = $this->_getMonthlyRangeArray($sDateFrom,$sDateTo);
		$nTotal = 0;
		$aMonthly = [];
		//$aWeekly = [];
		//$aWeekdaily = [];

		$dtObject = new DateTime(); // basically this month is on progress
		// decide on progress month
		if( (int)$dtObject->format('d') == 1 ) // if first day of the month
		{
			$dtObject->modify('-1 day'); // prev month is on progress
			array_pop( $aMonthlyPeriod ); // eliminate this month
		}
		$sOnProgressMonth = $dtObject->format('Ym');
		
		$oArgs = new stdClass();
		foreach( $aMonthlyPeriod as $nIdx => $sYrMo )
		{
			if( $sYrMo != $sOnProgressMonth ) // 진행 중월의 직전월까지는 단순 총계 작업
			{
				$oArgs->logdate = $sYrMo;
				$output = executeQuery('svestudio.getCompiledByMonth', $oArgs);
				$nSession = $output->data->tot_monthly_session;
				if( !$nSession )
				{
					$output = executeQueryArray('svestudio.getSessionLogMonthly', $oArgs);
					$nSession = (int)$output->data[0]->tot_session;
					$oArgs->tot_monthly_session = $nSession;
					$oInsertRst = executeQueryArray('svestudio.insertCompiledByMonth', $oArgs);
					unset( $oInsertRst );
				}
			}
			elseif( $sYrMo == $sOnProgressMonth ) // 진행 중 월은 직전일까지 총계 작업
			{
				$oArgs->logdate = $sYrMo;
				$oOldCompiledMonthlyLogRst = executeQuery('svestudio.getCompiledByMonth', $oArgs);
				$nSession = $oOldCompiledMonthlyLogRst->data->tot_monthly_session;
				$nRecCnt = count((array)$oOldCompiledMonthlyLogRst->data);
				$sLastUpdateDate = $oOldCompiledMonthlyLogRst->data->last_update_date;
				unset( $oOldCompiledMonthlyLogRst );

				$oLastDateRst = executeQueryArray('svestudio.getLastLogDate');
				$oLastLogRec = array_pop($oLastDateRst->data);
				$nLastLogDate = $oLastLogRec->logdate;
				unset( $oLastDateRst );
				unset( $oLastLogRec );

				if( $nRecCnt == 0 && $nSession == null ) // 기존 내역이 없으면 단순 총계 작업
				{
					$oMonthlyLogRst = executeQueryArray('svestudio.getSessionLogMonthly', $oArgs);
					$nSession = (int)$oMonthlyLogRst->data[0]->tot_session;
					$oArgs->tot_monthly_session = $nSession;
					$oArgs->last_update_date = $nLastLogDate;
					$oInsertRst = executeQueryArray('svestudio.insertCompiledByMonth', $oArgs);
					unset( $oInsertRst );
				}
				else // 기존 내역이 있으면 최종 갱신일 이후부터 추가
				{
					if( $this->_validateDate($sLastUpdateDate, 'Ymd') )
					{
						if( (int)$sLastUpdateDate < (int)$nLastLogDate ) // 최종 업데이트 일자보다 최종 로그 일자가 최신이면
						{
							// $sLastUpdateDate+1와 $sLastDayOfOnProgressMonth 의 결정 문제
							if( date('m', strtotime($sLastUpdateDate) == date('m', strtotime($this->_g_sToday)) ) ) // 최종 업데이트 일자가 오늘과 같은 달이면
								$sLastDayOfOnProgressMonth = (int)date('Ymd', strtotime('-1 day', strtotime($this->_g_sToday)));
							else // 최종 업데이트 일자가 전월 마지막 날이면
								$sLastDayOfOnProgressMonth = date('Ymt', strtotime($sLastUpdateDate));
//echo __FILE__.':'.__lINE__.'<BR>';
//var_dump(  );
//echo '<BR><BR>';
//var_dump(  );
//echo '<BR><BR>';
//var_dump( $this->_g_sToday);
//echo '<BR><BR>';
//var_dump( $nLastLogDate);
//echo '<BR><BR>';
//exit;
							
							for( $nLogDate = (int)date('Ymd', strtotime('+1 day', strtotime($sLastUpdateDate))); $nLogDate <= $sLastDayOfOnProgressMonth; $nLogDate++)
							{
								$oRetrieveArgs->logdate = $nLogDate;
								$oRetrieveRst = executeQueryArray('svestudio.getSessionLogDaily', $oRetrieveArgs);
								$nSession += (int)$oRetrieveRst->data[0]->tot_session;
//var_dump( $oRetrieveRst->data[0]->tot_session);
//echo '<BR><BR>';
								unset( $oRetrieveRst );
								unset( $oRetrieveArgs );

							}
							$oUpdateArgs->tot_monthly_session = $nSession;
							$oUpdateArgs->last_update_date = $nLastLogDate;
							$oUpdateArgs->logdate = $sYrMo;
							$oUpdateRst = executeQueryArray('svestudio.updateSessionLogMonthly', $oUpdateArgs);
							if (!$oUpdateRst->toBool()) 
								return $oUpdateRst;
							unset( $oUpdateRst );
							unset( $oUpdateArgs );
						}
					}
				}
			}
			$aMonthly[$sYrMo] = $nSession;
			//$aWeekly[$val->wknum] += $nSession;
			//$aWeekdaily[$val->dayname] += $nSession;
			$nTotal += $nSession;
		}

//		$aDatePeriod = $this->_getDateRangeArray($sDateFrom,$sDateTo);
//		$nTotal = 0;
//		$aMonthly = [];
//		$aWeekly = [];
//		$aWeekdaily = [];
//		foreach( $aDatePeriod as $date=>$val )
//		{
//			$sCacheByDateFile = _XE_PATH_.'files/svestudio/session/'.$date.'.php';
//			if( is_readable($sCacheByDateFile))
//				$nSession = FileHandler::readFile($sCacheByDateFile);
//			else
//			{
//				$args->logdate = $date;
//				$output = executeQueryArray('svestudio.getSessionLogDaily', $args);
//				$nSession = (int)$output->data[0]->tot_session;
//				if( (int)$date < (int)$this->_g_sToday )
//					$output = FileHandler::writeFile($sCacheByDateFile, $nSession);
//			}
//			$val->tot_session = $nSession;
//			$aMonthly[$val->mo] += $nSession;
//			$aWeekly[$val->wknum] += $nSession;
//			$aWeekdaily[$val->dayname] += $nSession;
//			$nTotal += $nSession;
//		}

		$aDatePeriod['gross_total'] = $nTotal;
		// estimate this month fullfillment
		if( count( $aMonthly ) )
			$aMonthly['Latest est.'] = (int)($aMonthly[$this->_g_sThisMonth] / $this->_g_nElapsedDaysOfThisMonth * $this->_g_nDaysOfMonth);
		$aDatePeriod['monthly'] = $aMonthly;
		//$aDatePeriod['weekly'] = $aWeekly;
		//$aDatePeriod['weekdaily'] = $aWeekdaily;
		return $aDatePeriod;
	}
/**
 * @brief 
 **/
	private function _getDailyInsiteRevenue($oSvorderAdminModel, $sDate)
	{
		$sCacheByDateFile = _XE_PATH_.'files/svestudio/sales/'.$sDate.'.php';
		if( is_readable($sCacheByDateFile))
		{
			$sSalesInfo = FileHandler::readFile($sCacheByDateFile);
			$oRst = unserialize($sSalesInfo);
		}
		else
		{
			$oRst = $oSvorderAdminModel->getSalesInfoDaily($sDate);
			if( (int)$date < (int)$this->_g_sToday - 7 ) // 7일 전 까지 실시간 계산
				$output = FileHandler::writeFile($sCacheByDateFile, serialize($oRst));
		}
		$oFinalRst = new stdClass();
		$oFinalRst->nAmount = $oRst->amount;
		$oFinalRst->nTrs = $oRst->count; // # of transactions
		if($oRst->amount && $oRst->count)
			$nCustomerPrice = (int)($oRst->amount / $oRst->count); // customer price
		else
			$nCustomerPrice = 0;
		$oFinalRst->nCustomerPrice = $nCustomerPrice;
		return $oFinalRst;
	}
/**
 * @brief 
 **/
	public function getInsiteSalesStatusPeriod($sDateFrom,$sDateTo)
	{
		$aDatePeriod = $this->_getDateRangeArray($sDateFrom,$sDateTo);
		$oSvorderAdminModel = &getAdminModel('svorder');
		if($oSvorderAdminModel)
		{
			$nTotalSales = 0;
			$nTotalTrs = 0;
			$aMonthlyGrossSales = [];
			$aWeeklyGrossSales = [];
			$aWeekdailyGrossSales = [];
			$aMonthlyGrossTrs = [];
			$aWeeklyGrossTrs = [];
			$aWeekdailyGrossTrs = [];
			$aMonthlyGrossCp = [];
			$aWeeklyGrossCp = [];
			$aWeekdailyGrossCp = [];

			//FileHandler::removeFilesInDir('./files/svestudio/sales/');
			foreach( $aDatePeriod as $date=>$val )
			{
				$oInsiteRevRst = $this->_getDailyInsiteRevenue($oSvorderAdminModel,$date);
				$val->amount = $oInsiteRevRst->nAmount;
				$val->trs = $oInsiteRevRst->nTrs; // # of transactions
				$val->cp = $oInsiteRevRst->nCustomerPrice;

//				$sCacheByDateFile = _XE_PATH_.'files/svestudio/sales/'.$date.'.php';
//				if( is_readable($sCacheByDateFile))
//				{
//					$sSalesInfo = FileHandler::readFile($sCacheByDateFile);
//					$oRst = unserialize($sSalesInfo);
//				}
//				else
//				{
//					$oRst = $oSvorderAdminModel->getSalesInfoDaily($date);
//					if( (int)$date < (int)$this->_g_sToday - 7 ) // 7일 전 까지 실시간 계산
//						$output = FileHandler::writeFile($sCacheByDateFile, serialize($oRst));
//				}
//				$val->amount = $oRst->amount;
//				$val->trs = $oRst->count; // # of transactions
//				$val->cp = (int)($oRst->amount / $oRst->count); // customer price
				$aMonthlyGrossSales[$val->mo] += $val->amount;
				$aWeeklyGrossSales[$val->wknum] += $val->amount;
				$aWeekdailyGrossSales[$val->dayname] += $val->amount;
				$nTotalSales += $val->amount;

				$aMonthlyGrossTrs[$val->mo] += $val->trs;
				$aWeeklyGrossTrs[$val->wknum] += $val->trs;
				$aWeekdailyGrossTrs[$val->dayname] += $val->trs;
				$nTotalTrs += $val->trs;
				
				if($aMonthlyGrossSales[$val->mo] && $aMonthlyGrossTrs[$val->mo])
					$aMonthlyGrossCp[$val->mo] = (int)($aMonthlyGrossSales[$val->mo] / $aMonthlyGrossTrs[$val->mo]);
				else
					$aMonthlyGrossCp[$val->mo] = 0;

				if($aMonthlyGrossSales[$val->wknum] && $aMonthlyGrossTrs[$val->wknum])
					$aWeeklyGrossCp[$val->wknum] = $aMonthlyGrossSales[$val->wknum] / $aMonthlyGrossTrs[$val->wknum];
				else
					$aWeeklyGrossCp[$val->wknum] = 0;

				if($aMonthlyGrossSales[$val->dayname] && $aMonthlyGrossTrs[$val->dayname])
					$aWeekdailyGrossCp[$val->dayname] = $aMonthlyGrossSales[$val->dayname] / $aMonthlyGrossTrs[$val->dayname];
				else
					$aWeekdailyGrossCp[$val->dayname] = 0;
			}
			$aDatePeriod['gross_sales'] = $nTotalSales;
			$aDatePeriod['gross_trs'] = $nTotalTrs;
			
			if($nTotalSales && $nTotalTrs)
				$aDatePeriod['gross_cp'] = (int)($nTotalSales/$nTotalTrs);
			else
				$aDatePeriod['gross_cp'] = 0;

			// estimate this month fullfillment
			if( count( $aMonthlyGrossSales ) )
				$aMonthlyGrossSales['Latest est.'] = (int)($aMonthlyGrossSales[$this->_g_sThisMonth] / $this->_g_nElapsedDaysOfThisMonth * $this->_g_nDaysOfMonth);

			if( count( $aMonthlyGrossTrs ) )
				$aMonthlyGrossTrs['Latest est.'] = (int)($aMonthlyGrossTrs[$this->_g_sThisMonth] / $this->_g_nElapsedDaysOfThisMonth * $this->_g_nDaysOfMonth);

			$aDatePeriod['sales_monthly'] = $aMonthlyGrossSales;
			$aDatePeriod['sales_weekly'] = $aWeeklyGrossSales;
			$aDatePeriod['sales_weekdaily'] = $aWeekdailyGrossSales;

			$aDatePeriod['trs_monthly'] = $aMonthlyGrossTrs;
			$aDatePeriod['trs_weekly'] = $aWeeklyGrossTrs;
			$aDatePeriod['trs_weekdaily'] = $aWeekdailyGrossTrs;

			$aDatePeriod['cp_monthly'] = $aMonthlyGrossCp;
			$aDatePeriod['cp_weekly'] = $aWeeklyGrossCp;
			$aDatePeriod['cp_weekdaily'] = $aWeekdailyGrossCp;
		}
		return $aDatePeriod;
	}
/**
 * @brief 
 **/
	public function getCommentStatusPeriod($sDateFrom,$sDateTo)
	{
		$aDatePeriod = $this->_getDateRangeArray($sDateFrom,$sDateTo);
		$oCommentModel = &getModel('comment');
		$nTotal = 0;
		$aMonthly = [];
		$aWeekly = [];
		$aWeekdaily = [];

		foreach( $aDatePeriod as $date=>$val )
		{
			$sCacheByDateFile = _XE_PATH_.'files/svestudio/comment/'.$date.'.php';
			if( is_readable($sCacheByDateFile))
				$nComCnt = FileHandler::readFile($sCacheByDateFile);
			else
			{
				$nComCnt = $oCommentModel->getCommentCountByDate($date);

				if( $date != $this->_g_sToday )
					$output = FileHandler::writeFile($sCacheByDateFile, $nComCnt);
			}
			$val->com_cnt = $nComCnt;// $oCommentModel->getCommentCountByDate($date);
			$aMonthly[$val->mo] += $val->com_cnt;
			$aWeekly[$val->wknum] += $val->com_cnt;
			$aWeekdaily[$val->dayname] += $val->com_cnt;
			$nTotal += $val->com_cnt;
		}
		$aDatePeriod['gross_total'] = $nTotal;

		// estimate this month fullfillment
		if( count( $aMonthly ) )
			$aMonthly['Latest est.'] = (int)($aMonthly[$this->_g_sThisMonth] / $this->_g_nElapsedDaysOfThisMonth * $this->_g_nDaysOfMonth);
		$aDatePeriod['monthly'] = $aMonthly;
		$aDatePeriod['weekly'] = $aWeekly;
		$aDatePeriod['weekdaily'] = $aWeekdaily;
		return $aDatePeriod;
	}
/**
 * @brief 
 **/
	public function getDocStatusPeriod($sDateFrom,$sDateTo)
	{
		$aDatePeriod = $this->_getDateRangeArray($sDateFrom,$sDateTo);
		$oDocumentAdminModel = &getAdminModel('document');
		$statusList = ['PUBLIC', 'SECRET'];
		$nTotal = 0;
		$aMonthly = [];
		$aWeekly = [];
		$aWeekdaily = [];

		foreach( $aDatePeriod as $date=>$val )
		{
			$sCacheByDateFile = _XE_PATH_.'files/svestudio/document/'.$date.'.php';
			if( is_readable($sCacheByDateFile))
				$nDocCnt = FileHandler::readFile($sCacheByDateFile);
			else
			{
				$nDocCnt = $oDocumentAdminModel->getDocumentCountByDate($date, [], $statusList);

				if( $date != $this->_g_sToday )
					$output = FileHandler::writeFile($sCacheByDateFile, $nDocCnt);
			}
			$val->doc_cnt = $nDocCnt;
			$aMonthly[$val->mo] += $val->doc_cnt;
			$aWeekly[$val->wknum] += $val->doc_cnt;
			$aWeekdaily[$val->dayname] += $val->doc_cnt;
			$nTotal += $val->doc_cnt;
		}
		$aDatePeriod['gross_total'] = $nTotal;

		// estimate this month fullfillment
		if( count( $aMonthly ) )
			$aMonthly['Latest est.'] = (int)($aMonthly[$this->_g_sThisMonth] / $this->_g_nElapsedDaysOfThisMonth * $this->_g_nDaysOfMonth);
		$aDatePeriod['monthly'] = $aMonthly;
		$aDatePeriod['weekly'] = $aWeekly;
		$aDatePeriod['weekdaily'] = $aWeekdaily;
		return $aDatePeriod;
	}
/**
 * @brief 
 **/
	public function getMemberStatusPeriod($sDateFrom,$sDateTo)
	{
		$aDatePeriod = $this->_getDateRangeArray($sDateFrom,$sDateTo);
		$oMemberAdminModel = &getAdminModel('member');
		$nTotal = 0;
		$aMonthly = [];
		$aWeekly = [];
		$aWeekdaily = [];

		foreach( $aDatePeriod as $date=>$val )
		{
			$sCacheByDateFile = _XE_PATH_.'files/svestudio/member/'.$date.'.php';
			if( is_readable($sCacheByDateFile))
				$nMemCnt = FileHandler::readFile($sCacheByDateFile);
			else
			{
				$nMemCnt = $oMemberAdminModel->getMemberCountByDate($date);
				if( $date != $this->_g_sToday )
					$output = FileHandler::writeFile($sCacheByDateFile, $nMemCnt);
			}
			$val->mem_cnt = $nMemCnt;
			$aMonthly[$val->mo] += $val->mem_cnt;
			$aWeekly[$val->wknum] += $val->mem_cnt;
			$aWeekdaily[$val->dayname] += $val->mem_cnt;
			$nTotal += $val->mem_cnt;
		}
		$aDatePeriod['gross_total'] = $nTotal;

		// estimate this month fullfillment
		if( count( $aMonthly ) )
			$aMonthly['Latest est.'] = (int)($aMonthly[$this->_g_sThisMonth] / $this->_g_nElapsedDaysOfThisMonth * $this->_g_nDaysOfMonth);
		$aDatePeriod['monthly'] = $aMonthly;
		$aDatePeriod['weekly'] = $aWeekly;
		$aDatePeriod['weekdaily'] = $aWeekdaily;
		return $aDatePeriod;
	}
/**
 * @brief 
 **/
	private function _validateDate($sDate, $sFormat = 'Y-m-d H:i:s')
	{
		$d = DateTime::createFromFormat($sFormat, $sDate);
		return $d && $d->format($sDate) == $sDate;
	}
/**
 * @brief takes two dates formatted as YYYYMMDD and creates an
 *       inclusive array of the months between the from and to dates.
 **/
	private function _getMonthlyRangeArray($sDateFrom,$sDateTo)
	{
		$aMonthRange = [];
		$dtStart = (new DateTime($sDateFrom))->modify('first day of this month');
		$dtEnd = (new DateTime($sDateTo))->modify('first day of next month');
		$diInterval = DateInterval::createFromDateString('1 month');
		$dpPeriod   = new DatePeriod($dtStart, $diInterval, $dtEnd);
		foreach($dpPeriod as $dtVal)
			$aMonthRange[] = $dtVal->format("Ym");
		return $aMonthRange;
	}
/**
 * @brief takes two dates formatted as YYYYMMDD and creates an
 *       inclusive array of the dates between the from and to dates.
 *       could test validity of dates here but I'm already doing
 *       that in the main script 
 **/
	private function _getDateRangeArray($sDateFrom,$sDateTo)
	{
		$aRange = [];
		$iDateFrom=mktime(1,0,0,substr($sDateFrom,4,2), substr($sDateFrom,6,2),substr($sDateFrom,0,4));
		$iDateTo=mktime(1,0,0,substr($sDateTo,4,2), substr($sDateTo,6,2),substr($sDateTo,0,4));

		if($iDateTo>=$iDateFrom)
		{
			$nDay = date('d', $iDateFrom);
			$nWkNum = (int)date('W', $iDateFrom);
			$sDayname = date('D', $iDateFrom);
			$nMonth = date('Ym', $iDateFrom);
			$oSingleData = new stdClass();
			$oSingleData->mo = $nMonth;
			$oSingleData->day = $nDay;
			$oSingleData->wknum = $nWkNum;
			$oSingleData->dayname = $sDayname;
			$aRange[date('Ymd',$iDateFrom)] = $oSingleData; // first entry
			while ($iDateFrom<$iDateTo)
			{
				$iDateFrom+=86400; // add 24 hours
				$nDay = date('d', $iDateFrom);
				$nWkNum = (int)date('W', $iDateFrom);
				$sDayname = date('D', $iDateFrom);
				$nMonth = date('Ym', $iDateFrom);
				$oSingleData = new stdClass();
				$oSingleData->mo = $nMonth;
				$oSingleData->day = $nDay;
				$oSingleData->wknum = $nWkNum;
				$oSingleData->dayname = $sDayname;
				$aRange[date('Ymd',$iDateFrom)] = $oSingleData;
			}
		}
		return $aRange;
	}
/**
 * @brief calculate number of days in a month
 **/
	private function _getDaysInMonth($nMonth, $nYear) 
	{ 
		return $nMonth == 2 ? ($nYear % 4 ? 28 : ($nYear % 100 ? 29 : ($nYear % 400 ? 28 : 29))) : (($nMonth - 1) % 7 % 2 ? 30 : 31); 
	}
}
/* End of file svestudio.admin.model.php */
/* Location: ./modules/svestudio/svestudio.admin.model.php */