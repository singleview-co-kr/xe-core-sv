<?php
/**
 * @class  svshortenerModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svshortenerModel
**/ 
class svshortenerModel extends module
{
	var $_g_aSourceAbbreviation = array( 'naver' => 'NV', 'daumkakao' => 'DAUM', 'google' => 'GG', 'facebook' => 'FB', 'instagram' => 'IN' );
	var $_g_aMediumAbbreviation = array( 'organic' => 'PNS', 'sns' => 'SNS' );//, 'display' => 'PS', 'cpc' => 'PS' );
	var $_g_aServiceAbbreviation = array( 'blog' => 'BL', 'cafe' => 'CF', 'kin' => 'KIN', 'post' => 'PO' );
/**
 * @brief initialization
 **/
	function init()
	{
	}
/**
 *
 **/
	public function getShortenerInfo( $sQueryValue )
	{
		$args = new stdClass();
		$args->shorten_uri_value = $sQueryValue;
		$output = executeQuery('svshortener.getSvshortenersUriInfo', $args );

		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svshortener_db_query');
		if( count( $output->data ) == 0 )  // 새로운 uri value이면
			return false;
		elseif( $output->data->utm_service_name == 'rel' ) // 연관검색어 유형이면 svtracker addon에서 gatk js script 출력 거부
			return false;
		else
		{
			$this->setBloggerType();
			$aTemp = Array();
			$sRegdate = zdate( $output->data->regdate,'Ymd' );
			$aTemp[1] = $output->data->utm_source_name;
			$aTemp[2] = $output->data->utm_medium_name;
			// campaign code 작성
			$aTemp[3] = $this->_g_aSourceAbbreviation[$output->data->utm_source_name].'_'.$this->_g_aMediumAbbreviation[$output->data->utm_medium_name].'_REF_'.$this->_g_aServiceAbbreviation[$output->data->utm_service_name].'_'.$sRegdate;
			$sUtmTerm = $this->generateUtmTerm($output->data->utm_term, $output->data->blogger_type,$output->data->blogger_id);
			$aTemp[4] = $sUtmTerm.'_'.$sRegdate;
			return $aTemp;
		}
	}
/**
 *
 **/
	public function setBloggerType()
	{
		require_once(_XE_PATH_.'modules/svshortener/blogger_type.php');
		$this->aBloggerType = $aBloggerType;
	}
/**
 *
 **/
	public function generateUtmTerm( $sUtmTerm, $sBloggerType, $sBloggerId )
	{
		$sFinalUtmTerm = $sUtmTerm.'_'.$this->aBloggerType[$sBloggerType];
		if( $sBloggerId )
			$sFinalUtmTerm .= '_'.$sBloggerId;
		return $sFinalUtmTerm;
	}
}