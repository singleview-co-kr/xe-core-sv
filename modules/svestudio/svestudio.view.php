<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svestudioView
 * @author singleview(root@singleview.co.kr)
 * @brief  svestudioView
**/ 
class svestudioView extends svestudio
{
	private $_g_oMidConfig = null; //////////////
/**
 * @brief Initialization
 */
	public function init()
	{
		$sSvestudioMid = Context::get('mid');
		$oModuleModel = &getModel('module');
		$oSvestudioMidConfig = $oModuleModel->getModuleInfoByMid($sSvestudioMid);
		$this->_g_oMidConfig = $oSvestudioMidConfig;
		
		if( $this->_g_oMidConfig->mid_type != svestudio::MID_TYPE_CRONTAB )
		{
			$oLoggedInfo = Context::get('logged_info');
			$oModuleGrant = $oModuleModel->getGrant($oSvestudioMidConfig, $oLoggedInfo);
			if( !$oModuleGrant->shop_staff )
				return $this->stop( 'msg_is_not_allowed' );
		}

		// 화면 표시를 허락하면 svorder/svorder.order_update.php의 접근 권한 인식을 위한 변수 설정
		$oLoggedInfo->svestudio_permitted_order_mgr_member_srl = $oLoggedInfo->member_srl;

		$sTemplatePath = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		if(!is_dir($sTemplatePath)||!$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
			$sTemplatePath = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		}
		$this->setTemplatePath($sTemplatePath);
		// change into administration layout
		$this->setLayoutPath( $sTemplatePath );
		$this->setLayoutFile('common_layout');
	}
/**
 * @brief 
 */
	public function dispSvestudioIndex()
	{
		switch( $this->_g_oMidConfig->mid_type )
		{
			case svestudio::MID_TYPE_CRONTAB:
				// http://chakkhan.com/crontab
				$this->_procCrontab();
				break;
			case svestudio::MID_TYPE_SHIPPING:
				Context::set('act', 'dispSvestudioOrderManagement');
				$this->dispSvestudioOrderManagement();
				break;
			default:
				return new BaseObject(-1, 'invalid_request');
		}	
	}
/**
 * @brief 주문서 입력 화면
 **/
	public function dispSvestudioOrderManagement() 
	{
		$oSvorderModel = &getModel('svorder');
		$oMemberModel = &getModel('member');
		$config = $oSvorderModel->getModuleConfig();
		Context::set('config', $config);

		if( Context::get( 'status' ) )
			$args->order_status = Context::get( 'status' );
		else
		{
			Context::set( 'status', svorder::ORDER_STATE_ON_DEPOSIT );
			$args->order_status = svorder::ORDER_STATE_ON_DEPOSIT;
		}

		$args->page = Context::get( 'page' );
		if( Context::get( 'search_key' ) )
		{
			$search_key = Context::get( 'search_key' );
			$search_value = Context::get( 'search_value' );
			if( $search_key == 'nick_name' && $search_value == '비회원' )
			{
				$search_key = 'member_srl';
				$search_value = 0;
			}
			$args->{$search_key} = $search_value;
		}

		if( !Context::get( 's_year' ) )
			Context::set( 's_year', date( 'Y' ) );
		$args->regdate = Context::get( 's_year' );
	 
		if( Context::get( 's_month' ) )
			$args->regdate = $args->regdate.Context::get( 's_month' );
		
		$oSvorderAdminModel = &getAdminModel('svorder');
		$oOrderList = $oSvorderAdminModel->getOrderListByStatus( $args );
		$member_config = $oMemberModel->getMemberConfig();
		$memberIdentifiers = array( 'user_id'=>'user_id' );
		$usedIdentifiers = array();	

		if( is_array( $member_config->signupForm ) )
		{
			foreach( $member_config->signupForm as $signupItem )
			{
				if( !count( $memberIdentifiers ) )
					break;
				if( in_array( $signupItem->name, $memberIdentifiers ) && ( $signupItem->required || $signupItem->isUse ) )
				{
					unset( $memberIdentifiers[$signupItem->name]) ;
					$usedIdentifiers[$signupItem->name] = $lang->{$signupItem->name};
				}
			}
		}
		Context::set('module_srl', $this->module_info->module_srl );
		Context::set('list', $oOrderList->data);
		Context::set('total_count', $oOrderList->total_count);
		Context::set('total_page', $oOrderList->total_page);
		Context::set('page', $oOrderList->page);
		Context::set('page_navigation', $oOrderList->page_navigation);
		Context::set('delivery_companies', $oSvorderModel->getDeliveryCompanies());
		Context::set('delivery_inquiry_urls', $oSvorderModel->getDeliveryInquiryUrls());
		Context::set('order_status', $oSvorderModel->getOrderStatusLabel());
		Context::set('usedIdentifiers', $usedIdentifiers);

		$oDummy = &getClass('svorder');
		$this->setTemplateFile('ordermanagement');
	}
/**
 * @brief 외부 트리거에 의해 자동 실행되는 명령어 관리
 */
	private function _procCrontab()
	{
		// http://chakkhan.com/crontab?%40v=1UVZFqiBpuudzSdPKTyW8tYmePzNJ9dWw%2BerZhQfYgHQpK%2BhHla0OfzD6JEX%2Be6cKZuLFfsySEjTkf3pVr51vw%3D%3D
		if( $_SERVER['HTTP_USER_AGENT'] != 'sv_crontab_bot' ) // to deny illegal access
			exit; // never react with weird approach
		$sQuery = Context::get('@v'); // = %40v; to deny illegal access
		if( is_null( $sQuery ) )
			exit; // never react with weird approach
		
		$aMsg=array(
			'OK' => 1, # OK
			'ED' => 2, # error detected
		);
		$oRespParam->a = null;
		Context::setResponseMethod('JSON'); // display class 작동 정지		

		require_once( _XE_PATH_.'modules/svestudio/sv_classes/svapi_crypt.php');
		$oSvApiCrypt = new singleviewApiCrypt();
		//echo 'secured crontab mode<BR><BR>';

		$oReceivedParams = $oSvApiCrypt->translateMsgCode($sQuery);
		$aParam = $oSvApiCrypt->buildQueryArray($oReceivedParams);
		$sStartDate = $aParam['start_ymd'];
		$oSvorderAdminModel = &getAdminModel('svorder');
		switch( $aParam['task'] )
		{
			case 'getNpayOrder':
				$oNpayOrderApi = $oSvorderAdminModel->getNpayOrderApi();
				$oRst = $oNpayOrderApi->getLatestOrder($sStartDate);
//$oRst = new BaseObject();
//$oRst->add('aProcessedRst', array() );
				break;
			case 'getNpayReview':
				$oNpayOrderApi = $oSvorderAdminModel->getNpayOrderApi();
				$oRst = $oNpayOrderApi->getLatestReview($sStartDate);
				break;
			case 'getNpayInquiry':

				break;
			case 'arrangeSvOrderStatus':

				break;
			default: // throw exception
				$oRespParam->a = array($aMsg['OK']);
				// encrypt transmit

				$res = $oSvApiCrypt->encryptData($oRespParam);
				print $res;
				exit;
		}
		$aFinalRst = $oRst->get('aProcessedRst');
		$oArg->task = $aParam['task'];
		$oArg->param = serialize($aParam);
		$oArg->result = serialize($aFinalRst);
		$oSvestudioController = &getController('svestudio');
		$oSvestudioController->insertCrontabLog($oArg);
		
		$bWarningMode = false;
		foreach( $aFinalRst as $nNpaySomeId => $oSingleRst )
		{
			if( $nNpaySomeId == 'start_from' || $nNpaySomeId == 'end_to' )
				continue;
			if(!$oSingleRst->bProcessed)
				$bWarningMode = true;
		}
		if( $bWarningMode )
			$oRespParam->a = array($aMsg['ED']);
		else
			$oRespParam->a = array($aMsg['OK']);

		// encrypt transmit
		$res = $oSvApiCrypt->encryptData($oRespParam);
		print $res;
		exit;
	}
/**
 * @brief
 */
	private function _getHexColor() 
	{
		return sprintf("#%02X%02X%02X", mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
	}
/**
 * @brief
 */
	private function _getDateArray()
	{
		if(is_null(Context::get('startdateInput')))
		{
			$theFirstDayOfThisMonth = date('y-m').'-01';
			$lStartdate = strtotime($theFirstDayOfThisMonth);
		}
		else
			$lStartdate = strtotime(Context::get('startdateInput'));

		if(is_null(Context::get('enddateInput')))
		{
			$nTodate = (int)date('d');
			$theYesterday = sprintf( "%s%02d", date('y-m-'), --$nTodate);
			$lEnddate = strtotime($theYesterday);//$lEnddate = strtotime('-1 day');
		}
		else
			$lEnddate = strtotime(Context::get('enddateInput'));

		$nDatediff = $lEnddate - $lStartdate;
		$nDatediff = floor($nDatediff/86480)+1;//60*60*24

		$aDatadate = array();
		for( $i=0; $i<=$nDatediff; $i++)
		{
			$sPlusday = '+'.$i.' day';
			array_push($aDatadate, date('Ymd', strtotime($sPlusday , $lStartdate)));
		}
		return $aDatadate;
	}
}
/* End of file svestudio.view.php */
/* Location: ./modules/svestudio/svestudio.view.php */