<?php
/**
 * @class  svpromotionModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svpromotionModel
 */
require_once(_XE_PATH_.'modules/svpromotion/svpromotion.unit.php');
class svpromotionModel extends module
{
/**
 * @brief initialization
 **/
	public function init()
	{
	}
/**
 * @brief ./svpromotion.admin.model.php::getModuleConfig()와 동일 코드 유지해야 함
 **/
	public function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		$oConfig = $oModuleModel->getModuleConfig('svpromotion');
        if((is_null($oConfig)))
            $oConfig = new stdClass();
		if(!$oConfig->fb_app_id)
			$oConfig->fb_app_id = '';
		return $oConfig;
	}
/**
 * @brief 
 **/
	public function getCouponPromotionList()
	{	
		$output = executeQueryArray('svpromotion.getCouponPromotionList');
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_db_query');
		else
		{
			foreach( $output->data as $key=>$val)
			{
				switch( $val->status )
				{
					case 0:
						$output->data[$key]->status = 'OFF';
						break;
					case 1:
						$output->data[$key]->status = 'ON';
						break;
					default:
						$output->data[$key]->status = 'WEIRD';
				}

				if( $output->data[$key]->allowed_grp )
					$output->data[$key]->allowed_grp = unserialize($output->data[$key]->allowed_grp);
				else
					$output->data[$key]->allowed_grp = null;

				if( $output->data[$key]->target_items )
					$output->data[$key]->target_items = unserialize($output->data[$key]->target_items);
				else
					$output->data[$key]->target_items = null;

				if( is_numeric( $val->end_date ) )
					$output->data[$key]->end_date = zdate($output->data[$key]->end_date);
			}
			//return $output->data;
			return $output;
		}
	}
/**
 * @brief svorder.model.php::confirmOffer()에서 호출
 * svorder.controller.php::issueReserves()에서 호출
 * svorder.view.php::dispSvorderOrderComplete()에서 호출
 * svorder.view.php::dispSvorderOrderList()에서 호출
 * svorder.view.php::dispSvorderOrderDetail()에서 호출
 **/
	public function getExpectedReserves( $nBaseAmount )
	{
		$oConfig = $this->getModuleConfig();
		if( $oConfig->allow_reserves_consumption == 'Y' )
			$fReservesRateNumber = $oConfig->reserves_ratio/100;
		else
			$fReservesRateNumber = 0;
		return floor( $fReservesRateNumber * $nBaseAmount / 100) * 100;
	}
