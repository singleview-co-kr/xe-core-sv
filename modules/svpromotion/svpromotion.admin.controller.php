<?php
/**
 * @class  svpromotionAdminController
 * @author singleview(root@singleview.co.kr)
 * @brief  svpromotionAdminController
 */
class svpromotionAdminController extends svpromotion
{
/**
 * @brief initialization
 **/
	public function init() 
	{
	}
/**
 * @brief 모듈 환경설정값 쓰기
 **/
	public function procSvpromotionAdminConfig() 
	{
		$oArgs = Context::getRequestVars();

		$oSvorderAdminModel = &getAdminModel('svorder');
		$aSvorderPage = $oSvorderAdminModel->getModInstList();

		$aPromotionMallOrderModuleSrl = array();
		foreach($aSvorderPage as $key=>$val )
		{
			if( Context::get($val->module_srl) == 'Y' )
				$aPromotionMallOrderModuleSrl[$val->module_srl] = 'Y';
		}
		$oArgs->aPromotionMallOrderModuleSrl = $aPromotionMallOrderModuleSrl;

		$output = $this->_saveModuleConfig($oArgs);
		if(!$output->toBool())
			$this->setMessage( 'error_occured' );
		else
			$this->setMessage( 'success_updated' );

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvpromotionAdminConfig');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}
/**
 * @brief 사이트 수량할인 관런 설정 쓰기
 **/
	public function procSvpromotionAdminSiteBulkPromotionMgmt() 
	{
		$oArgs = Context::getRequestVars();
		foreach( $oArgs->min_bulk_qty as $key=>$val )
		{
			if( (int)$val > 0 )
				$aSiteBulkPromotionQtyRange[$val] = $oArgs->discount_rate[$key];
		}
		$oFinalArgs = new stdClass();
		$oFinalArgs->site_bulk_promotion['qty_range'] = $aSiteBulkPromotionQtyRange;
		if( $oArgs->allow_site_bulk_promotion == 'Y' )
			$oFinalArgs->site_bulk_promotion['toggle'] = 'Y';
		
		if( $oArgs->site_bulk_promotion_mode == 'exclude' || $oArgs->site_bulk_promotion_mode == 'include' )
			$oFinalArgs->site_bulk_promotion['mode'] = $oArgs->site_bulk_promotion_mode;

		$aSiteBulkPromotionItem = array();
		foreach( $oArgs->site_bulk_promotion_item as $key=>$val )
			$aSiteBulkPromotionItem[$val] = 'Y';
		$oFinalArgs->site_bulk_promotion['item'] = $aSiteBulkPromotionItem;
		$output = $this->_saveModuleConfig($oFinalArgs);
		if(!$output->toBool())
			$this->setMessage( 'error_occured' );
		else
			$this->setMessage( 'success_updated' );

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvpromotionAdminSitePromotion');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}
/**
 * @brief 적립금 관런 설정 쓰기
 **/
	public function procSvpromotionAdminReservesMgmt() 
	{
		$oArgs = Context::getRequestVars();
		$output = $this->_saveModuleConfig($oArgs);
		if(!$output->toBool())
			$this->setMessage( 'error_occured' );
		else
			$this->setMessage( 'success_updated' );

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvpromotionAdminReserves');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}
/**
 * @brief insert promotion
 **/
	public function procSvpromotionAdminInsertCouponPromotion()
	{
		// 사용자 입력 변수 설정
		$args = Context::gets('status', 'public', 'sv_promotion_title','sv_promotion_description','sv_promotion_descount_amount','sv_promotion_descount_rate',
			'sv_promotion_descount_type', 'coupon_max_issue', 'coupon_max_usage', 'begin_date', 'end_date', 'max_qty', 'page' );
		
		if( strlen( $args->sv_promotion_title ) == 0 )
			return new BaseObject(-1, 'msg_invalid_promotion_title' );

		if( strlen( $args->sv_promotion_descount_amount ) == 0 && strlen( $args->sv_promotion_descount_rate ) == 0 )
			return new BaseObject(-1, 'msg_invalid_discount_policy1' );

		if( strlen( $args->sv_promotion_descount_type ) == 0 )
			return new BaseObject(-1, 'msg_invalid_discount_policy2' );

		if( $args->begin_date >  $args->end_date )
			return new BaseObject(-1, 'msg_invalid_coupon_working_period' );
		
		if( !$args->max_qty )
			$args->max_qty = 0;
		
		$oTargetItemArgs = Context::getRequestVars();
		$nPromotionSrl = $oTargetItemArgs->promotion_srl;
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$output = $oSvpromotionAdminModel->getCouponPromotionSetupInfo( $nPromotionSrl );

		foreach( $oTargetItemArgs as $sKeyName=>$sKeyValue )
		{
			if(strpos($sKeyName, 'target_items_') !== false) 
			{
				$aConf = explode( '_', $sKeyValue );

				if( $aConf[1] == 'Y' )
					$output->data[0]->target_items[$aConf[0]] = 'Y';
				elseif( $aConf[1] == 'N' && !is_null( $output->data[0]->target_items[$aConf[0]] ) )
					unset( $output->data[0]->target_items[$aConf[0]] );
			}
		}
	
		if( count( $output->data[0]->target_items ) > 0 )
			$args->target_items = serialize($output->data[0]->target_items);
		else
			$args->target_items = '';
				
		$oMemberModel = &getModel('member');
		$aMemberGroup = $oMemberModel->getGroups();
		$oGuest->group_srl = 0;
		$aMemberGroup[0] = $oGuest;
		ksort($aMemberGroup);
		$aGroupAllowPolicy = array();
		foreach( $aMemberGroup as $key=>$val )
		{
			if( Context::get('group_allow_'.$val->group_srl) )
				$aGroupAllowPolicy[$val->group_srl] = Context::get('group_allow_'.$val->group_srl);
		}
		$args->group_allow_policy = serialize($aGroupAllowPolicy);

		$nPromotionSrl = (int)Context::get('promotion_srl');
		if( $nPromotionSrl > 0 )
		{
			$args->sv_promotion_srl = $nPromotionSrl;
			$output = executeQuery('svpromotion.updateCouponPromotion', $args );
		}
		else
		{
			$args->sv_promotion_srl = getNextSequence();
			$output = executeQuery('svpromotion.insertCouponPromotion', $args );
		}

		if(!$output->toBool())
			return $output;

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvpromotionAdminCouponPromotionInfo', 'promotion_srl', $args->sv_promotion_srl, 'page', $args->page ));
	}
/**
 * @brief insert promotion
 **/
	public function procSvpromotionAdminUpdateEmailDomainPolicy()
	{
		// 사용자 입력 변수 설정
		$args = Context::gets('email_domain_name', 'email_domain_discount_title','email_domain_policy_opt','email_domain_discount_amount' );

		if( strlen( $args->email_domain_name ) == 0 )
			return new BaseObject(-1, 'msg_invalid_email_domain_name' );

		if( strlen( $args->email_domain_discount_title ) == 0 )
			return new BaseObject(-1, 'msg_invalid_email_domain_discount_title' );

		if( is_null( $args->email_domain_policy_opt ) || $args->email_domain_policy_opt > 3 )
			return new BaseObject(-1, 'msg_invalid_email_domain_policy_opt' );
		
		if( (int)$args->email_domain_discount_amount == 0 )
			return new BaseObject(-1, 'msg_invalid_email_domain_discount_amount' );
		
		$nEmailDomainSrl = Context::get('email_domain_srl' );

		if( is_null( $nEmailDomainSrl ) )
		{
			// generate module model object
			$oSvPromotionAdminModel = getAdminModel('svpromotion');
			$nIdx = $oSvPromotionAdminModel->getAdminEmailDomainMaxIndex();

			$args->email_domain_srl = ++$nIdx;
			$output = executeQuery('svpromotion.insertEmailDomainGroupDiscount', $args );

			if(!$output->toBool())
				return $output;
		}
		else
		{
			$args->email_domain_srl = $nEmailDomainSrl;
			$output = executeQuery('svpromotion.updateEmailDomainGroupDiscount', $args );

			if(!$output->toBool())
				return $output;
		}
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvpromotionAdminEmailDomainGroupInsert', 'email_domain_srl', Context::get('email_domain_srl') ));
	}
/**
 * @brief insert coupon called by module
 **/
	public function procSvpromotionAdminInsertCoupon()
	{
		$nPromotionSrl = Context::get('promotion_srl' );
		if( strlen( $nPromotionSrl ) == 0 )
			return new BaseObject(-1, 'msg_invalid_promotion_srl' );

		$sMode = Context::get('mode' );
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		if( $sMode == 'bulk' )
		{
			$oSvPromotionAdminModel = getAdminModel('svpromotion');

			$output = $oSvPromotionAdminModel->getCouponPromotionSetupInfo( $nPromotionSrl );
			if( count( $output ) != 1 )
				return new BaseObject(-1, 'msg_invalid_promotion_srl' );
			
			if( $output->data[0]->promotion_srl <= 0 || $output->data[0]->promotion_srl != $nPromotionSrl )
				return new BaseObject(-1, 'msg_invalid_promotion_srl' );
			
			$nMaxUseCnt = 1;
			if( $output->data[0]->max_use_count > 1 )
				$nMaxUseCnt = $output->data[0]->max_use_count;

			if( count($output->data[0]->allowed_grp) > 0 )
			{
				if($output->data[0]->allowed_grp[0] != 'Y') // allowed_grp[0] is always guest
					return new BaseObject(-1, 'msg_guest_not_allowed' );
			}

			$args->promotion_srl = $nPromotionSrl;
			$args->max_use_count = $nMaxUseCnt;
			
			$sCouponList = Context::get('coupon_serial_list' );
			if( strlen( $sCouponList ) > 0 )
			{
				$aCouponList = preg_split('/\n|\r\n?/', $sCouponList);
				if( count( $aCouponList ) == 0 )
					return new BaseObject(-1, 'msg_invalid_coupon_list' );

				foreach( $aCouponList as $key => $val )
				{
					$val = str_replace(' ', '', $val);
					$args->coupon_serial = strip_tags( $val );
					$args->member_srl = 0;
					$output = executeQuery('svpromotion.insertCoupon', $args );
					if(!$output->toBool())
						return new BaseObject(-1, 'msg_invalid_coupon_serial' );
				}
			}
			else
			{
				$nCouponQty = Context::get('coupon_qty' );
				if( $nCouponQty == 0 )
					return new BaseObject(-1, 'msg_invalid_coupon_qty' );

				$nCouponLength = Context::get('coupon_length' );
				if( $nCouponLength < 4 )
					return new BaseObject(-1, 'msg_invalid_coupon_length' );
				
				$nSuccessCnt = 0;
				do 
				{
					$sSerial = $oSvpromotionAdminModel->getCouponSerial($nCouponLength);
					$args->coupon_serial = utf8_encode( $sSerial );
					$args->member_srl = 0;
					$output = executeQuery('svpromotion.insertCoupon', $args );
					if($output->toBool())
						$nSuccessCnt++;
				}
				while( $nSuccessCnt < $nCouponQty);
			}
		}
		else if( $sMode == 'endorsed_single' ) // 기명 쿠폰 1장 발행
		{
			// 쿠폰 리스트 설정
			$nCouponMemberSrl = (int)Context::get('coupon_member_srl' );
			if( $nCouponMemberSrl == 0 )
				return new BaseObject(-1, 'msg_invalid_member_srl' );

			$sCouponSerial = Context::get('new_coupon_serial' );
			if( strlen( $sCouponSerial ) > 0 )
			{
				$sSerial = str_replace(' ', '', $sCouponSerial);
				$sSerial = strip_tags($sSerial);
			}
			else
			{
				$nCouponLength = Context::get('coupon_length' );
				if( $nCouponLength < 4 )
					$nCouponLength = 6;
				$sSerial = $oSvpromotionAdminModel->getCouponSerial($nCouponLength);
			}
			$oRst = $this->insertCoupon($nPromotionSrl, $sSerial,$nCouponMemberSrl);
			if(!$oRst->toBool())
				return $oRst;
		}
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvpromotionAdminCouponInfo', 'promotion_srl', $nPromotionSrl ) );
	}
/**
 * @brief update group discount
 **/
	public function procSvpromotionAdminSiteGroupDiscount()
	{
		$oRst = executeQuery('svpromotion.deleteSiteGroupDiscount');
		if (!$oRst->toBool()) 
			return $oRst;
		unset($oRst);
		$oMemberModel = &getModel('member');
		$aGroupList = $oMemberModel->getGroups();
		unset($oMemberModel);
		foreach( $aGroupList as $nIdx=>$oGroup )
		{
			if (Context::get('group_discount_'.$oGroup->group_srl))
			{
				$opt = Context::get('group_opt_'.$oGroup->group_srl);
				if( !$opt ) 
					$opt = '1';
				$oArgs = new stdClass();
				$oArgs->group_srl = $oGroup->group_srl;
				$oArgs->opt = $opt;
				$oArgs->price = Context::get('group_discount_'.$oGroup->group_srl);
				$oRst = executeQuery('svpromotion.insertSiteGroupDiscount', $oArgs);
				if( !$oRst->toBool() )
					return $oRst;
				unset($oRst);
				unset($oArgs);
			}
		}
		unset($aGroupList);
		$this->setMessage( 'success_updated' );
		$sReturnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvpromotionAdminSitePromotion');
		$this->setRedirectUrl($sReturnUrl);
	}
/**
 * @brief 
 **/
	public function procSvpromotionAdminUpdateItemDetail()
	{
		$nItemSrl = Context::get('item_srl');
		$nModuleSrl = Context::get('module_srl');

		// 아이텐별 기본 할인 정책 갱신
		$sDefaultDiscountTitle = Context::get('default_discount_title');
		$nDefaultDiscountAmount = Context::get('default_item_discount_amount');
		$sAllowDuplication = Context::get('allow_duplication'); 
		$bAllowDuplication = $sAllowDuplication == 'allow' ? 1 : 0;
		$nDefaultDiscountOpt = Context::get('default_item_opt');

		$sBeginDate = Context::get('begin_date');
		$sEndDate = Context::get('end_date');
		if( $sBeginDate )
		{
			if( !$this->_validateDate( $sBeginDate ) )
				return new BaseObject(-1, 'msg_invalid_datetime1');
			else
				$args->begindate = $sBeginDate;
		}

		if( $sEndDate )
		{
			if( !$this->_validateDate( $sEndDate ) )
				return new BaseObject(-1, 'msg_invalid_datetime2');
			else
				$args->enddate = $sEndDate;
		}

		$args->item_srl = $nItemSrl;
		$args->module_srl = $nModuleSrl;
		$args->group_srl = '0';
		$args->allow_duplication = $bAllowDuplication;
		$args->opt = $nDefaultDiscountOpt;
		$args->price = $nDefaultDiscountAmount;
		$args->discount_info = $sDefaultDiscountTitle;
		$output = executeQuery('svpromotion.insertGroupByItemPolicy', $args );
		unset( $args->group_srl );
		unset( $args->opt );
		unset( $args->price );
		unset( $args->discount_info );

		// 아이텐별 기본 증정 정책 갱신
		$args->item_srl = $nItemSrl;
		$output = executeQuery('svpromotion.deleteGiveawayByItemPolicy', $args );
		if( !$output->toBool() )
			return $output;
		
		$sDefaultGiveawayTitle = Context::get('default_giveaway_title');
		$nDefaultGiveawayItemSrl = Context::get('default_item_giveaway_item_srl');
		$nDefaultGiveawayItemQty = Context::get('default_item_giveaway_item_qty');
		if( $sDefaultGiveawayTitle && $nDefaultGiveawayItemSrl && $nDefaultGiveawayItemQty )
		{
			$args->giveaway_info = $sDefaultGiveawayTitle;
			$args->giveaway_item_srl = $nDefaultGiveawayItemSrl;
			$args->giveaway_item_qty = $nDefaultGiveawayItemQty;
			$args->module_srl = $nModuleSrl;
			$output = executeQuery('svpromotion.insertGiveawayByItemPolicy', $args );
			if( !$output->toBool() )
				return $output;
		
			unset( $args->giveaway_info );
			unset( $args->giveaway_item_srl );
			unset( $args->giveaway_item_qty );
		}

		// 아이템별 FB 라이크 할인 정책 갱신
		$nFbLikeDiscountAmount = Context::get('fb_like_item_discount_amount');
		$nFbLikeDiscountOpt = Context::get('fb_like_item_opt');
		if( $nFbLikeDiscountOpt && $nFbLikeDiscountAmount )
		{
			$args->promotion_type = 'fblike';
			$output = executeQuery('svpromotion.deleteConditionalByItemPolicy', $args );
			if( !$output->toBool() )
				return $output;
			
			$args->opt = $nFbLikeDiscountOpt;
			$args->price = $nFbLikeDiscountAmount;
			$output = executeQuery('svpromotion.insertConditionalByItemPolicy', $args );
			if( !$output->toBool() )
				return $output;
			
			unset( $args->promotion_type );
			unset( $args->opt );
			unset( $args->price );
		}
		// 아이템별 FB 공유하기 할인 정책 갱신
		$nFbShareDiscountAmount = Context::get('fb_share_item_discount_amount');
		$nFbShareDiscountOpt = Context::get('fb_share_item_opt');
		if( $nFbShareDiscountOpt && $nFbShareDiscountAmount )
		{
			$args->promotion_type = 'fbshare';
			$output = executeQuery('svpromotion.deleteConditionalByItemPolicy', $args );
			if( !$output->toBool() )
				return $output;
			
			$args->opt = $nFbShareDiscountOpt;
			$args->price = $nFbShareDiscountAmount;
			$output = executeQuery('svpromotion.insertConditionalByItemPolicy', $args );
			if( !$output->toBool() )
				return $output;
		}
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvpromotionAdminItemDiscountDetail', 'item_srl',$nItemSrl ));
	}
/**
 * @brief ./tpl/coupon_update.html에서 HTTP POST 호출
 **/
	public function procSvpromotionAdminUpdateCoupon()
	{
		$nCouponBelognedMemberSrl = Context::get('coupon_belonged_member_srl');
		$nCouponMaxUsage = Context::get('coupon_max_usage');

		$nCouponSrl = Context::get('coupon_srl');
		$nPromotionSrl = Context::get('promotion_srl');
		$args->coupon_srl = $nCouponSrl;
		$args->max_use_count = $nCouponMaxUsage;
		$args->member_srl = $nCouponBelognedMemberSrl;
		$output = executeQuery('svpromotion.updateCouponInfoByCouponSrl', $args );
		if( !$output->toBool() )
			return $output;
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvpromotionAdminUpdateCoupon', 'promotion_srl',$nPromotionSrl,'coupon_srl',$nCouponSrl ));
	}
/**
 * @brief ./tpl/coupon_update.html에서 ajax 호출
 **/
	public function procSvpromotionAdminRefreshCoupon()
	{
		$nCouponSrl = Context::get('coupon_srl');
		$args->coupon_srl = $nCouponSrl;
		$output = executeQuery('svpromotion.updateCouponRegdateByCouponSrl', $args );
		if( !$output->toBool() )
			return $output;
		//$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvpromotionAdminUpdateCoupon', 'promotion_srl',$nPromotionSrl,'coupon_srl',$nCouponSrl ));
	}
/**
 * @brief 
 **/
	public function procSvpromotionAdminDeleteCoupon()
	{
		$nCouponSrl = Context::get('coupon_srl');
		$nPromotionSrl = Context::get('promotion_srl');
		$args->coupon_srl = $nCouponSrl;
		$output = executeQuery('svpromotion.deleteCouponByCouponSrl', $args );
		if( !$output->toBool() )
			return $output;
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvpromotionAdminCouponInfo', 'promotion_srl',$nPromotionSrl ));
	}
/**
 * @brief /svorder/ext_class/npay/npay_api.class.php::resetOrderInfo()에서 호출
 **/
	public function deletePromotionInfo($oArg)
	{
		if( $oArg->cart_srl )
			$oRst = executeQuery('svpromotion.deletePromotionCartByCartSrl', $oArg );
		elseif( $oArg->order_srl )
			$oRst = executeQuery('svpromotion.deletePromotionOrderByOrderSrl', $oArg );
		return $oRst;
	}
/**
 * @brief insert coupon
 * called by svcrm.controller.php::triggerInsertMemberAfter()
 **/
	public function insertCoupon($nPromotionSrl, $sCouponList, $nMemberSrl)
	{
		if( !$nPromotionSrl )
			return new BaseObject(-1, 'msg_invalid_promotion_srl' );

		if( strlen( $sCouponList)==0 )
			return new BaseObject(-1, 'msg_invalid_coupon_list' );

		if( !$nMemberSrl )
			return new BaseObject(-1, 'msg_invalid_member_srl' );

		// generate module model object
		$oSvPromotionAdminModel = getAdminModel('svpromotion');
		$output = $oSvPromotionAdminModel->getCouponPromotionSetupInfo( $nPromotionSrl );
		if( count( $output ) != 1 )
			return new BaseObject(-1, 'msg_invalid_promotion_srl' );
		
		if( $output->data[0]->promotion_srl <= 0 || $output->data[0]->promotion_srl != $nPromotionSrl )
			return new BaseObject(-1, 'msg_invalid_promotion_srl' );
		
		$nMaxUseCnt = 1;
		if( $output->data[0]->max_use_count > 1 )
			$nMaxUseCnt = $output->data[0]->max_use_count;

		$bPrivileged = true;
		if( count($output->data[0]->allowed_grp) > 0 )
		{
			$oMemberModel = getModel('member');
			$oMemberInfo = $oMemberModel->getMemberInfoByMemberSrl($nMemberSrl);
			$bPrivileged = false;
			foreach($oMemberInfo->group_list as $key=>$val)
			{
				if($output->data[0]->allowed_grp[$key] == 'Y')
				{
					$bPrivileged = true;
					break;
				}
			}
		}
		if( !$bPrivileged )
			return new BaseObject(-1, 'msg_not_privileged' );

		$aCouponList = preg_split('/\n|\r\n?/', $sCouponList);
		if( count( $aCouponList ) == 0 )
			return new BaseObject(-1, 'msg_invalid_coupon_list' );
		
		$args->promotion_srl = $nPromotionSrl;
		$args->max_use_count = $nMaxUseCnt;
		
		$nMemberSrl = (int)$nMemberSrl;
		if( is_null($nMemberSrl) )
			$nMemberSrl = 0;
		$args->member_srl = $nMemberSrl;
		//$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		//$nSrl = $oSvpromotionAdminModel->getNextCouponSrl();
		foreach( $aCouponList as $key => $val )
		{
			//$args->coupon_srl = $nSrl++;
			$args->coupon_serial = utf8_encode( $val );
			$output = executeQuery('svpromotion.insertCoupon', $args );
			if(!$output->toBool())
				return $output;
		}
		return new BaseObject();
	}
/**
 * @brief 
 **/
	private function _validateDate($sDate, $sFormat = 'YmdHis')
	{
		$d = DateTime::createFromFormat($sFormat, $sDate);
		// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
		return $d && $d->format($sFormat) === $sDate;
	}
/**
 * @brief arrange and save module config
 **/
	private function _saveModuleConfig($oArgs)
	{
		$oSvcrmAdminModel = &getAdminModel('svpromotion');
		$oConfig = $oSvcrmAdminModel->getModuleConfig();
		foreach( $oArgs as $key=>$val)
			$oConfig->{$key} = $val;

		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('svpromotion', $oConfig);
		return $output;
	}
/**
 * @brief 
 **/
	/*function procSvitemAdminMemberDiscount()
	{
		$oMemberMedel = &getModel('member');
		$vars = Context::getRequestVars();
		if(!$vars->member_id)
			return new BaseObject(-1,'아이디를 입력해주세요.');
		$member_srl = $oMemberMedel->getMemberSrlByUserID($vars->member_id);
		if(!$member_srl)
			return new BaseObject(-1,'존재하지 않는 ID입니다.');
		$args->member_srl = $member_srl;
		if(!$vars->discount)
		{
			// delete member_discount by no '$vars->discount'
			$output = executeQuery('svitem.deleteMemberDiscount', $args);
			if(!$output->toBool())
				return $output;
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvitemAdminMemberDiscount','module_srl',Context::get('module_srl')));
			return;
		}
		// check member_srl
		$output = executeQuery('svitem.getMemberDiscount', $args);
		if(!$output->toBool())
			return $output;
		if($output->data) // delete member_discount
			return new BaseObject(-1, '할인적용중인 ID입니다. 삭제후 등록 해주세요.');

		$args->discount = $vars->discount;
		$args->opt = $vars->member_opt;
		// insert member_discount
		$output = executeQuery('svitem.insertMemberDiscount', $args);
		if(!$output->toBool()) 
			return $output;

		//[member_id] => aaa
		//[member_opt] => 2
		//[member_discount] => bbb
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvitemAdminMemberDiscount','module_srl',Context::get('module_srl')));
	}*/
/**
 * @brief 
 **/
	/*function procSvitemAdminQuantityDiscount()
	{
		$oSvitemModel = &getModel('svitem');
		$vars = Context::getRequestVars();
		if(!$vars->item_code || !$vars->quantity || !$vars->discount)
			return new BaseObject(-1,'상품코드와 수량 그리고 할인가를 입력해주세요.');

		$item_info = $oSvitemModel->getItemByCode($vars->item_code);
		$item_srl = $item_info->item_srl;
		if(!$item_srl)
			return new BaseObject(-1, '상품이 없습니다.');
		$args->item_srl = $item_srl;
		// check 
		$output = executeQuery('svitem.getQuantityDiscount', $args);
		if(!$output->toBool())
			return $output;

		if($output->data) // delete 
			return new BaseObject(-1, '할인적용중인 상품입니다. 삭제후 등록 해주세요.');

		$args->quantity = $vars->quantity;
		$args->discount = $vars->discount;
		$args->opt = $vars->quantity_opt;
		// insert member_discount
		$output = executeQuery('svitem.insertQuantityDiscount', $args);

		if(!$output->toBool())
			return $output;
		//[member_id] => aaa
		//[member_opt] => 2
		//[member_discount] => bbb
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvitemAdminQuantityDiscount','module_srl',Context::get('module_srl')));
	}*/
/**
 * @brief 
 **/
	/*function procSvitemAdminDeleteMemberDiscount()
	{
		$vars = Context::getRequestVars();
		if(!$vars->member_srls)
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvitemAdminMemberDiscount','module_srl',Context::get('module_srl'))); 
			return;
		}
		foreach($vars->member_srls as $k => $v)
		{
			$args->member_srl = $v;
			$output = executeQuery('svitem.deleteMemberDiscount', $args);
			if(!$output->toBool())
				return $output;
		}
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvitemAdminMemberDiscount','module_srl',Context::get('module_srl'))); 
	}*/
/**
 * @brief 
 **/
	/*function procSvitemAdminDeleteQuantityDiscount()
	{
		$vars = Context::getRequestVars();
		if(!$vars->item_srls)
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvitemAdminQuantityDiscount','module_srl',Context::get('module_srl')));
			return;
		}
		foreach($vars->item_srls as $k => $v)
		{
			$args->item_srl = $v;
			$output = executeQuery('svitem.deleteQuantityDiscount', $args);
			if(!$output->toBool())
				return $output;
		}
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispSvitemAdminQuantityDiscount','module_srl',Context::get('module_srl')));
	}*/
}