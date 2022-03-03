<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svestudioController
 * @author singleview(root@singleview.co.kr)
 * @brief  svestudioController
**/ 
class svestudioController extends svestudio
{
/**
 * @brief initialization
 **/
	public function init()
	{
		$sSvestudioMid = Context::get('mid');
		$oModuleModel = &getModel('module');
		$oSvestudioMidConfig = $oModuleModel->getModuleInfoByMid($sSvestudioMid);
		$oLoggedInfo = Context::get('logged_info');
		$oModuleGrant = $oModuleModel->getGrant($oSvestudioMidConfig, $oLoggedInfo);
		if( !$oModuleGrant->shop_staff )
			return $this->stop( 'msg_not_allowed' );
		$nModuleSrl = $oSvestudioMidConfig->module_srl;// Context::get('module_srl');
		$sAct = Context::get('act');
		$oSvestudioModel = &getModel('svestudio');
		$bAllowed = $oSvestudioModel->isPermittedActByMemberGrp( $oLoggedInfo, $nModuleSrl, $sAct );
		if( !$bAllowed )
			return $this->stop( 'msg_not_allowed' );
		// 화면 표시를 허락하면 svorder/svorder.order_update.php의 접근 권한 인식을 위한 변수 설정
		$oLoggedInfo->svestudio_permitted_order_mgr_member_srl = $oLoggedInfo->member_srl;
	}
/**
 * @brief
 */
	public function procSvestudioUpdateDeliveryInfo() 
	{
		$aOrderSrl = Context::get('order_srls');
		$aExpressId = Context::get('express_id');
		$aInvoiceNo = Context::get('invoice_no');
		$nUpdatedItems = 0;
		$sErrMsg = null;
		//$oSvorderModel = &getClass('svorder'); // to load svorder global defined
		$oSvorderAdminController = &getAdminController('svorder');
		foreach( $aOrderSrl as $nIdx => $nOrderSrl )
		{
			$sExpressId = $aExpressId[$nIdx];
			$sInvoiceNo = $aInvoiceNo[$nIdx];
			$aInvoiceInfo = $oSvorderAdminController->parseInvoiceSerials( $sInvoiceNo );
			$oArgs->invoice_no = $aInvoiceInfo['default'];
			$oArgs->extra_invoice_no = $aInvoiceInfo['extra'];
			$oArgs->order_srl = $nOrderSrl;
			$oArgs->order_status = svorder::ORDER_STATE_ON_DELIVERY; // 송장번호가 입력되면 배송중 상태로 변경
			$oArgs->express_id = $sExpressId;
			$oArgs->cs_memo = 'procSvestudioUpdateDeliveryInfo 일괄처리';
			if( !$oArgs->express_id || strlen( $oArgs->invoice_no )==0)
				continue;
			
			$oUpdateRst = $oSvorderAdminController->updateSingleOrderStatus( $nOrderSrl, $oArgs );
			if( !$oUpdateRst->toBool() )
				$sErrMsg .= $oUpdateRst->getMessage().'<BR>';
			else
			{
				unset( $args );
				$nUpdatedItems++;
			}
		}
		$this->setMessage( $nUpdatedItems.'개의 거래가 처리되었습니다.<BR>'.$sErrMsg );
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid',Context::get('mid'),'act', 'dispSvestudioOrderManagement','status',Context::get('status'),'page',Context::get('cur_page'));
			$this->setRedirectUrl($returnUrl);
		}
	}
/**
 * @brief
 **/
	public function procSvestudioUpdateOrderStatus()
	{
		$oSvorderAdminController = &getAdminController('svorder');
		$oSvorderAdminController->procSvorderAdminUpdateStatusMultiple();
		$this->setMessage($oSvorderAdminController->getMessage());
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid',Context::get('mid'),'act', 'dispSvestudioOrderManagement','status',Context::get('status'),'page',Context::get('cur_page'));
			$this->setRedirectUrl($returnUrl);
		}
	}
/**
 * @brief
 */
	public function procSvestudioRegisterShippingInvoice() 
	{
		$oSvorderAdminController = &getAdminController('svorder');
		$oSvorderAdminController->procSvorderAdminRegisterShippingSerial();
		$sMsgFromSvorderAdmin = $oSvorderAdminController->getMessage();
		$this->setMessage( $sMsgFromSvorderAdmin );
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			if( !defined(svorder::ORDER_STATE_ON_DEPOSIT) )
				getClass('svorder');

			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid',Context::get('mid'),'act', 'dispSvestudioOrderManagement','status',svorder::ORDER_STATE_ON_DELIVERY);
			$this->setRedirectUrl($returnUrl);
		}
	}
/**
 * @brief
 */
	public function procSvestudioCSVDownloadByOrderStatus() 
	{
		$oSvorderAdminController = &getAdminController('svorder');
		$oSvorderAdminController->procSvorderAdminCSVDownloadByOrder();
	}
/**
 * @brief 배송 담당이 [입금완료] 주문을 다운로드하면 해당 주문이 자동으로 [배송준비]로 이동 
 */
	public function procSvestudioCSVDownloadOrderPrepareShipping() 
	{
		$oSvorderAdminController = &getAdminController('svorder');
		$oSvorderAdminController->procSvorderAdminCSVDownloadByOrder();
		if( !defined(svorder::ORDER_STATE_ON_DEPOSIT) )
			getClass('svorder');

		$args->list_count = 1000; // 목록 수 20개 제한이 기본인 쿼리를 변경시키기 위한 flag
		$args->order_status = svorder::ORDER_STATE_PAID;
		$oSvorderAdminModel = &getAdminModel('svorder');
		$oOrderList = $oSvorderAdminModel->getOrderListByStatus( $args );
		$aOrderSrl = array();
		foreach( $oOrderList->data as $nIdx => $oRec )
			$aOrderSrl[] = $oRec->order_srl;
		
		Context::set( 'cart', $aOrderSrl );
		Context::set( 'order_srls', $aOrderSrl );
		Context::set( 'order_status', svorder::ORDER_STATE_PREPARE_DELIVERY );		
		$oSvorderAdminController->procSvorderAdminUpdateStatusMultiple();

		$sRstMsg = $oSvorderAdminController->getMessage();
		if( $sRstMsg != '저장했습니다.' )
		{
			echo $sRstMsg; // 다운로드 엑셀 파일에 경고 표시
			/*$this->setMessage( $sRstMsg );
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
			{
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid',Context::get('mid'),'act', 'dispSvestudioOrderManagement','status',Context::get('status'),'page',Context::get('cur_page'));
				$this->setRedirectUrl($returnUrl);
			}*/
		}
	}
/**
 * @brief crontab approach log
 **/
	public function insertCrontabLog($oArg)
	{
		$oArg->http_user_agent = $_SERVER['HTTP_USER_AGENT'];
		$oArg->remote_addr = $_SERVER['REMOTE_ADDR'];
		$oRst = executeQuery('svestudio.insertCrontabLog', $oArg);
//echo __FILE__.':'.__lINE__.'<BR>';
//var_dump( $oRst);
//echo '<BR><BR>';
		if(!$oRst->toBool()) 
			return $oRst;
		return new BaseObject();
	}
}