/**
 * @brief 특정 회원의 적립금 내역과 잔액 추출
 **/
	public function getReservesStatusByMemberSrl( $nMemberSrl )
	{
		if( !$nMemberSrl )
			return new BaseObject();

		$args->member_srl = (int)$nMemberSrl;
		$args->is_deleted = 'N';
		$args->is_active = 'Y';
		$oRst = executeQueryArray( 'svpromotion.getReservesLogByMemberSrl', $args );
		if( !$oRst->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_reserves_db_query');
		
		$aReservesByOrderSrl = array();
		$nRemainingReserves = 0;
		foreach( $oRst->data as $key=>$val )
		{
			if( $val->mode == '+' )
			{
				$nRemainingReserves += $val->amount;
				$aReservesByOrderSrl[$val->order_srl]->received = $val->amount;
			}
			else if( $val->mode == '-' )
			{
				$nRemainingReserves -= $val->amount;
				$aReservesByOrderSrl[$val->order_srl]->consumed = $val->amount;
			}
		}
		$oRst->add( 'nRemainingReserves', $nRemainingReserves );
		$oRst->add( 'aReservesByOrderSrl', $aReservesByOrderSrl );
		return $oRst;
	}
/**
 * @brief 적립금 일련번호로 적립금 내역 추출
 * svorder.model.php::getOrderInfo()에서 호출
 **/
	public function getReservesLogByReservesSrl( $nReservesLogSrl )
	{
		$args->reserves_srl = (int)$nReservesLogSrl;
		$output = executeQuery( 'svpromotion.getReservesLogByReserveSrl', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_reserves_db_query');
		$oRst = new BaseObject();
		if( $output->data->is_active == 'Y' && $output->data->is_deleted == 'N' )
		{
			$oRst->add('amount', $output->data->amount );
			$oRst->add('mode', $output->data->mode );
		}
		return $oRst;
	}
/**
 * @brief 특정 거래의 적립금 내역 추출
 **/
	public function getReservesLogByOrderSrl( $nOrderSrl )
	{
		$args->order_srl = (int)$nOrderSrl;
		$output = executeQueryArray( 'svpromotion.getReservesLogByOrderSrl', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_reserves_db_query');
		return $output;
	}
/**
 * @brief 
 **/
	public function getNextReservesLogSrl()
	{
		$output = executeQuery('svpromotion.getMaxReservesLogSrl' );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_reserves_log_db_query');
		else
			return ++$output->data->reserves_srl;
	}
/**
 * @brief check site level bulk promotion
 **/
	public function getSiteLevelQuantityDiscount( $oInArgs )
	{
		$oConfig = $this->getModuleConfig();
		if( $oConfig->site_bulk_promotion['toggle'] != 'Y' )
			return new BaseObject();

		$sMode = $oConfig->site_bulk_promotion['mode'];
		$aTargetedItemList = $oConfig->site_bulk_promotion['item'];
		$aCartedItemList = $oInArgs->cart->item_list;
		$nItemMatched = 0;
		if( $sMode == 'include' )
		{
			foreach( $aCartedItemList as $key=>$val )
			{
				if(array_key_exists($val->item_srl, $aTargetedItemList)) 
					$nItemMatched += $val->quantity;
			}
		}
		else if( $sMode == 'exclude' )
		{
			foreach( $aCartedItemList as $key=>$val )
			{
				if(!array_key_exists($val->item_srl, $aTargetedItemList)) 
					$nItemMatched += $val->quantity;
			}
		}
		$nSelectedQtyRange = 0;
		$fSelectedDiscountRate = 0;
		$nRecommendedQtyRange = 0;
		$fRecommendedDiscountRate = 0;
		$aMinimumQtyRange = $oConfig->site_bulk_promotion['qty_range'];
		foreach( $aMinimumQtyRange as $key=>$val )
		{
			if( $nItemMatched < $key && !$nRecommendedQtyRange )
			{
				$nRecommendedQtyRange = $key;
				$fRecommendedDiscountRate = $val/100;
			}
			if( $nItemMatched >= $key )
			{
				$nSelectedQtyRange = $key;
				$fSelectedDiscountRate = $val/100;
			}
		}
		$oRst = new BaseObject();
		if( $nRecommendedQtyRange )
		{
			$oSvitemAdminModel = &getAdminModel('svitem');
			$output = $oSvitemAdminModel->getSvitemAdminItemList();
			$aItemList = $output->data;
			$aRecommendedItemList = array();
			
			if( $sMode == 'include' )
			{
				foreach( $aItemList as $key=>$val )
				{
					if(array_key_exists($val->item_srl, $aTargetedItemList))
					{
						if( $val->display == 'Y' )
							$aRecommendedItemList[] = $val->item_name;
					}
				}
			}
			else if( $sMode == 'exclude' )
			{
				foreach( $aItemList as $key=>$val )
				{
					if(!array_key_exists($val->item_srl, $aTargetedItemList)) 
					{
						if( $val->display == 'Y' )
							$aRecommendedItemList[] = $val->item_name;
					}
				}
			}
			$oRst->add('recommeded_qty_range', $nRecommendedQtyRange );
			$oRst->add('recommeded_discount_rate', $fRecommendedDiscountRate );
			$oRst->add('remaining_qty', $nRecommendedQtyRange - $nItemMatched );
			$oRst->add('recommeded_item_list', $aRecommendedItemList );
		}
		if( $nItemMatched )
		{
			$oRst->add('matched_qty_range', $nSelectedQtyRange );
			$oRst->add('matched_discount_rate', $fSelectedDiscountRate );
		}
		return $oRst;
	}
/**
 * @brief svpromotion.controller.php::triggerSetPointAfter()에서 호출
 **/
	public function getSimpleCouponListByMemberPromotionSrl( $nMemberSrl, $nPromotionSrl )
	{
		if( !$nMemberSrl )
			return new BaseObject(-1, 'msg_invalid_member_srl' );

		if( !$nPromotionSrl )
			return new BaseObject(-1, 'msg_invalid_promotion_srl' );
		
		$args->member_srl = (int)$nMemberSrl;
		$args->promotion_srl = (int)$nPromotionSrl;
		return executeQueryArray( 'svpromotion.getCouponListByMemberPromotionSrl', $args );
	}
/**
 * @brief svorder.order_update.php::_reevaluateOrder()에서 호출
 **/
	public function getCouponInfoByCouponSrl( $nCouponSrl )
	{
		if( !$nCouponSrl )
			return new BaseObject(-1, 'msg_invalid_coupon_srl' );

		$oArgs->coupon_srl = (int)$nCouponSrl;
		return executeQuery( 'svpromotion.getCouponByCouponSrl', $oArgs );
	}
/**
 * @brief $this->getCouponInfoBySerialNumber()의 쿠폰 만료일 계산 코드 블록과 병합해야 함
 **/
	public function getCouponInfoByMemberSrl( $nMemberSrl )
	{
		if( !$nMemberSrl )
			return new BaseObject(-1, 'msg_invalid_member_srl' );
		
		$args->member_srl = (int)$nMemberSrl;
		$output = executeQueryArray( 'svpromotion.getCouponListByMemberSrl', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_coupon_db_query');

		$aPromotionInfo = array();
		$oRst->coupon_list = array();
		$nCouponIdx = 0;
		//$nPromotionSrl = $output->data[1]->promotion_srl;
		if( count( $output->data ) > 0 )
		{
			$oSvitemModel = &getModel('svitem');
			unset( $args );
			foreach( $output->data as $key=>$val )
			{
				if( $aPromotionInfo[$val->promotion_srl] )
					$oPromotionRst->data = $aPromotionInfo[$val->promotion_srl];
				else
				{
					$nPromotionSrl = $val->promotion_srl;
					$oPromotionRst = $this->_getCouponPromotionSetupInfo( $nPromotionSrl );
					$aItems = array();
					foreach( $oPromotionRst->data->target_items as $svItemKey=>$svItemVal )
					{
						$oSvitemInfo = $oSvitemModel->getItemInfoByItemSrl($svItemKey);
						$aItems[] = $oSvitemInfo->item_name;
					}
					$oPromotionRst->data->target_items = $aItems;
					// arrange coupon policy
					$sDiscType = $oPromotionRst->data->descount_type;
					switch( $sDiscType )
					{
						case 'amount':
							$fDisc = $output->data->descount_amount_policy;
							$nCouponDiscountedPayAmnt = $nOriginalPayAmnt - $fDisc;
							$oPromotionRst->data->discount_info = '결제액 중 '. $oPromotionRst->data->descount_amount_policy.'원 할인';
							break;
						case 'rate':
							$fDisc = sprintf( "%.2f", ((int)$oPromotionRst->data->descount_rate_policy)/100 );
							$nCouponDiscountedPayAmnt = $nOriginalPayAmnt * (1- (int)$oPromotionRst->data->descount_rate_policy/100);
							$oPromotionRst->data->discount_info = '결제액의 '.$oPromotionRst->data->descount_rate_policy.'% 할인';
							break;
						default:
							$oPromotionRst->data->discount_info = '쿠폰 할인 정보 오류! 관리자에게 문의하세요.';
							break;
					}
				}
				if( count( $oPromotionRst->data ) == 1 && $oPromotionRst->data->status == 1 )
				{
					$aPromotionInfo[$val->promotion_srl] = $oPromotionRst->data;
					unset( $val->promotion_srl );
					$val->promotion_title = $oPromotionRst->data->promotion_title;
					$val->discount_info = $oPromotionRst->data->discount_info;
					$val->target_items = $oPromotionRst->data->target_items;
					unset( $val->member_srl );
					$val->regdate = zdate( $val->regdate, 'Y-m-d H:i:s' );
					
					$sCouponRegDatetime = $val->regdate;
					//$val->expiration = '22001231235959';
					$sCurDatetime = date('YmdHis');
					if( $oPromotionRst->data->begin_date && $oPromotionRst->data->begin_date > $sCurDatetime )
						$val->expiration = sprintf(Context::getLang('msg_error_coupon_not_began'), date( 'Y-m-d', strtotime($oPromotionRst->data->begin_date) ) );
					
					if( $oPromotionRst->data->end_date )
					{
						if( strpos($oPromotionRst->data->end_date, 'minutes') !== false )
						{
							if( $sCouponRegDatetime )
							{
								$endTime = strtotime($oPromotionRst->data->end_date, strtotime($sCouponRegDatetime));
								$sIndividualCouponExpDatatime = date('YmdHis', $endTime);

								$val->expiration = zdate( $sIndividualCouponExpDatatime, 'Y-m-d H:i:s' );
								if( $sIndividualCouponExpDatatime < $sCurDatetime )
									$val->expiration = Context::getLang('msg_error_coupon_expired');
							}
						}
						else
						{
							$val->expiration = zdate( $oPromotionRst->data->end_date, 'Y-m-d H:i:s' );
							if( $oPromotionRst->data->end_date < $sCurDatetime )
								$val->expiration = Context::getLang('msg_error_coupon_expired');
						}
					}
					else
						$val->expiration = Context::getLang('msg_eternal_coupon');
					
					$oRst->coupon_list[$nCouponIdx++] = $val;
				}
			}
		}
		return $oRst;
	}
/**
 * @brief 완료된 거래의 주문 수준 프로모션 정보 생성
 **/
	public function buildOrderLevelPromotionInfo($oPromotionInfo)
	{
		$oOrderPromoInfo = new stdClass();
		$oOrderPromoInfo->version = svpromotion::PROMO_INFO_VERS;
		$oOrderPromoInfo->aCheckoutPromotion = $oPromotionInfo->promotion;
		return $oOrderPromoInfo;
	}
/**
 * @brief 완료된 거래의 품목 수준 프로모션 정보 생성
 **/
	public function buildCartLevelPromotionInfo($oCartVal)
	{
		$oRstFinal = new stdClass();
		$oRstFinal->oPromotionInfo = null;
		$oRstFinal->is_promoted = '';

		$oPromotionInfo = new stdClass();
		$oPromotionInfo->version = svpromotion::PROMO_INFO_VERS;

		if(count((array)$oCartVal->conditional_promotion->promotion))
			$oPromotionInfo->oSocialPromotion = $oCartVal->conditional_promotion->promotion;
		
		if($oCartVal->oItemDiscountPromotion)
			$oPromotionInfo->oItemDiscountPromotion = $oCartVal->oItemDiscountPromotion;
	
		if($oCartVal->oGiveawayPromotion)
			$oPromotionInfo->oGiveawayPromotion = $oCartVal->oGiveawayPromotion;

		if(isset($oPromotionInfo->oSocialPromotion) || 
			isset($oPromotionInfo->oItemDiscountPromotion) ||
			isset($oPromotionInfo->oGiveawayPromotion))
		{
			$oRstFinal->oPromotionInfo = $oPromotionInfo;
			$oRstFinal->is_promoted = 'Y';
		}
		return $oRstFinal;
	}
/**
 * @brief 완료된 거래의 주문 수준 프로모션 정보 해석
 **/
	public function getOrderLevelPromotionInfo( $nOrderSrl)
	{
		$oPromoArg->order_srl = $nOrderSrl;
		$oOrderPromoRst = executeQueryArray( 'svpromotion.getOrderPromoInfo', $oPromoArg );
		if(!$oOrderPromoRst->toBool())
			return $oOrderPromoRst;
		unset($oPromoArg); 
	
		//$aOrderLevelPromotionInfo = [];
		if( count( $oOrderPromoRst->data ) )
		{
			//$nOrderLevelPromoIdx = 0;
			$oOrderPromotionInfo = unserialize($oOrderPromoRst->data[0]->promotion_info);
			if( $oOrderPromotionInfo->version == '1.1' )
			{
				$oOrderPromoRst->add('oOrderPromo', $oOrderPromotionInfo);
				//foreach( $oOrderPromotionInfo->oCheckoutPromotion as $nIdx => $oVal )
				//{
				//	$aOrderLevelPromotionInfo[$nOrderLevelPromoIdx]->coupon_srl = $oVal->coupon_srl;
				//	$aOrderLevelPromotionInfo[$nOrderLevelPromoIdx]->title = $oVal->title;
				//	$aOrderLevelPromotionInfo[$nOrderLevelPromoIdx++]->total_disc_amnt = $oVal->total_disc_amnt;
				//}
			}
		}
		return $oOrderPromoRst;
	}
/**
 * @brief 완료된 거래의 품목 수준 프로모션 정보 해석
 **/
	public function getCartLevelPromotionInfo($nCartSrl, $oCartItem)
	{
		$oPromoArg->cart_srl = $nCartSrl;
		$oCartPromoRst = executeQueryArray( 'svpromotion.getCartPromoInfo', $oPromoArg );
		if(!$oCartPromoRst->toBool())
			return $oCartPromoRst;
		unset($oPromoArg); 
		
		$aDiscountInfo = [];
		$nGrossDiscountAmnt = 0;
		$nShippingItems = 0;
		if( count( $oCartPromoRst->data ) )
		{
			$nPromotionIdx = 0;
			$oCartPromotionInfo = unserialize($oCartPromoRst->data[0]->promotion_info);
			if( $oCartPromotionInfo->version == '1.1' )
			{
				if( $oCartPromotionInfo->oSocialPromotion )
				{
					foreach( $oCartPromotionInfo->oSocialPromotion as $nIdx2 => $oVal2 )
					{
						$aDiscountInfo[$nPromotionIdx]->type = $oVal2->type;
						$aDiscountInfo[$nPromotionIdx]->title = $oVal2->title;
						$aDiscountInfo[$nPromotionIdx]->is_applied = $oVal2->is_applied;
						$aDiscountInfo[$nPromotionIdx++]->unit_disc_amnt = $oVal2->resultant_disc_amnt;
						$nGrossDiscountAmnt += $oVal2->resultant_disc_amnt;
					}
				}
				if( $oCartPromotionInfo->oItemDiscountPromotion )
				{
					foreach( $oCartPromotionInfo->oItemDiscountPromotion as $nIdx2 => $oVal2 )
					{
						$aDiscountInfo[$nPromotionIdx]->type = $oVal2->type; 
						$aDiscountInfo[$nPromotionIdx]->title = $oVal2->title;
						$aDiscountInfo[$nPromotionIdx]->is_applied = $oVal2->is_applied;
						$aDiscountInfo[$nPromotionIdx++]->unit_disc_amnt = $oVal2->unit_disc_amnt;
						$nGrossDiscountAmnt += $oVal2->unit_disc_amnt;
					}
				}
				if( $oCartPromotionInfo->oGiveawayPromotion )
				{
					foreach( $oCartPromotionInfo->oGiveawayPromotion as $nIdx2 => $oVal2 )
					{
						$aDiscountInfo[$nPromotionIdx]->type = $oVal2->type; 
						$aDiscountInfo[$nPromotionIdx]->title = $oVal2->title.' '.$oVal2->giveaway_item_qty.'개씩';
						$aDiscountInfo[$nPromotionIdx]->giveaway_item_price = $oVal2->giveaway_item_price;
						$aDiscountInfo[$nPromotionIdx]->is_applied = $oVal2->is_applied;
						$aDiscountInfo[$nPromotionIdx++]->giveaway_item_qty = $oVal2->giveaway_item_qty;
						$nTmpShipItems = $oVal2->giveaway_item_qty * $oCartItem->quantity;
						$nShippingItems += $nTmpShipItems;
					}
				}
			}
		}
		$oCartPromoRst->add('oCartPromoInfo', $oCartPromotionInfo); // stdClass for data handling like confirmOffer
		$oCartPromoRst->add('aCartPromo', $aDiscountInfo); // array for svorder manager UI
		$oCartPromoRst->add('nGrossDiscountAmnt', $nGrossDiscountAmnt);
		$oCartPromoRst->add('nShippingItems', $nShippingItems);
		return $oCartPromoRst;
	}
/**
 * @brief svorder.model.php::confirmOffer()에서 호출
 **/
	public function getCheckoutPrice( $oInArgs )
	{
		$oCheckoutPromotionInfo = new stdClass();
		$oCheckoutPromotionInfo->version = svpromotion::PROMO_INFO_VERS;
		$nTotalDiscountAmount = 0;
		$oRst = new BaseObject();
		
		// 주문서 입력 후 할인액; 주문서 입력전 할인액은 무시함
		$nInitialItemTotalPrice = $oInArgs->cart->sum_price;
		$oSiteBulkOutput = $this->getSiteLevelQuantityDiscount( $oInArgs );
		$nBulkQtyRange = (int)$oSiteBulkOutput->get('matched_qty_range');
		$fBulkDiscountRate = (float)$oSiteBulkOutput->get('matched_discount_rate');
		$nBulkQtyRecommendedRange = (int)$oSiteBulkOutput->get('recommeded_qty_range');
		$fBulkDiscountRecommendedRate = (float)$oSiteBulkOutput->get('recommeded_discount_rate');
		$nRemainingQty = (int)$oSiteBulkOutput->get('remaining_qty');
		$aBulkDiscountRecommendedItemList = $oSiteBulkOutput->get('recommeded_item_list');
		if( $nBulkQtyRange > 0 )
		{
			$nTotalDiscountAmount = (int)($nInitialItemTotalPrice * $fBulkDiscountRate);
			$oCheckoutPromotionInfo->promotion[0]->type = 'site_bulk';
			unset($oCheckoutPromotionInfo->promotion[0]->coupon_srl); // 쿠폰 사용 무효화
			$oCheckoutPromotionInfo->promotion[0]->total_disc_amnt = $nTotalDiscountAmount;
			$oCheckoutPromotionInfo->promotion[0]->title = $nBulkQtyRange.'개 이상  '.number_format($nTotalDiscountAmount,0).'원 할인';
		}
		if( $nBulkQtyRecommendedRange )
		{
			$oRst->add('remaining_qty', $nRemainingQty );
			$oRst->add('recommeded_qty_range', $nBulkQtyRecommendedRange );
			$oRst->add('recommeded_discount_rate', $fBulkDiscountRecommendedRate );
			$oRst->add('recommeded_item_list', $aBulkDiscountRecommendedItemList );
		}
		if( !defined(svorder::ORDER_STATE_ON_DEPOSIT) )
			getClass('svorder');

		$aTempItemList = $oInArgs->cart->item_list;
		// 취소 상태인 장바구니 품목은 계산에서 제외함
		foreach( $aTempItemList as $nIdx => $oVal )
		{
			if( $oVal->order_status == svorder::ORDER_STATE_CANCEL_REQUESTED ||
				$oVal->order_status == svorder::ORDER_STATE_CANCELLED )
				unset( $aTempItemList[$nIdx] );
		}
		// 쿠폰 할인 정책을 무시한 품목별 원래 할인액을 계산함
		$nOriginalDiscountAmnt = 0;
		foreach( $aTempItemList as $nIdx => $oCartVal )
		{
			if( $oInArgs->recheck_mode ) // 완료된 결제 부분 취소를 위한 재평가
			{
				if( $oCartVal->oSocialPromotion )
					$oCartVal->discount_amount = $oCartVal->oSocialPromotion[0]->resultant_disc_amnt;
			}
			else // 신규 결제
			{
				if( $oCartVal->conditional_promotion )
				{
					$oCartVal->conditional_promotion = unserialize( $oCartVal->conditional_promotion );
					$oCartVal->discount_amount = $oCartVal->conditional_promotion->promotion[0]->resultant_disc_amnt;
				}
			}
			if( $oCartVal->oItemDiscountPromotion )
				$oCartVal->discount_amount += $oCartVal->oItemDiscountPromotion[0]->unit_disc_amnt;
			if( $oCartVal->discount_amount ) // $oCartVal->discount_amount 을 쿠폰 할인 정책을 무시한 품목별 원래 단위 할인액으로 설정함
				$nOriginalDiscountAmnt += $oCartVal->quantity * $oCartVal->discount_amount;
		}
		$oCouponOutput = new BaseObject();
		$sCouponSerial = $oInArgs->coupon_number;
		if( strlen( $sCouponSerial ) > 0 )
		{
			$nOriginalPayAmnt = 0;
			$nAlreadyDiscountedPayAmnt = 0;
			foreach( $aTempItemList as $key => $val ) // 이미 적용된 할인 혜택을 계산
			{
				$nOriginalPayAmnt += $val->price * $val->quantity;
				$nAlreadyDiscountedPayAmnt += ($val->price - $val->discount_amount)*$val->quantity;
			}
			if( $nOriginalPayAmnt - $nAlreadyDiscountedPayAmnt < $nTotalDiscountAmount )
				$nAlreadyDiscountedPayAmnt = $nInitialItemTotalPrice - $nTotalDiscountAmount;

			$oCouponOutput = $this->getCouponInfoBySerialNumber( $sCouponSerial, $nOriginalPayAmnt, $nAlreadyDiscountedPayAmnt, $oInArgs->recheck_mode );
			if( !$oCouponOutput->toBool() ) // 쿠폰 입력되었지만 쿠폰 정보에 이상이 있으면 이후 계산하지 않음
				return $oCouponOutput;

			$sDiscountType = $oCouponOutput->data->descount_type;
			$discount_policy = $oCouponOutput->data->discpolicy;
			$nCouponDiscountQtyMax = (int)$oCouponOutput->data->max_qty;
			$aCouponAllowableItems = $oCouponOutput->data->target_items;
			if( $nCouponDiscountQtyMax || count( $aCouponAllowableItems ) )// 최대 수량 정책 혹은 품목 제한 정책 ON이면
			{
				if( $sDiscountType == 'rate' && ( $discount_policy > 0 && $discount_policy <= 1 ) )
				{
					$aTmpCart = array();
					$aTmpCart = $this->_shellDescSortByItemPrice( $aTempItemList );
					$nTmpCouponDiscQtyMax = $nCouponDiscountQtyMax; // 할인 수량 확인하기 위한 임시 변수
					$bDiscountDuplicated = false;
					foreach( $aTmpCart as $key => $val ) 
					{
						if( $nCouponDiscountQtyMax )
						{
							if( $nTmpCouponDiscQtyMax >= $val->quantity )
							{
								$nTempQty = $val->quantity;
								if( $aCouponAllowableItems )
								{
									if( $aCouponAllowableItems[$val->item_srl] == 'Y' )
										$nTmpCouponDiscQtyMax -= $val->quantity;
								}
								else
									$nTmpCouponDiscQtyMax -= $val->quantity;
							}
							else
							{
								if( $aCouponAllowableItems )
								{
									if( $aCouponAllowableItems[$val->item_srl] == 'Y' )
									{
										$nTempQty = $nTmpCouponDiscQtyMax;
										$nTmpCouponDiscQtyMax = 0; 
									}
								}
								else
								{
									$nTempQty = $nTmpCouponDiscQtyMax;
									$nTmpCouponDiscQtyMax = 0; 
								}
							}
						}
						else
							$nTempQty = $val->quantity;
						
						if( $aCouponAllowableItems )
						{
							if( $aCouponAllowableItems[$val->item_srl] == 'Y' ) // 쿠폰 할인할 수 있는 품목이면 쿠폰 할인을 적용함
							{
								// 품목별 프로모션의 설정에 페북 좋아요 할인도 종속되므로 한번에 처리함
								if( isset( $val->oItemDiscountPromotion[0] ) )
									$val->oItemDiscountPromotion[0]->is_applied = 'no'; // 검출된 품목 프로모션을 비활성화
								if( isset( $val->conditional_promotion->promotion[0] ) )
								{
									$val->oConditionalPromotion[0]->is_applied = 'no'; // 검출된 품목 SNS 좋아요 프로모션을 비활성화
									$val->conditional_promotion->promotion[0]->is_applied = 'no'; // alias for old version compatibility
								}

								if( $val->oItemDiscountPromotion[0]->allow_duplication == '1' ) // 품목 프로모션을 다른 프모로션과 중복 사용할 수 있다면
								{
									$nTotalDiscountAmount += $val->discount_amount * $val->quantity; // 품목별 프로모션의 설정에 페북 좋아요 할인도 종속되므로 한번에 처리함
									$bDiscountDuplicated = true;
								}
								else
									$nTotalDiscountAmount += round($val->price * $nTempQty * $discount_policy);
							}
							else // 쿠폰 할인할 수 없는 품목이면 기존 품목 할인을 유지함
								$nTotalDiscountAmount += $val->discount_amount * $val->quantity;
						}
						else // 쿠폰 적용 대상이 설정되지 않으면 모든 장바구니 품목을 점검
						{
							// 품목별 프로모션의 설정에 페북 좋아요 할인도 종속되므로 한번에 처리함
							if( $val->oItemDiscountPromotion[0]->allow_duplication == '1' )
							{
								$nTotalDiscountAmount += $val->discount_amount * $val->quantity; // 품목 기본 할인 정책을 적용한 후
								$nTotalDiscountAmount += round($val->price * $nTempQty * $discount_policy); // 쿠폰에 해당하는 할인 정책을 추가함
								$bDiscountDuplicated = true;
							}
							else
							{
								$nTotalDiscountAmount += round($val->price * $nTempQty * $discount_policy);
								if( isset( $val->oItemDiscountPromotion[0] ) )
									$val->oItemDiscountPromotion[0]->is_applied = 'no'; // 검출된 품목 프로모션을 비활성화
								if( isset( $val->conditional_promotion->promotion[0] ) )
								{
									$val->oConditionalPromotion[0]->is_applied = 'no'; // 검출된 품목 SNS 좋아요 프로모션을 비활성화
									$val->conditional_promotion->promotion[0]->is_applied = 'no'; // alias for old version compatibility
								}
							}
						}
						if( $nCouponDiscountQtyMax && !$nTmpCouponDiscQtyMax ) // 할인 수량 ON이며 제한에 도달하면 정지
							break;
					}
				}
				else if( $sDiscountType == 'amount' && $discount_policy > 1 )
					$nTotalDiscountAmount = $discount_policy;
			}
			else // 최대 수량 정책 OFF, 품목 제한 정책 OFF이면
			{
				if( $sDiscountType == 'rate' && ( $discount_policy > 0 && $discount_policy <= 1 ) )
					$nTotalDiscountAmount = (int)($nInitialItemTotalPrice * $discount_policy);
				else if( $sDiscountType == 'amount' && $discount_policy > 1 )
					$nTotalDiscountAmount = $discount_policy;
			}

			if( $nTotalDiscountAmount > 0 )
			{
				$oCheckoutPromotionInfo->promotion[0]->type = 'coupon';
				$oCheckoutPromotionInfo->promotion[0]->coupon_srl = $oCouponOutput->data->coupon_srl;
				$oCheckoutPromotionInfo->promotion[0]->total_disc_amnt = $nTotalDiscountAmount;
				$nAdditionalDiscount = $nTotalDiscountAmount - $nOriginalDiscountAmnt;
				if( $bDiscountDuplicated && $nAdditionalDiscount > 0 )
					$oCheckoutPromotionInfo->promotion[0]->title = $oCouponOutput->data->promotion_title.' '.number_format($nAdditionalDiscount,0).'원 추가 할인';
				else
					$oCheckoutPromotionInfo->promotion[0]->title = $oCouponOutput->data->promotion_title.' '.number_format($nTotalDiscountAmount,0).'원 할인';

				$oRst->add('disctype', $oCouponOutput->data->descount_type);
				$oRst->add('discpolicy', $oCouponOutput->data->discpolicy);
				$oRst->add('promotion_title', $oCouponOutput->data->promotion_title);
			}
			else
				return new BaseObject(-1, 'msg_meaningless_coupon');
		}
		$oConfig = $this->getModuleConfig();
		if( $oConfig->aPromotionMallOrderModuleSrl[$oInArgs->module_srl] == 'Y' )
		{
			if( strlen( $sCouponSerial ) > 0 )
			{
				if( $nInitialItemTotalPrice != $nTotalDiscountAmount )
					return new BaseObject(-1, 'msg_error_promotion_mall_pay_not_allowed');
			}
			else
			{
				if( $oInArgs->cart->total_discounted_price > 0 )
					return new BaseObject(-1, 'msg_error_promotion_mall_pay_not_allowed');
			}
		}
		else
		{
			if( $nInitialItemTotalPrice * $oConfig->discount_rate_limit/100 < $nTotalDiscountAmount )
				return new BaseObject(-1, sprintf(Context::getLang('msg_error_over_discount_not_allowed'), $oConfig->discount_rate_limit));
		}
		if( !$oSiteBulkOutput->toBool() )
			return $oSiteBulkOutput;
		$oRst->add('total_discount_amount', $nTotalDiscountAmount );
		$oRst->add('promotion_info', $oCheckoutPromotionInfo );
		return $oRst;
	}
/**
 * @brief $this->getPromotionInfoByMemberSrl()의 쿠폰 만료일 계산 코드 블록과 병합해야 함
 * $nOriginalPayAmnt: 정상 결제 청구액
 * $nAlreadyDiscountedPayAmnt: 결제 전 적용된 할인액
 * $sRecheckMode: 다품목 결제에서 부분 품목 취소 시 쿠폰 혜택을 다시 계산하는 방식
 **/
	public function getCouponInfoBySerialNumber( $sCouponSerialNumber, $nOriginalPayAmnt, $nAlreadyDiscountedPayAmnt, $sRecheckMode )
	{
		if( !$sCouponSerialNumber )
			return new BaseObject( -1, 'msg_invalid_coupon_serial' );
	
		$args->coupon_serial = trim( $sCouponSerialNumber );
		$output = executeQuery( 'svpromotion.getCouponInfoBySerialNumber', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_coupon_db_query');

		if( count( $output->data ) != 1 )
			return new BaseObject(-1, 'msg_invalid_coupon_serial');
		
		$sCouponRegDatetime = $output->data->regdate;
		if( $output->data->member_srl > 0 )
		{
			$logged_info = Context::get('logged_info');
			if (!$logged_info) 
				return new BaseObject(-1, 'msg_endorsed_coupon');
			if( $output->data->member_srl != $logged_info->member_srl )
				return new BaseObject(-1, 'msg_endorsed_coupon');
		}
		if( !$sRecheckMode && $output->data->max_use_count <= $output->data->used_count )
			return new BaseObject(-1, 'msg_error_expired_coupon_serial_number' );

		$nCouponSrl = (int)$output->data->coupon_srl;
		$nPromotionSrl = $output->data->promotion_srl;
		unset( $output );
		unset( $args );
		$output = $this->_getCouponPromotionSetupInfo( $nPromotionSrl );
		// 쿠폰 캠페인 on off 상태 검사
		if( !$sRecheckMode && $output->data->status != 1 )
			return new BaseObject(-1, 'msg_coupon_closed' );

		$sCurDatetime = date('YmdHis');
		if( $output->data->begin_date && $output->data->begin_date > $sCurDatetime )
			return new BaseObject(-1, sprintf(Context::getLang('msg_error_coupon_not_began'), date( 'Y-m-d', strtotime($output->data->begin_date) ) ) );

		$bPermitted = true;
		if( count($output->data->allowed_grp) > 0 )
		{
			$logged_info = Context::get('logged_info');
			if (!$logged_info) 
				$logged_info->group_list[0] = 'guest';

			$bPermitted = false;
			foreach($logged_info->group_list as $key=>$val)
			{
				if($output->data->allowed_grp[$key] == 'Y')
				{
					$bPermitted = true;
					break;
				}
			}
		}
		if( !$bPermitted )
			return new BaseObject(-1, 'msg_not_allowed_group_for_coupon' );

		if( !$sRecheckMode && $output->data->end_date )
		{
			if( strpos($output->data->end_date, 'minutes') !== false )
			{
				if( $sCouponRegDatetime )
				{
					$endTime = strtotime($output->data->end_date, strtotime($sCouponRegDatetime));
					$sIndividualCouponExpDatatime = date('YmdHis', $endTime);
					if( $sIndividualCouponExpDatatime < $sCurDatetime )
						return new BaseObject(-1, 'msg_error_coupon_expired' );
				}
			}
			else
			{
				if( $output->data->end_date < $sCurDatetime )
					return new BaseObject(-1, 'msg_error_coupon_expired' );
			}
		}
		if( !$output->toBool() || count( $output->data ) != 1 )
			return new BaseObject(-1, 'msg_error_weird_coupon_serial_number');
		$output->data->coupon_srl = $nCouponSrl;
		$sDiscType = $output->data->descount_type;
		$oCouponPromotion = new stdClass();
		$nCouponDiscountedPayAmnt = 0;
		switch( $sDiscType )
		{
			case 'amount':
				$fDisc = $output->data->descount_amount_policy;
				$nCouponDiscountedPayAmnt = $nOriginalPayAmnt - $fDisc;
				$oCouponPromotion->discount_info = $output->data->promotion_title.'_'.$output->data->promotion_description.'_결제액 중 '. $output->data->descount_amount_policy.'원 할인';
				break;
			case 'rate':
				$fDisc = sprintf( "%.2f", ((int)$output->data->descount_rate_policy)/100 );
				$nCouponDiscountedPayAmnt = $nOriginalPayAmnt * (1- (int)$output->data->descount_rate_policy/100);
				$oCouponPromotion->discount_info = $output->data->promotion_title.'_'.$output->data->promotion_description.'_결제액의 '.$output->data->descount_rate_policy.'% 할인';
				break;
			default:
				$fDisc = 0;
				break;
		}
		// 쿠폰 할인의 혜택이 작으면 처리 거부
		if( $nAlreadyDiscountedPayAmnt <= $nCouponDiscountedPayAmnt )
			return new BaseObject(-1, 'msg_non_economic_coupon');

		$output->data->discpolicy = $fDisc;
		unset( $output->variables );
		unset( $output->data->descount_amount_policy );
		unset( $output->data->descount_amount );
		unset( $output->data->descount_rate_policy );
		unset( $output->data->regdate );
		unset( $output->data->begin_date );
		unset( $output->data->end_date );
		unset( $output->data->promotion_description );
		return $output;
	}
/**
 * @brief 
 **/
	public function getItemPriceList( $item_list, $group_list=null )
	{
		foreach( $item_list as $key=>$val )
		{
			$output = $this->_discountSpecificItem($val);
			$item_list[$key]->discounted_price = $output->discounted_price;
			$item_list[$key]->discount_amount = $output->discount_amount;
			$item_list[$key]->discount_info = $output->discount_info;
			$item_list[$key]->sum_discount_amount = $output->discount_amount * $val->quantity;
			$item_list[$key]->sum_discounted_price = $output->discounted_price * $val->quantity;
			$item_list[$key]->sum_price = $val->price * $val->quantity;
		}
		return $item_list;
	}
/**
 * @brief 제품 상세페이지에서 아이템별 할인 정책 제공
 * svitem.view.php::dispSvitemItemDetail()에서 호출
 **/
	public function getPromotionInfoForItemDetailPage( $item )
	{
		$oPromotionPolicy = array();
		// 기본 증정 추출
		$oGiveawayPromoResult = $this->_getGiveawayItem( $item );
		$oPromotionPolicy['giveaway'] = $oGiveawayPromoResult;
		// 기본 할인액 계산
		$oUnconditionalDiscountPolicy = $this->_discountSpecificItem( $item );
		$oPromotionPolicy['unconditional_disc'] = $oUnconditionalDiscountPolicy;
		$config = $this->getModuleConfig();
		// 최대 할인액 계산
		$nMaxDiscountAmntLimit = (int)($item->price * $config->discount_rate_limit/100);
		// 잔여 할인액 계산
		$nBalanceToGoMax = $nMaxDiscountAmntLimit-$oUnconditionalDiscountPolicy->discount_amount;
		$aFbPromotion = Array( 'fblike', 'fbshare' );
		foreach( $aFbPromotion as $key => $val )
		{
			$oFbLikeConditionalPromoResult = $this->_getFbConditionalDiscountItem( $item, $nBalanceToGoMax, $val );
			if( $oFbLikeConditionalPromoResult->conditional_additional_discount_amount )
			{
				unset( $oFbLikeConditionalPromoResult->error );
				unset( $oFbLikeConditionalPromoResult->message );
				unset( $oFbLikeConditionalPromoResult->variables );
				unset( $oFbLikeConditionalPromoResult->httpStatusCode );
				if( $oUnconditionalDiscountPolicy->discount_amount > 0 )
					$oFbLikeConditionalPromoResult->conditional_additional_discount_amount += $oUnconditionalDiscountPolicy->discount_amount;

				$oConditionalPromoResult->promotion[$key] = $oFbLikeConditionalPromoResult;
			}
		}
        $oConditionalPromoResult = new stdClass();
		$oConditionalPromoResult->fb_app_id = $config->svpromotion_fb_app_id;
		$aCoupon = $this->_getCouponConditionalDiscountItem( $item, $nBalanceToGoMax );
		foreach( $aCoupon as $nIdx => $oVal )
			$oConditionalPromoResult->promotion[] = $oVal;
		$oPromotionPolicy['conditional'] = $oConditionalPromoResult;
		return $oPromotionPolicy;
	}
/**
 * @brief 아이템별 부가 조건부 프로모션 정책 제공
 * svcart.controller.php::createCartObj()에서 호출
 * promotion[1]->resultant_giveaway_qty 와 같이 Qty 구조체가 추가되면 
 * svcart.controller.php::procSvcartUpdateQuantity()도 변경해야 함
 **/
	public function getPromotionDetailForCartAddition($item, $oOrderInfo, $aRequestedPromotion)
	{
		$oPromotionPolicy = array();
		$oItemInfo = new stdClass();
		$oDiscountInfo = new stdClass();
		$oConditionalPromotionInfo = new stdClass();

		// 기존 할인액 계산
		$oUnconditionalDiscountPolicy = $this->_discountSpecificItem($item);
		if(!$oUnconditionalDiscountPolicy->toBool())
			return $oUnconditionalDiscountPolicy;

		$oDiscountInfo->version = svpromotion::PROMO_INFO_VERS;
        $oDiscountInfo->promotion[0] = new stdClass();
		$oDiscountInfo->promotion[0]->type = 'item_policy';
		$oDiscountInfo->promotion[0]->unit_disc_amnt = $oUnconditionalDiscountPolicy->discount_amount;
		$oDiscountInfo->promotion[0]->title = $oUnconditionalDiscountPolicy->discount_info;
		
		if($oDiscountInfo->promotion[0]->unit_disc_amnt > 0)
			$oItemInfo->discount_info = $oDiscountInfo;
		else
			$oItemInfo->discount_info = null;
		
		$oItemInfo->discount_amount = $oUnconditionalDiscountPolicy->discount_amount;
		$oItemInfo->discounted_price = $oUnconditionalDiscountPolicy->discounted_price;
		$config = $this->getModuleConfig();
		// 최대 할인액 계산
		$nMaxDiscountAmntLimit = (int)($item->price * $config->discount_rate_limit/100);
		// 잔여 할인액 계산
		$nBalanceToGoMax = $nMaxDiscountAmntLimit-$oUnconditionalDiscountPolicy->discount_amount;
		
		// FB like 와 FB shr만 conditional_promotion에 남기고 장바구니 DB에 기록함
		$oConditionalPromotionInfo->version = svpromotion::PROMO_INFO_VERS;
		$nIdx = 0;
		foreach($aRequestedPromotion as $key => $sPromotionType)
		{
			switch($sPromotionType)
			{
				case 'fblike': // 아이템별 fb like 할인 정책 가져오기
				case 'fbshare': // 아이템별 fb share 할인 정책 가져오기
					$oConditionalPromoResult = $this->_getFbConditionalDiscountItem($item, $nBalanceToGoMax, $sPromotionType);
					if(!$oConditionalPromoResult->toBool()) 
						return $oConditionalPromoResult;

					if($oConditionalPromoResult->discount_amount > 0)
						$oConditionalPromoResult->conditional_additional_discount_amount += $oUnconditionalDiscountPolicy->discount_amount;

					if($oConditionalPromoResult->conditional_additional_discount_amount > 0)
					{
						$oItemInfo->discount_amount = $oConditionalPromoResult->conditional_additional_discount_amount;
						$oItemInfo->discounted_price = ($item->price - $oConditionalPromoResult->conditional_additional_discount_amount)*$oOrderInfo->quantity;
						$oConditionalPromotionInfo->promotion[$nIdx]->type = $sPromotionType;
						$oConditionalPromotionInfo->promotion[$nIdx]->resultant_disc_amnt = $oConditionalPromoResult->conditional_additional_discount_amount;
						$oConditionalPromotionInfo->promotion[$nIdx]->title = $oConditionalPromoResult->conditional_additional_discount_info;
//////////////////////////////////////////////////////
						$oConditionalPromotionInfo->promotion[$nIdx]->is_applied = 'yes';
///////////////////////////////////////////////////////
						$oItemInfo->conditional_promotion = $oConditionalPromotionInfo;
					}
					break;
				default:
					break;
			}
		}
		$oConditionalPromoResult = new BaseObject();
/*		$oConditionalPromoResult = $this->_getGiveawayItem( $item );
var_dump( $oConditionalPromoResult );
		if( !$oConditionalPromoResult->toBool() ) 
			return $oConditionalPromoResult;
		
		if( $oConditionalPromoResult->conditional_additional_discount_giveaway_item_srl > 0 )
		{
			$oConditionalPromotionInfo->promotion[1]->type = 'giveaway';
			$oConditionalPromotionInfo->promotion[1]->giveaway_item_srl = $oConditionalPromoResult->conditional_additional_discount_giveaway_item_srl;
			$oConditionalPromotionInfo->promotion[1]->resultant_giveaway_qty = $oConditionalPromoResult->conditional_additional_discount_giveaway_item_qty*$oOrderInfo->quantity;
			$oConditionalPromotionInfo->promotion[1]->title = $oConditionalPromoResult->conditional_additional_discount_info;
			$oItemInfo->conditional_promotion = $oConditionalPromotionInfo;
		}
*/
		$oConditionalPromoResult->add('item_promotion_info', $oItemInfo);
		return $oConditionalPromoResult;
	}
/**
 * @brief 아이템별 기본 할인 정책 제공
 **/
	public function getItemPriceDetail( &$item, $bIgnoreLoggedinInfo=false )
	{
		if( $bIgnoreLoggedinInfo )
			$oResult = $this->_discountSpecificItem( $item, true );
		else
			$oResult = $this->_discountSpecificItem( $item );
		return $oResult;
	}
/**
 * @brief 아이템별 부가 조건부 프로모션 정책 상세 내용 제공
 * svitem.model.php::getSvitemConditionalPromoInfo()에서 호출
 **/
	public function getItemConditionalPromotionDetail( $item, $sPromotionType )
	{
		// 기존 할인액 계산
		$oDiscountPolicy = $this->_discountSpecificItem( $item );
		$config = $this->getModuleConfig();
		
		// 최대 할인액 계산
		$nMaxDiscountAmntLimit = (int)($item->price * $config->discount_rate_limit/100);
		// 잔여 할인액 계산
		$nBalanceToGoMax = $nMaxDiscountAmntLimit-$oDiscountPolicy->discount_amount;
		
		switch( $sPromotionType )
		{
			case 'fblike':
			case 'fbshare':
				$oConditionalPromoResult = $this->_getFbConditionalDiscountItem( $item, $nBalanceToGoMax, $sPromotionType );
				if( !$oConditionalPromoResult->toBool() ) 
					return $oConditionalPromoResult;

				if( $oDiscountPolicy->discount_amount > 0 )
				{
					$oConditionalPromoResult->conditional_additional_discount_amount += $oDiscountPolicy->discount_amount;
					$oConditionalPromoResult->fb_app_id = $config->svpromotion_fb_app_id;
				}
				break;
			case 'giveaway':
				$oConditionalPromoResult = $this->_getGiveawayItem( $item ); //, $nBalanceToGoMax );
				if( !$oConditionalPromoResult->toBool() ) 
					return $oConditionalPromoResult;
				break;
			default:
				break;
		}
		return $oConditionalPromoResult;
	}
/**
 * @brief svcart.model.php::_discountItems()에서 호출
 * svorder.model.php::confirmOffer()에서 호출
 **/
	public function getItemPriceCart( $aItemList )
	{
		if( !defined(svorder::ORDER_STATE_ON_DEPOSIT) )
			getClass('svorder');

		$oRet = new stdClass();
		$oRet->total_price=0;
		$oRet->sum_price=0;
		$oRet->total_discounted_price=0;
		$oRet->total_discount_amount=0;
		$oRet->item_list = null;
		
		$oSvitemModel = &getModel('svitem');
		foreach( $aItemList as $nItemIdx => $oItem )
		{
			// 아이템별 기본 할인 정책 가져오기
			$oDiscountRst = $this->_discountSpecificItem( $oItem );
			if( $oDiscountRst->discount_amount ) // 상품별 기본 할인 정책 원본 구조체 기록 for svorder.order.php::_setSvCartList()
			{
				$aItemList[$nItemIdx]->oItemDiscountPromotion[0]->type = $oDiscountRst->promotion_type;
				$aItemList[$nItemIdx]->oItemDiscountPromotion[0]->unit_disc_amnt = $oDiscountRst->discount_amount;
				$aItemList[$nItemIdx]->oItemDiscountPromotion[0]->discounted_price = $oDiscountRst->discounted_price;
				$aItemList[$nItemIdx]->oItemDiscountPromotion[0]->title = $oDiscountRst->discount_info;
				$aItemList[$nItemIdx]->oItemDiscountPromotion[0]->allow_duplication = $oDiscountRst->allow_duplication;
				$aItemList[$nItemIdx]->oItemDiscountPromotion[0]->is_applied = 'yes'; // 프로모션 정보 버전 v1.0과 UI 호환을 위해 true false가 아닌 yes no로 설정함
			}

			$aItemList[$nItemIdx]->discounted_price = $oDiscountRst->discounted_price;
			$aItemList[$nItemIdx]->discount_amount = $oDiscountRst->discount_amount;
			$aItemList[$nItemIdx]->discount_info = '<p id=\'discount_info\' class=\''.$oItem->item_srl.'\'>'.$oDiscountRst->discount_info.'</p>';
			$aItemList[$nItemIdx]->sum_discount_amount = $oDiscountRst->discount_amount * $oItem->quantity;
			$aItemList[$nItemIdx]->sum_discounted_price = $oDiscountRst->discounted_price * $oItem->quantity;
			$aItemList[$nItemIdx]->sum_price = $oItem->price * $oItem->quantity;

			// 장바구니 담는 시점에서만 발생하는 아이템별 fb like or fb shr 할인 정책 가져오기
			$oConditionalPromotion = unserialize( $oItem->conditional_promotion );
			if( $oConditionalPromotion )
			{
				if( $oConditionalPromotion->version == '1.0' || $oConditionalPromotion->version == '1.1' )
				{
					foreach( $oConditionalPromotion->promotion as $promotion_key=>$promotion_val)
					{
						if( $promotion_val->type == 'fblike' || $promotion_val->type == 'fbshare' )
						{
							$aItemList[$nItemIdx]->discount_info .= '<p id=\'fb_discount_info\' class=\''.$oItem->item_srl.'\'>'.$promotion_val->title.'</p>';
							$aItemList[$nItemIdx]->discount_amount += $promotion_val->resultant_disc_amnt;
							$aItemList[$nItemIdx]->discounted_price = $aItemList[$nItemIdx]->discounted_price + $oItem->option_price - $promotion_val->resultant_disc_amnt;
							$aItemList[$nItemIdx]->sum_discount_amount += $promotion_val->resultant_disc_amnt * $oItem->quantity;
							$aItemList[$nItemIdx]->sum_discounted_price = $aItemList[$nItemIdx]->discounted_price * $oItem->quantity;
						}
					}
					$aItemList[$nItemIdx]->oConditionalPromotion = $oConditionalPromotion->promotion; // 상품별 조건부 할인 정책 원본 구조체 기록 for svorder.order.php::_setSvCartList()
				}
			}
			$oGiveawayPromoResult = $this->_getGiveawayItem( $oItem );
			if( !$oGiveawayPromoResult->toBool() ) 
				return $oGiveawayPromoResult;
			if( $oGiveawayPromoResult->conditional_additional_discount_giveaway_item_srl > 0 )
			{
				$aItemList[$nItemIdx]->discount_info .= '<p id=\'giveaway_info\' class=\''.$oItem->item_srl.'\'>'.$oGiveawayPromoResult->conditional_additional_discount_info.'</p>';
				//$oGiveawayPromoResult->conditional_additional_discount_giveaway_item_qty*$oOrderInfo->quantity;
				// 상품별 증정 정책 원본 구조체 기록 for svorder.order.php::_setSvCartList()
				$aItemList[$nItemIdx]->oGiveawayPromotion[0]->type = $oGiveawayPromoResult->conditional_additional_discount_type;
				$aItemList[$nItemIdx]->oGiveawayPromotion[0]->title = $oGiveawayPromoResult->conditional_additional_discount_info;
				$aItemList[$nItemIdx]->oGiveawayPromotion[0]->giveaway_item_srl = $oGiveawayPromoResult->conditional_additional_discount_giveaway_item_srl;
				$aItemList[$nItemIdx]->oGiveawayPromotion[0]->giveaway_item_name = $oGiveawayPromoResult->conditional_additional_discount_giveaway_item_name;
				$aItemList[$nItemIdx]->oGiveawayPromotion[0]->giveaway_item_price = $oGiveawayPromoResult->conditional_additional_discount_giveaway_item_price;
				$aItemList[$nItemIdx]->oGiveawayPromotion[0]->giveaway_item_qty = $oGiveawayPromoResult->conditional_additional_discount_giveaway_item_qty;
				//$aItemList[$nItemIdx]->oGiveawayPromotion[0]->giveaway_item_url = $oGiveawayPromoResult->conditional_additional_discount_giveaway_item_url;
			}

			// option
			$oOption = FALSE;
			if( $oItem->option_srl )
			{
				$aOptions = $oSvitemModel->getOptions($oItem->item_srl);
				if( isset($aOptions[$oItem->option_srl]) )
					$oOption = $aOptions[$oItem->option_srl];
			}
			if( $oOption )
			{
				// 단가
				$aItemList[$nItemIdx]->price = $oItem->price + ($oOption->price);
				// 할인가 합계
				$aItemList[$nItemIdx]->sum_discounted_price += ($oOption->price * $oItem->quantity);
				// 판매가(원가격)
				$aItemList[$nItemIdx]->sum_price += ($oOption->price * $oItem->quantity);
			}

			if( $oItem->order_status == svorder::ORDER_STATE_CANCEL_REQUESTED ||
				$oItem->order_status == svorder::ORDER_STATE_CANCELLED )
				continue; // 취소 상태인 장바구니 품목은 계산에서 제외함

			$oRet->total_discounted_price += $aItemList[$nItemIdx]->sum_discounted_price;
			$oRet->total_discount_amount += $aItemList[$nItemIdx]->sum_discount_amount;
			$oRet->sum_price += $aItemList[$nItemIdx]->sum_price;
		}
		$oRet->total_price = round($oRet->total_discounted_price);
		$oRet->item_list = $aItemList;
		return $oRet;
	}
/**
 * @brief 적립금 사용 요청이 타당한지 검토함
 **/
	public function isClaimingReservesAcceptable( $nClaimingReserves )
	{
		$logged_info = Context::get('logged_info');
		if( !$logged_info )
			return new BaseObject(-1, 'msg_login_required');

		if( !$nClaimingReserves )
			return new BaseObject(-1, 'msg_error_no_claiming_reserves');

		$oConfig = $this->getModuleConfig();
		$oReservesRst = $this->getReservesStatusByMemberSrl($logged_info->member_srl);
		$nRemainingReserves = $oReservesRst->get('nRemainingReserves');
		if( (int)$oConfig->minimum_reserves_available <= $nRemainingReserves && $nClaimingReserves <= $nRemainingReserves )
			return new BaseObject();
		else
			return new BaseObject(-1, 'msg_error_not_enough_reserves');
	}
/**
 * @brief 
 **/	
	private function _getCouponConditionalDiscountItem( $item_info, $nBalanceToGoMax )
	{
		$aAppliedCoupon = array();
		$aCoupon = $this->getCouponPromotionList();
		foreach( $aCoupon as $nIdx => $oVal )
		{
			if( $oVal->status == 'ON' && $oVal->public )
			{
				if( $oVal->target_items[$item_info->item_srl] == 'Y' )
				{
					$oRst = new stdClass;
					$oRst->conditional_additional_discount_type = 'coupon';
					$oRst->conditional_additional_discount_amount = 0;
					$oRst->conditional_additional_discount_info = '';
					
					$discount_info = '개당 ';
					if( $oVal->descount_type == 'amount' ) //$oDiscountInfo->opt == '1' )
					{
						$nDiscountAmount = $oVal->descount_amount_policy;//$oDiscountInfo->price;
						$nRestrictedDiscountAmnt = min( $nDiscountAmount, $nBalanceToGoMax );
					}
					else if( $oVal->descount_type == 'rate' ) //$oDiscountInfo->opt == '2' )
					{
						$nDiscountAmount = $item_info->price * $oVal->descount_rate_policy / 100;
						$nRestrictedDiscountAmnt = min( $nDiscountAmount, $nBalanceToGoMax );
					}

					$nRestrictedDiscountAmnt = (int)$nRestrictedDiscountAmnt; // 소수점 
					$discount_info .= number_format($nRestrictedDiscountAmnt,0).'원 '.$oVal->promotion_title.' 쿠폰 할인';
					$oRst->conditional_additional_discount_amount = (int)$nRestrictedDiscountAmnt;
					$oRst->conditional_additional_discount_info = $discount_info;
					$aAppliedCoupon[] = $oRst;
				}
			}
		}
		return $aAppliedCoupon;	
	}
/**
 * @brief 
 **/
	private function _getCouponPromotionSetupInfo( $nPromotionSrl )
	{
		if( !$nPromotionSrl )
			return new BaseObject(-1, 'msg_invalid_coupon_promotion_srl' );

		$args->promotion_srl = $nPromotionSrl;
		$output = executeQuery('svpromotion.getCouponPromotionDetail', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_db_query');
		else
		{
			if( $output->data->allowed_grp )
				$output->data->allowed_grp = unserialize($output->data->allowed_grp);
			else
				$output->data->allowed_grp = null;

			if( $output->data->target_items )
				$output->data->target_items = unserialize($output->data->target_items);
			else
				$output->data->target_items = null;

			return $output;
		}
	}
/**
 * @brief 
 **/
	private function _getGiveawayItem( &$item_info )//, $nBalanceToGoMax )
	{
		$result = new BaseObject();
		$result->conditional_additional_discount_type = '';
		$result->conditional_additional_discount_info = '';
        $args = new stdClass();
		$args->module_srl = $item_info->module_srl;
		$args->item_srl = $item_info->item_srl;
		$output = executeQuery( 'svpromotion.getGiveawayPromotionByItem', $args );
		if(!$output->toBool())
			return $output;

		if( count( $output->data ) == 0 )
			return $result;
		
		$oSvitemModel = &getModel('svitem');
		$oGiveawayItemInfo = $oSvitemModel->getItemInfoByItemSrl($output->data->giveaway_item_srl);
		
		$oModuleModel = &getModel('module');
		$oShopModuleInfo = $oModuleModel->getModuleInfoByDocumentSrl( $oGiveawayItemInfo->document_srl );

		$result->conditional_additional_discount_type = 'giveaway';
		$result->conditional_additional_discount_giveaway_item_srl = $output->data->giveaway_item_srl;
		$result->conditional_additional_discount_giveaway_item_name = $oGiveawayItemInfo->item_name;
		$result->conditional_additional_discount_giveaway_item_price = $oGiveawayItemInfo->price;
		$result->conditional_additional_discount_giveaway_item_qty = $output->data->giveaway_quantity;
		$result->conditional_additional_discount_giveaway_item_url = '/'.$oShopModuleInfo->mid.'/'.$oGiveawayItemInfo->document_srl;
		$result->conditional_additional_discount_info = $output->data->giveaway_info.'_'.$oGiveawayItemInfo->item_name.'_증정';
		return $result;	
	}
/**
 * @brief 
 **/	
	private function _getFbConditionalDiscountItem( &$item_info, $nBalanceToGoMax, $sPromotionType )
	{
		$result = new BaseObject();
		$result->conditional_additional_discount_type = '';
		$result->conditional_additional_discount_amount = 0;
		$result->conditional_additional_discount_info = '';
		if( is_null( $sPromotionType ) || ( $sPromotionType != 'fblike' && $sPromotionType != 'fbshare' ) )
		{
			$result->conditional_additional_discount_info = '잘못된 facebook 할인 요청입니다.';
			return $result;	
		}
        $args = new stdClass();
		$args->item_srl = $item_info->item_srl;
		$args->promotion_type = $sPromotionType;//'fblike';
		$output = executeQuery( 'svpromotion.getConditionalDiscountByItem', $args );
		if(!$output->toBool())
			return $output;

		if( count( $output->data ) == 0 )
			return $result;

		$oDiscountInfo = $output->data;
		if( $oDiscountInfo->opt == '1' )
		{
			$nDiscountAmount = $oDiscountInfo->price;
			$nRestrictedDiscountAmnt = min( $nDiscountAmount, $nBalanceToGoMax );
		}
		else if( $oDiscountInfo->opt == '2' )
		{
			$nDiscountAmount = $item_info->price * $oDiscountInfo->price / 100;
			$nRestrictedDiscountAmnt = min( $nDiscountAmount, $nBalanceToGoMax );
		}
		
		if( $sPromotionType == 'fblike' )
			$sPromotionTitle = 'Like';
		if( $sPromotionType == 'fbshare' )
			$sPromotionTitle = 'Share';

		$nRestrictedDiscountAmnt = (int)$nRestrictedDiscountAmnt; // 소수점 
		$discount_info = '개당 '.number_format($nRestrictedDiscountAmnt,0).'원 '.$sPromotionTitle.' 즉시 할인';
		$result->conditional_additional_discount_type = $sPromotionType;
		$result->conditional_additional_discount_amount = (int)$nRestrictedDiscountAmnt;
		$result->conditional_additional_discount_info = $discount_info;
		return $result;	
	}
/**
 * @brief core of discount policy for a specific itme
 * $bIgnoreLoggedinInfo param is for npay order info validation
 **/	
	private function _discountSpecificItem( &$item, $bIgnoreLoggedinInfo=false )
	{
		if( $bIgnoreLoggedinInfo )
			$logged_info = null;
		else
			$logged_info = Context::get('logged_info');
		
		if( $logged_info )
		{
			$oMemberModel = &getModel('member');
			$group_list = $oMemberModel->getMemberGroups($logged_info->member_srl);
		}
		else
			$group_list = array();
		
		$config = $this->getModuleConfig();
		// 개별 아이템 할인 정책 판별 클래스 생성
		$oPromotionEvaluator = new svpromotionUnit( $item->price, $config->discount_rate_limit );
		// 회원별 할인
		$oMemberDiscountOutput = $this->_getMemberDiscount( $item, $logged_info );
		$oPromotionEvaluator->add( $oMemberDiscountOutput ); 
		// 구매수량 할인
		$oBulkDiscountOutput = $this->_getItemLevelQuantityDiscount( $item );
		$oPromotionEvaluator->add( $oBulkDiscountOutput ); 
		// 상품 할인
		$oItemDiscountOutput = $this->_getItemDiscount( $item );
		$oPromotionEvaluator->add( $oItemDiscountOutput); 
		// 상품별 그룹할인 계산 TBD/////////////////
		//$oGroupDiscountOutput = $this->_getGroupDiscount($item, $group_list);
		///////////////////////////////////
		// 사이트그룹할인
		$oSitegroupDiscountOutput = $this->_getSiteGroupDiscount( $item, $group_list);
		$oPromotionEvaluator->add( $oSitegroupDiscountOutput ); 

		// referral 할인
		//if( strlen( $_SESSION['HTTP_INIT_REFERER'] ) > 0 )
		//{
		//	$sTargetReferrer = 'singleview';
		//	if( eregi( $sTargetReferrer, $_SESSION['HTTP_INIT_REFERER'] ) >-1 )
		//	{
		//		$output->discount_amount = 5000;
		//		$output->discounted_price -= $output->discount_amount;
		//		$output->discount_info = $sTargetReferrer.'회원할인';
		//	}
		//}	
		// 개별 아이템 할인 정책 판별
		return $oPromotionEvaluator->getMaxDiscount();
	}
/**
 * @brief 
 **/
	private function _getMemberDiscount( &$item_info, $logged_info )
	{
		// get email domain group discount policy firstly
		$aMemeberEmailInfo = $logged_info->email_host;
        $args = new stdClass();
		$args->email_domain = $aMemeberEmailInfo;
		$oEmailDomainDiscountInfo = executeQuery('svpromotion.getMemberDiscountInfoByEmailDomain', $args );
		
		if( count( $oEmailDomainDiscountInfo->data ) )
		{
			$output = new BaseObject();
			$output->data->opt = $oEmailDomainDiscountInfo->data->opt;
			$output->data->discount = $oEmailDomainDiscountInfo->data->price;
			$output->data->member_target = $oEmailDomainDiscountInfo->data->description;
		}
		
		// get individual member discount policy secondly
		unset($args);
        $args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$oMemberDiscountInfo = executeQuery('svpromotion.getMemberDiscountInfo', $args);

		if( count( $oMemberDiscountInfo->data ) )
		{
			$output = new BaseObject();
			$output->data->opt = $oMemberDiscountInfo->data->opt;
			$output->data->discount = $oMemberDiscountInfo->data->price;
			$output->data->member_target = $oMemberDiscountInfo->data->description;
		}
		else
		{
			if(!$oEmailDomainDiscountInfo->toBool())
				return $output;
			if(!$oEmailDomainDiscountInfo->data)
				return new BaseObject();
		}
		
		// decide meber discount policy finally
		$member_discount_data = $output->data;
		$member_opt = $member_discount_data->opt;
		$member_discount = $member_discount_data->discount;
		$member_target = $member_discount_data->member_target;
	
		if($member_opt == '1')
		{
			$discounted_price = $item_info->price - $member_discount;
			$discount_info .= $member_target.' '.number_format($member_discount,0). '원 할인';
		}
		else
		{
			$discounted_price = $item_info->price * ((100 - $member_discount) / 100);
			$discount_info .= $member_target.' '.$member_discount . '% 할인';
		}

		if( !$discounted_price )
			return new BaseObject();
		
		$output->promotion_type = 'member_policy';
		$output->discount_amount = $item_info->price - $discounted_price;
		$output->discounted_price = $discounted_price;
		$output->discount_info = $discount_info;

		return $output;
	}
/**
 * @brief check item level bulk promotion
 **/
	private function _getItemLevelQuantityDiscount( &$item_info )
	{
        $args = new stdClass();
		$args->module_srl = $item_info->module_srl;
		$args->item_srl = 0;
		$output = executeQueryArray( 'svpromotion.getBulkDiscountInfo', $args );
		if(!$output->toBool()) 
			return $output;

		$result = new BaseObject();
		$result->promotion_type = 'item_qty_policy';
		$result->discount_amount = 0;
		$result->discounted_price = 0;
		$result->discount_info = '';
		if( count( $output->data ) == 0 )
		{
			$result->discount_amount = 0;
			$result->discounted_price = $item_info->price;
			$result->discount_info = '';
			return $result;
		}

		$purchase_count = $item_info->quantity; // 상세페이지 이후에 설정됨
		$nDesignatedKey = 0;
		foreach( $output->data as $key => $val )
		{
			if( $purchase_count < $val->min_quantity )
			{
				if( $key == 0 )
					$nDesignatedKey = -1;
				else
					$nDesignatedKey = $key-1;
				break;
			}
		}
		
		if( $nDesignatedKey == -1 )
			$result->discount_amount = 0;
		else
		{
			$quantity_opt = $output->data[$nDesignatedKey]->opt;
			$quantity_discount = $output->data[$nDesignatedKey]->price;
			if( $quantity_opt == '1' )
			{
				$discounted_price = ($item_info->price - $quantity_discount) ;
				$discount_info = '개당 '.number_format($quantity_discount,0).'원 수량할인';
			}
			else if( $quantity_opt == '2' )
			{
				$discounted_price = $item_info->price * ((100 - $quantity_discount) / 100);
				$discount_info = $quantity_discount.'% 수량할인';
			}

			$result->discount_amount = $item_info->price - $discounted_price;
			$result->discounted_price = $discounted_price;
			$result->discount_info = $discount_info;
		}
		return $result;	
	}
/**
 * @brief $sOrderDate = date('YmdHis')
 **/
	private function _getItemDiscount($oItemInfo, $sOrderDate = null)
	{
        $oArgs = new stdClass();
		$oArgs->item_srl = $oItemInfo->item_srl;
		$output = executeQueryArray('svpromotion.getGroupDiscountByItem', $oArgs);
		if(!$output->toBool())
			return $output;

		$oRst = new BaseObject();
		$oRst->promotion_type = 'item_policy';
		$oRst->discount_amount = 0;
		$oRst->discounted_price = $oItemInfo->price;
		$oRst->allow_duplication = $oDiscountInfo->allow_duplication;
		$oRst->discount_info = 0;

		if(!$sOrderDate)
		{
			reset($output->data);
			$nFirstIdx = key($output->data);
			$oDiscountInfo = $output->data[$nFirstIdx];
			$dtBegin = strtotime($oDiscountInfo->begindate);
			$dtEnd = strtotime($oDiscountInfo->enddate);
			$dtNow = time();
			
			if($dtBegin && $dtBegin >= $dtNow)
				return $oRst;

			if($dtEnd && $dtEnd <= $dtNow)
				return $oRst;
			
			if($dtBegin && $dtEnd)
			{
				if($dtBegin >= $dtNow || $dtEnd <= $dtNow)
					return $oRst;
			}
			if($oDiscountInfo->opt == '1')
			{
				$discounted_price = $oItemInfo->price - $oDiscountInfo->price;
				$nDiscountAmount = $oDiscountInfo->price;
				$discount_info = $oDiscountInfo->discount_info.' '.number_format($oDiscountInfo->price,0).'원 할인';
			}
			else if($oDiscountInfo->opt == '2')
			{
				$discounted_price = $oItemInfo->price * ((100 - $oDiscountInfo->price) / 100);
				$nDiscountAmount = $oItemInfo->price * $oDiscountInfo->price / 100;
				$discount_info = $oDiscountInfo->discount_info.' '.$oDiscountInfo->price.'% 할인';
			}
			$oRst->discount_amount = $nDiscountAmount;
			$oRst->discounted_price = $discounted_price;
			$oRst->allow_duplication = $oDiscountInfo->allow_duplication;
			$oRst->discount_info = $discount_info;
		}
		else
		{
			$output = executeQueryArray('svpromotion.getGroupDiscountByItem', $oArgs);
			if(!$output->toBool())
				return $output;

			foreach($output->data as $nIdx => $oData)
			{
				$oDiscountInfo = $oData;
				$dtBegin = strtotime($oDiscountInfo->begindate);
				$dtEnd = strtotime($oDiscountInfo->enddate);
				$dtOrder = strtotime($sOrderDate);
				
				if($dtBegin && $dtBegin >= $dtOrder)
					continue;

				if($dtEnd && $dtEnd <= $dtOrder)
					continue;
				
				if($dtBegin && $dtEnd)
				{
					if($dtBegin >= $dtOrder || $dtEnd <= $dtOrder)
						continue;
				}
				if($oDiscountInfo->opt == '1')
				{
					$discounted_price = $oItemInfo->price - $oDiscountInfo->price;
					$nDiscountAmount = $oDiscountInfo->price;
					$discount_info = $oDiscountInfo->discount_info.' '.number_format($oDiscountInfo->price,0).'원 할인';
				}
				else if($oDiscountInfo->opt == '2')
				{
					$discounted_price = $oItemInfo->price * ((100 - $oDiscountInfo->price) / 100);
					$nDiscountAmount = $oItemInfo->price * $oDiscountInfo->price / 100;
					$discount_info = $oDiscountInfo->discount_info.' '.$oDiscountInfo->price.'% 할인';
				}
				$oRst->discount_amount = $nDiscountAmount;
				$oRst->discounted_price = $discounted_price;
				$oRst->allow_duplication = $oDiscountInfo->allow_duplication;
				$oRst->discount_info = $discount_info;
				break;
			}
		}
		return $oRst;
	}
/**
 * @brief 
 **/
	private function _getGroupDiscount( &$item_info, $group_list )
	{
		$args->item_srl = $item_info->item_srl;
		$output = executeQueryArray('svpromotion.getGroupDiscount', $args);

		if( !$output->toBool() )
			return $output;
		$group_discount = $output->data;

		if( !is_array( $group_discount ) )
			$group_discount = array();
		$discounted_price = 0;
		$discount_info = '';
		
		foreach( $group_discount as $key => $val )
		{
			if( array_key_exists( $val->group_srl, $group_list ) ) 
			{
				$discount_info = $group_list[$val->group_srl];
				if( $val->opt=='2' ) 
				{
					$discounted_price = $item_info->price * ((100 - $val->price) / 100);
					$discount_info .= ' '.$val->price.'% 할인';
				} 
				else 
				{
					$discounted_price = $item_info->price - $val->price;
					$discount_info .= ' 할인';
				}
				if ($discounted_price > 0)
					break;
			}
		}
		if (!$discounted_price) 
			$discounted_price = $item_info->price;

		$output = new BaseObject();
		$output->promotion_type = 'group_policy';
		$output->discount_amount = $item_info->price - $discounted_price;
		$output->discounted_price = $discounted_price;
		$output->discount_info = $discount_info;
		return $output;
	}
/**
 * @brief 
 **/
	private function _getSiteGroupDiscount( &$item_info, $group_list )
	{
		$output = executeQueryArray('svpromotion.getSiteGroupDiscount' );
		if( !$output->toBool() )
			return $output;
		
		$group_discount = $output->data;

		if( !is_array( $group_discount ) )
			$group_discount = array();

		$discounted_price = 0;
		$discount_info = '';
		foreach( $group_discount as $key => $val )
		{
			if( array_key_exists( $val->group_srl, $group_list ) )
			{
				$discount_info = $group_list[$val->group_srl];
				if( $val->opt == '1' )
				{
					$discounted_price = $item_info->price - $val->price;
					$discount_info .= ' 할인';
				}
				else if( $val->opt == '2' )
				{
					$discounted_price = $item_info->price * ((100 - $val->price) / 100);
					$discount_info .= ' '.$val->price.'% 할인';
				}
				
				if( $discounted_price > 0 )
					break;
			}
		}
		if( !$discounted_price )
			$discounted_price = $item_info->price;

		$output = new BaseObject();
		$output->promotion_type = 'site_group_policy';
		$output->discount_amount = $item_info->price - $discounted_price;
		$output->discounted_price = $discounted_price;
		$output->discount_info = $discount_info;
		return $output;
	}
/**
 * @brief 장바구니 내용을 품목 가격 기준으로 내림차순 쉘정렬
 * 평균수행시간 : O(nlogn^2)
 **/
	private function _shellDescSortByItemPrice( $aCart )
	{ 
		$nSize = count($aCart);
		for( $i=0; $i < count($aCart); $i++)
		{
			$nSize = $nSize/3+1;
			for( $j=0; $nSize + $j < count($aCart); $j++)
			{
				if( $aCart[$j]->price < $aCart[$j + $nSize]->price )
				{
					$temp = $aCart[$j];
					$aCart[$j] = $aCart[$j+$nSize];
					$aCart[$j+$nSize] = $temp;
				}
			}
		}
		return $aCart;
	}
/**
 * @brief 회원의 적립금 잔액 추출
 **/
	/*public function getRemainigReservesByMemberSrl( $nMemberSrl )
	{
		$args->member_srl = (int)$nMemberSrl;
		$output = executeQueryArray( 'svpromotion.getReservesLogByMemberSrl', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_reserves_db_query');
		
		$nRemainingReserves = 0;
		foreach( $output->data as $key=>$val )
		{
			if( $val->is_deleted == 'N' && $val->is_active == 'Y' )
			{
				if( $val->mode == '+' )
					$nRemainingReserves +=  $val->amount;
				else if( $val->mode == '-' )
					$nRemainingReserves -=  $val->amount;
			}
		}
		return $nRemainingReserves;
	}*/
/**
 * @brief modules/svorder/ext_class/npay/npay_api.class.php::_insertOrderFromNpay()에서 호출
 * npay는 주문서 생성시 쇼핑몰 자체 할인액 등을 무시해 버리기 때문에, npay API가 제공하는 단위가격 정보로는 자사몰의 할인 정책을 알 수 없음.
 **/
	/*public function getItemDiscountInfo4npay( $nItemSrl, $sOrderDate )
	{
		$oSvitemModel = &getModel('svitem');
		$oItemInfo = $oSvitemModel->getItemInfoByItemSrl($nItemSrl);
		$oItemInfo->item_srl = $nItemSrl;
		$oItemInfo->price = $oItemInfo->price ;
		return $this->_getItemDiscount( $oItemInfo, $sOrderDate );
	}*/
}