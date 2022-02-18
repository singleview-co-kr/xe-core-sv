<?php
/**
 * @class  svpromotionAdminView
 * @author singleview(root@singleview.co.kr)
 * @brief  svpromotionAdminView
 */
class svpromotionAdminView extends svpromotion 
{
/**
 * @brief initialization
 **/
	function init()
	{
		// module이 svshopmaster일때 관리자 레이아웃으로
		if(Context::get('module') == 'svshopmaster')
		{
			$sClassPath = _XE_PATH_ . 'modules/svshopmaster/svshopmaster.class.php';
			if(file_exists($sClassPath))
			{
				require_once($sClassPath);
				$oSvshopmaster = new svshopmaster;
				$oSvshopmaster->init($this);
			}
		}

		// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
		$module_srl = Context::get('module_srl');
		if( !$module_srl && $this->module_srl )
		{
			$module_srl = $this->module_srl;
			Context::set( 'module_srl', $module_srl );
		}

		$oModuleModel = &getModel('module');

		// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
		if( $module_srl ) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl( $module_srl );
			if( !$module_info )
			{
				Context::set('module_srl','');
				$this->act = 'list';
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}
		if($module_info && !in_array($module_info->module, array('svpromotion')))
			return $this->stop("msg_invalid_request");

		//if(Context::get('module')=='svshopmaster')
		//{
		//	$this->setLayoutPath('');
		//	$this->setLayoutFile('common_layout');
		//}
		
		// set template file
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);
		$this->setTemplateFile('index');
		Context::set('tpl_path', $tpl_path);
	}
