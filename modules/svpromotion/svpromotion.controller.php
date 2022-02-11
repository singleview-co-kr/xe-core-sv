<?php
/**
 * @class  svpromotionController
 * @author singleview(root@singleview.co.kr)
 * @brief  svpromotionController
 */
class svpromotionController extends svpromotion
{
/**
 * @brief initialization
 **/
	function init()
	{
	}
/**
 * @brief 회원 포인트 변동 -> 레벨 변동 -> 그룹변동의 결과로 쿠폰 자동 발행
 **/
	public function triggerSetPointAfter(&$obj) 
	{
		$nMemberOldLevel = (int)$obj->current_level;
		$nMemberNewLevel = (int)$obj->new_level;
		if( $nMemberNewLevel > $nMemberOldLevel ) // 레벨 상승의 결과로
		{
			if( count( $obj->new_group_list ) > 0 ) // 회원 그룹 이동할때만 쿠폰 부여
			{
				$nNewGroupSrl = (int)$obj->new_group_list[0];
				$oSvpromotionModel = &getModel('svpromotion');
				$oCouponPromotionList = $oSvpromotionModel->getCouponPromotionList();
				foreach( $oCouponPromotionList->data as $key=>$val )
				{
					if( $val->allowed_grp[$nNewGroupSrl] == 'Y' )
					{
						$nPromotionSrl = $val->promotion_srl;
						$nCouponMemberSrl = (int)$obj->member_srl;
						if( $nCouponMemberSrl == 0 )
							debugPrint( 'invalid_member_srl');

						$oRst = $oSvpromotionModel->getSimpleCouponListByMemberPromotionSrl($nCouponMemberSrl, $nPromotionSrl);
						if(!$oRst->toBool())
						{
							debugPrint( $oRst );
							return;
						} 

debugPrint( count( $oRst->data ) );
debugPrint( (int)$val->max_issue_count );

						if( count( $oRst->data ) >= (int)$val->max_issue_count )
						{
							debugPrint( 'automatic coupon issuing denied exceeds maximum issue count limit');
							return;
						}

						$oSvpromotionAdminModel = &getAdminModel('svpromotion');
						$oSvpromotionAdminController = &getAdminController('svpromotion');
						$nCouponLength = 6;
						for( $i = 0; $i < 3; $i++ )
						{
							$sSerial = $oSvpromotionAdminModel->getCouponSerial($nCouponLength);
							$oRst = $oSvpromotionAdminController->insertCoupon($nPromotionSrl, $sSerial,$nCouponMemberSrl);
							if(!$oRst->toBool())
								debugPrint( $oRst );
							else
							{
								debugPrint( 'new coupon '.$sSerial.' issued because member '.$nCouponMemberSrl.' raised group to '.$nNewGroupSrl );
								return;
							}
						}
					}
				}
			}
		}
	}
/**
 * @brief mark usage of the coupon; increase use count
 * svorder.controller.php::completePgProcess()에서 호출
 **/
	public function procSvprmotionMarkUsedCoupon( $oOrderInfo )
	{
		$nCouponSrl = (int)$oOrderInfo->checkout_promotion_info->aCheckoutPromotion[0]->coupon_srl;
		if( $nCouponSrl <= 0 )
			return new BaseObject(-1, 'msg_error_while_mark_used_coupon' );

		$args->coupon_srl = $nCouponSrl;
		$output = executeQuery( 'svpromotion.increaseCouponUsedCountByCouponSrl', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_while_mark_used_coupon');
		
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$oCouponInfo = $oSvpromotionAdminModel->getCouponInfo($nCouponSrl);
		$args->promotion_srl = $oCouponInfo->promotion_srl;
		$args->member_srl = $oOrderInfo->member_srl;
		$args->order_srl = $oOrderInfo->order_srl;
		$output = executeQuery( 'svpromotion.insertCouponUsageLog', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_while_log_coupon_usage');
	
		return new BaseObject();
	}
/**
 * @brief mark usage of the coupon; decrease use count
 * svorder.admin.controller.php::procSvorderAdminCancelSettlement()에서 호출
 **/
	public function procSvprmotionRollbackBenefit( $oOrderInfo )
	{
debugPrint( 'rollbackConsumerBenefit - begin' );
		if( count( $oOrderInfo->oCheckoutPromotionInfo->aCheckoutPromotion ) )
		{
			$nCouponSrl = (int)$oOrderInfo->oCheckoutPromotionInfo->aCheckoutPromotion[0]->coupon_srl;
			if( $nCouponSrl > 0 )
			{
				$args->coupon_srl = $nCouponSrl;
				$output = executeQuery( 'svpromotion.decreaseCouponUsedCountByCouponSrl', $args );
				debugPrint( 'msg_error_while_rollback_used_coupon' );
				debugPrint( $args );
				
				$oSvpromotionAdminModel = &getAdminModel('svpromotion');
				$oCouponInfo = $oSvpromotionAdminModel->getCouponInfo($nCouponSrl);
				$args->type = 'rollback';
				$args->promotion_srl = $oCouponInfo->promotion_srl;
				$args->member_srl = $oOrderInfo->nMemberSrl;
				$args->order_srl = $oOrderInfo->nOrderSrl;

				$output = executeQuery( 'svpromotion.insertCouponUsageLog', $args );
				debugPrint( 'msg_error_while_log_coupon_usage' );
				debugPrint( $args );
			}
		}
		if( $oOrderInfo->reserves_consume_srl )
			$this->toggleReservesLog( $oOrderInfo->nReservesConsumeSrl, 'delete', 'full_cancel' );
		if( $oOrderInfo->reserves_receive_srl )
			$this->toggleReservesLog( $oOrderInfo->nReservesReceiveSrl, 'delete', 'full_cancel' );
debugPrint( 'rollbackConsumerBenefit - end' );
		return new BaseObject();
	}
/**
 * @brief register claimed reserves
 **/
	public function consumeReserves( $nOrderSrl, $nReservesAmntClaimed )
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
			return new BaseObject(-1, 'msg_login_required');
		if( $nOrderSrl == 0 || $nReservesAmntClaimed == 0 )
			return new BaseObject(-1, 'msg_error_no_claiming_reserves' );

		$oSvpromotionModel = &getModel('svpromotion');
		$nReservesLogSrl = $oSvpromotionModel->getNextReservesLogSrl();
		$args->reserves_srl = $nReservesLogSrl;
		$args->member_srl = $logged_info->member_srl;
		$args->order_srl = $nOrderSrl;
		$args->mode = '-';
		$args->amount = $nReservesAmntClaimed;
		$output = executeQuery( 'svpromotion.insertReservesLog', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_while_register_reserves_log');

		$output->add('reserves_srl', $nReservesLogSrl );
		return $output;
	}
/**
 * @brief toggle reserves 
 **/
	public function toggleReservesLog( $nReservesLogSrl, $sToggleMode, $sReasonType )
	{
		switch( $sToggleMode )
		{
			case 'active':
				$oArgs->is_active = 'Y';
				$oArgs->is_deleted = 'N';
				break;
			case 'deactive':
				$oArgs->is_active = 'N';
				break;
			case 'delete':
				$oArgs->is_active = 'N';
				$oArgs->is_deleted = 'Y';
				break;
			default:
				return new BaseObject(-1, 'msg_invalid_reserves_log_toggle_mode');
				break;
		}

		switch( $sReasonType )
		{
			case 'full_cancel':
				$oArgs->reason = svpromotion::RESERVES_REASON_FULL_CANCEL;
				break;
			case 'partial_cancel':
				$oArgs->reason = svpromotion::RESERVES_REASON_PARTIAL_CANCEL;
				break;
			case 'settlement':
			default:
				$oArgs->reason = svpromotion::RESERVES_REASON_SETTLEMENT;
				break;
		}

		$oArgs->reserves_srl = $nReservesLogSrl;
		$oRst = executeQuery( 'svpromotion.updateReservesLog', $oArgs );
		if( !$oRst->toBool() )
			return new BaseObject(-1, 'msg_error_while_register_reserves_log');
		return new BaseObject();
	}
/**
 * @brief issue reserves for newly settled transaction
 **/
	public function issueReserves( $nOrderSrl, $nBaseAmount, $nMemberSrl )
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
			return new BaseObject(-1, 'msg_login_required');
		if( $nOrderSrl == 0 || $nMemberSrl == 0 || $nBaseAmount == 0 )
			return new BaseObject(-1, 'msg_error_incomplete_transaction_info' );

		$oSvpromotionModel = &getModel('svpromotion');
		$output = $oSvpromotionModel->getReservesLogByOrderSrl($nOrderSrl);
		if( !$output->toBool() )
			return $output;
		foreach( $output->data as $key=>$val ) // 동일 거래에 대해 적립금 지급 내역이 있는지 확인
		{
			if( $val->mode == '+' && $val->is_active == 'Y' )
				return new BaseObject(-1, 'msg_error_already_issued_transaction' );
		}
		$nReservesLogSrl = $oSvpromotionModel->getNextReservesLogSrl();
		$args->reserves_srl = $nReservesLogSrl;
		$args->member_srl = $logged_info->member_srl;
		$args->order_srl = $nOrderSrl;
		$args->mode = '+';
		$args->is_active = 'Y';
		$args->amount = $oSvpromotionModel->getExpectedReserves( $nBaseAmount );
		$output = executeQuery( 'svpromotion.insertReservesLog', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_while_register_reserves_log');

		$output->add('reserves_srl', $nReservesLogSrl );
		return $output;
	}
/**
 * @brief 완료된 거래의 주문 수준 프로모션 정보 추가
 **/
	public function insertOrderLevelPromotionInfo( $oInArg )
	{
		if( is_null( $oInArg->order_srl ) || is_null( $oInArg->oCheckoutPromotionInfo_srz ) )
			return new BaseObject(-1, 'msg_invalid_params');
		return executeQuery( 'svpromotion.insertOrderPromoInfo', $oInArg );
	}
/**
 * @brief 완료된 거래의 품목 수준 프로모션 정보 추가
 **/
	public function insertCartLevelPromotionInfo( $oInArg )
	{
		if( is_null( $oInArg->cart_srl ) || is_null( $oInArg->oPromotionInfo_srz ) )
			return new BaseObject(-1, 'msg_invalid_params');
		return executeQuery( 'svpromotion.insertCartPromoInfo', $oInArg );
	}
/**
 * @brief activate reserves when finalize settlement
 * deleteReserves 와 코드 병합해야 함
 **/
	/*public function activateReservesLog( $nReservesLogSrl )
	{
		$args->reserves_srl = $nReservesLogSrl;
		$args->is_active = 'Y';
		$output = executeQuery( 'svpromotion.updateReservesLog', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_while_register_reserves_log');
		return new BaseObject();
	}*/
/**
 * @brief activateReservesLog 와 코드 병합해야 함
 **/
	/*public function deleteReserves( $nReservesLogSrl )
	{
		$args->reserves_srl = $nReservesLogSrl;
		$args->is_deleted = 'Y';
		$output = executeQuery( 'svpromotion.updateReservesLog', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_while_register_reserves_log');
		return new BaseObject();
	}*/
}