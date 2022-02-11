<?php
/**
 * @class  svpromotionUnit
 * @author singleview(root@singleview.co.kr)
 * @brief  standardize unit of svpromotion to evaluate buyers' benefit
 */
class svpromotionUnit
{
	private $_aPrmotion = Array();
	private $_nPromotionIdx = 0;
	private $_nItemNormaPrice = 0;
	private $_fItemMaxDiscountRate = 0;
	private $_nItemMinPrice = 0;
/**
 * @brief 
 **/
	public function __construct( $nItemPrice, $fMaxDiscRate = 0 )
	{
		if( $nItemPrice > 0 )
			$this->_nItemNormaPrice = $nItemPrice;

		if( $fMaxDiscRate > 0 && $fMaxDiscRate < 1 )
			$this->_fItemMaxDiscountRate = $fMaxDiscRate;
		else if( $fMaxDiscRate > 1 && $fMaxDiscRate < 100 )
			$this->_fItemMaxDiscountRate = $fMaxDiscRate/100;
		else
			$this->_fItemMaxDiscountRate = 0.5;

		$this->_nItemMinPrice = $nItemPrice*(1-$this->_fItemMaxDiscountRate);
	}
/**
 * @brief 
 **/
	public function add( $oPromotion )
	{
		// 판매가보다 적은 할인 정책만 수용
		if( $oPromotion->discount_amount > 0 && $oPromotion->discounted_price > 0 )
		{
			$oTmpPrmotion = new BaseObject();
			$oTmpPrmotion->promotion_type = $oPromotion->promotion_type;//'normal';
			$oTmpPrmotion->discount_amount = $oPromotion->discount_amount;
			$oTmpPrmotion->discounted_price = $oPromotion->discounted_price;
			$oTmpPrmotion->discount_info = $oPromotion->discount_info;
			$oTmpPrmotion->allow_duplication = $oPromotion->allow_duplication;
			
			$this->_aPrmotion[$this->_nPromotionIdx++] = $oTmpPrmotion;
			unset( $oTmpPrmotion );
		}
	}
/**
 * @brief 
 **/
	public function getMaxDiscount()
	{
		$nMaxDiscAmnt = 0;
		$nChoosedIdx = -1;
		foreach( $this->_aPrmotion as $key => $val )
		{
			if( $val->discount_amount > $nMaxDiscAmnt )
			{
				$nMaxDiscAmnt = $val->discount_amount;
				$nChoosedIdx = $key;
			}
		}
		if( $nChoosedIdx == -1 )
		{
			$oNoDiscountOutput = new BaseObject();
			$oNoDiscountOutput->discount_amount = 0;
			$oNoDiscountOutput->discounted_price = $this->_nItemNormaPrice;
			return $oNoDiscountOutput;
		}
		else
		{
			// 최대 할인액 초과 여부 점검
			if( $this->_aPrmotion[$nChoosedIdx]->discounted_price < $this->_nItemMinPrice )
			{
				$this->_aPrmotion[$nChoosedIdx]->discounted_price = $this->_nItemMinPrice;
				$this->_aPrmotion[$nChoosedIdx]->discount_amount = $this->_nItemNormaPrice - $this->_nItemMinPrice;
				$this->_aPrmotion[$nChoosedIdx]->discount_info = sprintf( '최대 %d%% 할인 혜택', $this->_fItemMaxDiscountRate*100);
			}
			return $this->_aPrmotion[$nChoosedIdx];
		}
	}
}