/**
 * @brief display svpromotion list
 **/
	function dispSvpromotionAdminIndex() 
	{
		$oSvpromotionModel = &getModel('svpromotion');
		$output = $oSvpromotionModel->getCouponPromotionList();
		// use context::set to setup variables on the templates
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('promotion_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		$this->setTemplateFile('index');
	}
/**
 * @brief display coupon promotion performance detail
 **/
	function dispSvpromotionAdminCouponPerformance() 
	{
		$nPromotionSrl = Context::get('promotion_srl');
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$oCouponInfo = $oSvpromotionAdminModel->getCouponPromotionSetupInfo( $nPromotionSrl );
		Context::set( 'coupon_promotion_title', $oCouponInfo->data[0]->promotion_title );

		$nCurrentPage = Context::get('page');

		$output = $oSvpromotionAdminModel->getCouponPromotionUsageLog( $nPromotionSrl, $nCurrentPage );
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set( 'performance_list', $output->data );
		$this->setTemplateFile('coupon_performance');
	}
/**
 * @brief display the basic configuration form
 **/
	function dispSvpromotionAdminConfig()
	{
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$oConfig = $oSvpromotionAdminModel->getModuleConfig();
		Context::set('config',$oConfig);

		$oSvorderAdminModel = &getAdminModel('svorder');
		$aSvorderPage = $oSvorderAdminModel->getModInstList();
		Context::set('svorder_page', $aSvorderPage);

		$this->setTemplateFile('config');
	}
/**
 * @brief display the basic configuration form
 **/
	function dispSvpromotionAdminReserves()
	{
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$config = $oSvpromotionAdminModel->getModuleConfig();
		Context::set('config',$config);
		$this->setTemplateFile('reserves_mgmt');
	}
/**
 * @brief display the selected promotion admin information
 **/
	function dispSvpromotionAdminCouponPromotionInfo() 
	{
		$this->_dispSvpromotionAdminInsertCouponPromotion();
	}
/**
 * @brief display the promotion insert form
 **/
	function _dispSvpromotionAdminInsertCouponPromotion()
	{
		$nPromotionSrl = Context::get('promotion_srl');
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$output = $oSvpromotionAdminModel->getCouponPromotionSetupInfo( $nPromotionSrl );
		Context::set( 'promotion_detail', $output->data[0] );

		// get groups
		$oMemberModel = &getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list', $group_list);

		// bulk discount begin
		unset( $output );
		$oSvitemAdminModel = &getAdminModel('svitem');
		$oArgs->page = Context::get('page');
		$output = $oSvitemAdminModel->getSvitemAdminItemList($oArgs);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('item_list', $output->data );
		// bulk discount end
		$this->setTemplateFile('coupon_promotion_insert');
	}
/**
 * @brief display coupon update screen
 **/
	function dispSvpromotionAdminUpdateCoupon() 
	{
		$nCouponSrl = Context::get('coupon_srl');
		Context::set('coupon_srl', $nCouponSrl);
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$aInfo = $oSvpromotionAdminModel->getCouponInfo( $nCouponSrl );
		Context::set( 'member_srl', $aInfo->member_srl );
		Context::set( 'coupon_serial', $aInfo->coupon_serial );
		Context::set( 'max_use_count', $aInfo->max_use_count );
		Context::set( 'used_count', $aInfo->used_count );
		$this->setTemplateFile('coupon_update');
	}
/**
 * @brief display coupon delete screen
 **/
	function dispSvpromotionAdminDeleteCoupon() 
	{
		$nCouponSrl = Context::get('coupon_srl');
		Context::set('coupon_srl', $nCouponSrl);
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$aInfo = $oSvpromotionAdminModel->getCouponInfo( $nCouponSrl );
		Context::set( 'coupon_serial', $aInfo->coupon_serial );
		Context::set( 'max_use_count', $aInfo->max_use_count );
		Context::set( 'used_count', $aInfo->used_count );
		$this->setTemplateFile('coupon_delete');
	}
/**
 * @brief display the promotion insert form
 **/
	function dispSvpromotionAdminCouponInfo()
	{
		$nPromotionSrl = Context::get('promotion_srl');
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$output = $oSvpromotionAdminModel->getCouponListByCouponPromotion( $nPromotionSrl );
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set( 'coupon_list', $output->data );
		$this->setTemplateFile('coupon_list_by_promotion');
	}
/**
 * @brief 사이트 단위의 프로모션 정책 설정
 **/
	function dispSvpromotionAdminSitePromotion()
	{
		// get groups
		$oMemberModel = &getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list', $group_list);
		
		// start to get site member group discount info 
		$output = executeQueryArray( 'svpromotion.getSiteGroupDiscount' );
		if(!$output->toBool()) 
			return $output;

		$output_data = $output->data;
		$group_discount = array();
		if( $output_data )
		{
			foreach( $output_data as $key=>$val )
				$group_discount[$val->group_srl] = $val;
		}
		Context::set('group_discount', $group_discount);
		// end to get site member group discount info 

		unset( $output );
		unset( $group_discount );
		// start to get email domain group of member discount info
		$output = executeQueryArray( 'svpromotion.getEmailDomainGroupDiscount' );
		if(!$output->toBool()) 
			return $output;
		$output_data = $output->data;
		foreach( $output_data as $key=>$val )
		{
			if( $val->opt == 1 )
				$output_data[$key]->discount_polocy = number_format($output_data[$key]->price,0).'원 할인';
			else if( $val->opt == 2 )
				$output_data[$key]->discount_polocy = $output_data[$key]->price.'% 할인';
		}
		Context::set('email_domain_group_discount_list', $output->data);
		// end to get email domain group of member discount info
		// bulk discount begin
		unset( $output );
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$config = $oSvpromotionAdminModel->getModuleConfig();
		Context::set('config',$config);
		
		$oSvitemAdminModel = &getAdminModel('svitem');
		$oArgs = new stdClass();
		$oArgs->page = Context::get('page');
		$output = $oSvitemAdminModel->getSvitemAdminItemList($oArgs);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('item_list', $output->data );
		// bulk discount end
		$this->setTemplateFile('site_promotion');
	}
/**
 * @brief dispSvpromotionAdminSitePromotion() 화면에서 호출
 **/
	function dispSvpromotionAdminEmailDomainGroupInsert()
	{
		$nEmailDomainSrl = Context::get('email_domain_srl' );
		$oSvpromotionAdminModel = &getAdminModel('svpromotion');
		$output = $oSvpromotionAdminModel->getEmailDomainDiscountInfo( $nEmailDomainSrl );
		Context::set( 'promotion_detail', $output );
		$this->setTemplateFile('email_domain_group_insert');
	}
/**
 * @brief 
 **/	
	function dispSvpromotionAdminItemDiscountList()
	{
		$oSvitemAdminModel = &getAdminModel('svitem');
		$oArgs = new stdClass();
		$oArgs->page = Context::get('page');
		$output = $oSvitemAdminModel->getSvitemAdminItemList($oArgs);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('item_list', $output->data );
		$this->setTemplateFile('item_list_discount');
	}
/**
 * @brief 
 **/	
	function dispSvpromotionAdminItemListPopup()
	{
		// change into popup layout
		$this->setLayoutFile( 'popup_layout.html' );

		$oSvitemAdminModel = &getAdminModel('svitem');
		$oArgs->page = Context::get('page');
		$output = $oSvitemAdminModel->getSvitemAdminItemList($oArgs);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('item_list', $output->data );
		$this->setTemplateFile('item_list_popup');
	}
/**
 * @brief 
 **/
	function dispSvpromotionAdminItemDiscountDetail()
	{
		$oSvitemModel = &getModel('svitem');
		$item_srl = Context::get('item_srl');
		$item_info = $oSvitemModel->getItemInfoByItemSrl($item_srl);
		Context::set('module_srl', $item_info->module_srl );
		Context::set('item_name', $item_info->item_name );
		Context::set('thumb_file_srl', $item_info->thumb_file_srl );

		$oSvpromotionModel = &getModel('svpromotion');
		$oCouponRst = $oSvpromotionModel->getCouponPromotionList();
		$aEffectiveCouponList = [];
		foreach( $oCouponRst->data as $nIdx => $oVal )
		{
			if($oVal->target_items[$item_srl] == 'Y')
			{
				if($oVal->descount_type == 'rate')
					$aEffectiveCouponList[$oVal->promotion_srl]->sPolicy = $oVal->descount_rate_policy.'% 할인';
				elseif($oVal->descount_type == 'amount')
					$aEffectiveCouponList[$oVal->promotion_srl]->sPolicy = $oVal->descount_amount_policy.'원 할인';
				$aEffectiveCouponList[$oVal->promotion_srl]->sTitle = $oVal->promotion_title;
			}
		}
		Context::set('aEffectiveCouponList', $aEffectiveCouponList);

		$oSvitemAdminModel = &getAdminModel('svitem');
		$list = $oSvitemAdminModel->getModInstList();

		foreach( $list as $key => $val )
		{
			if( $val->module_srl == $item_info->module_srl )
			{
				Context::set('module_info', $val );
				break;
			}
		}
		
		// get groups
		$oMemberModel = &getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list', $group_list);
		
		// 아이템별 기본 할인 정책 가져오기 시작
		$args->item_srl = $item_srl;
		$output = executeQueryArray('svpromotion.getGroupDiscountByItem', $args );
		if(!$output->toBool()) 
			return $output;

		reset( $output->data );
		$nFirstIdx = key( $output->data );
		$oDiscountInfo = $output->data[$nFirstIdx];
		if( $oDiscountInfo->group_srl == 0 )
		{
			$item_discount_info->discount_title = $oDiscountInfo->discount_info;
			$item_discount_info->price = $oDiscountInfo->price;
			$item_discount_info->opt = $oDiscountInfo->opt;
			$item_discount_info->allow_duplication = $oDiscountInfo->allow_duplication;
			$item_discount_info->begindate = $oDiscountInfo->begindate;
			$item_discount_info->enddate = $oDiscountInfo->enddate;
		}
		/*foreach( $oDiscountInfo as $key => $val )
		{
			if( $val->group_srl == 0 )
			{
				$item_discount_info->discount_title = $val->discount_info;
				$item_discount_info->price = $val->price;
				$item_discount_info->opt = $val->opt;
				$item_discount_info->allow_duplication = $val->allow_duplication;
				$item_discount_info->begindate = $val->begindate;
				$item_discount_info->enddate = $val->enddate;
			}
		}*/

		Context::set('item_discount_info', $item_discount_info);
		unset( $item_discount_info );
		// 아이템별 기본 할인 정책 가져오기 끝

		// 아이템별 FB like 할인 정책 가져오기 시작
		$args->item_srl = $item_srl;
		$args->promotion_type = 'fblike';
		$output = executeQueryArray('svpromotion.getConditionalDiscountByItem', $args );
		if(!$output->toBool()) 
			return $output;

		foreach( $output->data as $key => $val )
		{
			$item_fblike_discount_info->price = $val->price;
			$item_fblike_discount_info->opt = $val->opt;
		}
		Context::set('item_fb_like_discount_info', $item_fblike_discount_info);
		// 아이템별 FB Like 할인 정책 가져오기 끝
		// 아이템별 FB share 할인 정책 가져오기 시작
		$args->item_srl = $item_srl;
		$args->promotion_type = 'fbshare';
		$output = executeQueryArray('svpromotion.getConditionalDiscountByItem', $args );
		if(!$output->toBool()) 
			return $output;

		foreach( $output->data as $key => $val )
		{
			$item_fbshare_discount_info->price = $val->price;
			$item_fbshare_discount_info->opt = $val->opt;
		}
		Context::set('item_fb_share_discount_info', $item_fbshare_discount_info);
		// 아이템별 FB share 할인 정책 가져오기 끝

		// 아이템별 무조건 증정 정책 가져오기 시작
		$args->item_srl = $item_srl;
		$args->module_srl = $item_info->module_srl;
		$output = executeQueryArray('svpromotion.getGiveawayPromotionByItem', $args );
		if(!$output->toBool()) 
			return $output;
		
		foreach( $output->data as $key => $val )
		{
			$item_giveaway_info->giveaway_title = $val->giveaway_info;
			$item_giveaway_info->giveaway_item_srl = $val->giveaway_item_srl;
			$item_giveaway_info->giveaway_item_qty = $val->giveaway_quantity;
			$oGiveawayItemInfo = $oSvitemModel->getItemInfoByItemSrl($val->giveaway_item_srl);
			$item_giveaway_info->giveaway_item_title = $oGiveawayItemInfo->item_name;
		}
		Context::set('item_giveaway_info', $item_giveaway_info);
		// 아이템별 무조건 증정 정책 가져오기 끝

		unset( $args );
		$output_data = $output->data;
		$group_discount = array();
		if( $output_data )
		{
			foreach( $output_data as $key=>$val )
				$group_discount[$val->group_srl] = $val;
		}
		Context::set('group_discount', $group_discount);
		$this->setTemplateFile('item_detail_discount');
	}
}