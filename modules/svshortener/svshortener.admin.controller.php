<?php
/**
 * @class  svshortenerAdminController
 * @author singleview(root@singleview.co.kr)
 * @brief  svshortenerAdminController
**/ 

class svshortenerAdminController extends svshortener
{
	/**
	 * @brief initialization
	 **/
	function init() 
	{
	}
/**
 * @brief Save page edits
 */
	public function procSvshortenerAdminInsert()
	{
		$oArgs = Context::getRequestVars();
		$oArgs->utm_source_name = trim( preg_replace('/\s+/', '', $oArgs->utm_source_name) );
		$oArgs->utm_medium_name = trim( preg_replace('/\s+/', '', $oArgs->utm_medium_name) );
		$oArgs->utm_service_name = trim( preg_replace('/\s+/', '', $oArgs->utm_service_name) );

		if( strlen( $oArgs->utm_source_name ) == 0 )
			return new BaseObject(-1, 'msg_error_no_utm_source_name');
		if( strlen( $oArgs->utm_medium_name ) == 0 )
			return new BaseObject(-1, 'msg_error_no_utm_medium_name');
		if( strlen( $oArgs->utm_service_name ) == 0 )
			return new BaseObject(-1, 'msg_error_no_utm_service_name');
		
		switch( $oArgs->utm_service_name )
		{
			case 'blog':
				$sServiceType = 'b';
				break;
			case 'cafe':
				$sServiceType = 'c';
				break;
			case 'kin':
				$sServiceType = 'k';
				break;
			case 'post':
				$sServiceType = 'p';
				break;
			case 'rel':
				$sServiceType = 'r';
				break;
			default:
				return new BaseObject(-1, 'msg_error_invalid_utm_service_name');
		}

		$aAllocationDateInfo = ['start_date'=>'start_date', 'end_date'=>'end_date'];
		foreach( $aAllocationDateInfo as $sInargTitle => $sSqlVarTitle )
		{
			$oRst = $this->_getExpenseAllocationDateType($oArgs->{$sInargTitle});
			if(!$oRst->toBool())
				return $oRst;
			
			$sFormatType = $oRst->get('sFormatType');
			if( $sFormatType == 'yyyymmdd' )
				$bValid = $this->_validateDateActual($oArgs->{$sInargTitle});
			elseif( $sFormatType == 'increment' )
			{
				$oArgs->{$sInargTitle} = str_replace ("d", " days", $oArgs->{$sInargTitle});
				$oArgs->{$sInargTitle} = str_replace ("w", " week", $oArgs->{$sInargTitle});

				if( $sInargTitle == 'start_date' )
					$timestamp = strtotime($oArgs->{$sInargTitle});
				elseif( $sInargTitle == 'end_date' )
					$timestamp = strtotime($oArgs->start_date.' '.$oArgs->{$sInargTitle});

				$oTempArg->{$sInargTitle} = date("Ymd", $timestamp);
				$bValid = $this->_validateDateActual($oTempArg->{$sInargTitle});
				if(!$bValid)
					return new BaseObject(-1, 'msg_error_invalid_date_info');
				else
					$oArgs->{$sInargTitle} = $oTempArg->{$sInargTitle};
			}
		}

		//$sCurDate = date("Ymd");
		if( (int)date("Ymd") > (int)$oArgs->start_date )
			return new BaseObject(-1, 'msg_error_invalid_date_info');
		if( (int)$oArgs->start_date > (int)$oArgs->end_date )
			return new BaseObject(-1, 'msg_error_invalid_date_info');

		$oSvshortenerAdminModel = getAdminModel('svshortener');
		$aLine = explode("\n", str_replace("\r", "", $oArgs->term_and_blogger_id));
		foreach( $aLine as $nIdx => $sVal )
		{
			$sVal = str_replace("\t", ";", $sVal);
			$aVal = explode(';', $sVal );
			$oArgs->utm_term =  str_replace(' ', '', $aVal[0]);
			$oArgs->utm_term = trim( preg_replace('/\s+/', '', $oArgs->utm_term) );
			$oArgs->blogger_id =  str_replace(' ', '', $aVal[1]);
			$oArgs->blogger_id = trim( preg_replace('/\s+/', '', $oArgs->blogger_id) );
			
			if( strlen( $oArgs->utm_term ) == 0 )
				return new BaseObject(-1, 'msg_error_no_utm_term');
			
			$nIdx = $oSvshortenerAdminModel->getMaxIndex();
			$oArgs->shorten_uri_value = $sServiceType.++$nIdx;

			if( $oSvshortenerAdminModel->isExistingUriValue( $oArgs->shorten_uri_value ) )
				return new BaseObject(-1, 'msg_error_existing_shorten_uri_value');

			$output = executeQuery('svshortener.insertSvshortenerTrackingUrl', $oArgs );
			if( !$output->toBool() )
				return new BaseObject(-1, 'msg_error_svshortener_db_query');
		}

		$this->add("page", Context::get('page'));
		$this->setMessage($msg_code);
		$returnUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSvshortenerAdminIndex');
		$this->setRedirectUrl($returnUrl);
	}
/**
 * @brief 비용 배분 날짜 형식 판단
 */
	private function _getExpenseAllocationDateType($sDate)
	{
		$aDateFormat = ['yyyymmdd'=>'/^(19|20)\d{2}(0[1-9]|1[012])(0[1-9]|[12][0-9]|3[0-1])$/', 'increment'=>'/^[+][^0]?[1-9]+[0-9]*[dw]$/'];
		foreach( $aDateFormat as $sFormatType => $sRegex )
		{
			preg_match($sRegex, $sDate, $aMatches, PREG_OFFSET_CAPTURE, 0);
			if( array_key_exists( 0, $aMatches) )
				break;
			$aMatches = null;
		}

		if(!$aMatches)
			return new BaseObject(-1, 'msg_error_invalid_date_info');
		else
		{
			$oRst = new BaseObject();
			$oRst->add('sFormatType',$sFormatType);
			return $oRst;
		}
	}
/**
 * @brief yyyymmdd가 실제 날짜인지 검사
 */	
	private function _validateDateActual($date, $format = 'Ymd')
	{
		$d = DateTime::createFromFormat($format, $date);
		// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
		return $d && $d->format($format) === $date;
	}
}