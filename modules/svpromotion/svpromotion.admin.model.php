<?php
/**
 * @class  svpromotionAdminModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svpromotionAdminModel
 */
class svpromotionAdminModel extends svpromotion
{
/**
 * Initialization
 * @return void
 */
	public function init()
	{
	}
/**
 * @brief 
 **/
	public function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('svpromotion');
		if(is_null($config))
			$config = new stdClass();
		if(!$config->fb_app_id)
			$config->fb_app_id = '';
		return $config;
	}
/**
 * @brief 
 **/
	public function getCouponPromotionUsageLog( $nPromotionSrl, $nCurrentPage )
	{
		if( !$nPromotionSrl )
			return new BaseObject(-1, 'msg_invalid_coupon_promotion_srl' );

		$args->promotion_srl = $nPromotionSrl;
		$args->page = $nCurrentPage;
		$output = executeQueryArray('svpromotion.getCouponPerformance', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_db_query');
		
		$oMemberModel = &getModel('member');
		$oSvorderAdminModel = &getAdminModel('svorder');
		$bIncludingApi = false;
		$oOrder = $oSvorderAdminModel->getSvOrderClass($bIncludingApi);
		foreach( $output->data as $key=>$val)
		{
			if( $val->member_srl > 0 )
			{
				$oMemberInfo = $oMemberModel->getMemberInfoByMemberSrl($val->member_srl);
				$output->data[$key]->nick_name = $oMemberInfo->nick_name;
			}
			else
				$output->data[$key]->nick_name = 'guest';

			$oLoadRst = $oOrder->loadSvOrder($val->order_srl);
			if (!$oLoadRst->toBool()) 
				return $oLoadRst;
			unset( $oLoadRst );
			$oOrderHeader = $oOrder->getHeader();
			$output->data[$key]->sum_price = $oOrderHeader->sum_price;
			$output->data[$key]->total_discount_amount = $oOrderHeader->total_discount_amount;
			$output->data[$key]->total_discounted_price = $oOrderHeader->sum_price - $oOrderHeader->total_discount_amount;
			$output->data[$key]->title =  $oOrderHeader->title;
			unset( $oMemberInfo );
			unset( $oOrderHeader );
		}

		return $output;
	}
/**
 * @brief 
 **/
	public function getCouponPromotionSetupInfo( $nPromotionSrl )
	{
		if( !$nPromotionSrl )
			return new BaseObject(-1, 'msg_invalid_coupon_promotion_srl' );

		$args->promotion_srl = $nPromotionSrl;
		$output = executeQueryArray('svpromotion.getCouponPromotionDetail', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_db_query');
		else
		{
			$output->data[0]->allowed_grp = unserialize($output->data[0]->allowed_grp);
			$output->data[0]->target_items = unserialize($output->data[0]->target_items);
			return $output;
		}
	}
/**
 * @brief 
 **/
	public function getCouponListByCouponPromotion( $nPromotionSrl )
	{
		if( !$nPromotionSrl )
			return new BaseObject(-1, 'msg_invalid_coupon_promotion_srl' );
		
		$args->page = Context::get('page');
		$args->promotion_srl = $nPromotionSrl;
		$output = executeQueryArray('svpromotion.getCouponList', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_coupon_db_query');
		else
		{
			$oMemberModel = &getModel('member');
			foreach( $output->data as $key=>$val)
			{
				if( $val->member_srl > 0 )
				{
					$oMemberInfo = $oMemberModel->getMemberInfoByMemberSrl($val->member_srl);
					if( is_null( $oMemberInfo ) )
						$output->data[$key]->nick_name = 'quit';
					else
						$output->data[$key]->nick_name = $oMemberInfo->nick_name;
				}
			}
			return $output;
		}
	}
/**
 * @brief 
 **/
	public function getCouponInfo($nCouponSrl)
	{
		$args->coupon_srl = $nCouponSrl;
		$output = executeQuery('svpromotion.getCouponInfo', $args );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromtion_coupon_db_query');
		else
			return $output->data;
	}
/**
 * @brief 
 **/
	public function getAdminEmailDomainMaxIndex()
	{
		$output = executeQueryArray('svpromotion.getEmailDomainMaxIndex' );

		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromotion_email_domain_group_db_query');

		if( count( $output->data ) == 0 )  // 최초 입력시 ++인덱스가 0이 되도록
			return -1;

		foreach( $output->data as $key => $val )
			return $val->email_domain_srl;
	}
/**
 * @brief 
 **/
	public function getEmailDomainDiscountInfo( $nDomainSrl )
	{
		$args->email_domain_srl = $nDomainSrl;
		$output = executeQuery('svpromotion.getEmailDomainDetail', $args );

		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svpromotion_email_domain_group_db_query');
		
		if( count( $output->data ) == 0 )  
			return new BaseObject(-1);

		return $output->data;
	}
/**
 * @brief generate coupon random number
 * @usage getCouponSerial(9);
 * called by svcrm.controller.php::triggerInsertMemberAfter()
 **/
	public function getCouponSerial( $nSize )
	{
		/*$alpha_key = '';
		$keys = range('A', 'Z');

		for( $i = 0; $i < 2; $i++ )
			$alpha_key .= $keys[array_rand($keys)];

		$length = $nSize - 2;
		$key = '';
		$keys = range(0, 9);

		for ($i = 0; $i < $length; $i++) 
			$key .= $keys[array_rand($keys)];

		return $alpha_key.$key;*/
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $nSize; $i++) 
			$randomString .= $characters[rand(0, $charactersLength - 1)];

		return $randomString;
	}
/**
 * @brief 
 **/
	/*public function getCouponPromotionList()
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
///////////////////////
				if( $output->data[$key]->allowed_grp )
					$output->data[$key]->allowed_grp = unserialize($output->data[$key]->allowed_grp);
				else
					$output->data[$key]->allowed_grp = null;

				if( $output->data[$key]->target_items )
					$output->data[$key]->target_items = unserialize($output->data[$key]->target_items);
				else
					$output->data[$key]->target_items = null;
////////////////////////
				if( is_numeric( $val->end_date ) )
					$output->data[$key]->end_date = zdate($output->data[$key]->end_date);
			}
			return $output;
		}
	}*/
}
/* End of file svpromotion.admin.model.php */
/* Location: ./modules/svpromotion/svpromotion.admin.model.php */