<?php
/**
* @brief session 모듈에 의존성
* @author aztr0v0iz
* @developer w9721066@gmail.com
*/
class SvTracker
{
	var $_g_aQuery = null;
	var $_g_sErrMsg = '';
	var $_g_oShrtenInfo = array();
/**
 * @brief 
 **/
	public function SvTracker()
	{
		$this->_g_aQuery = explode( '&', $_SERVER['QUERY_STRING'] );
		$this->_setHttpReferer();
		return TRUE;
	}
/**
 * @brief 
 **/
	public function getShortenTracker()
	{
		return $this->_getShortner();
	}
/**
 * @brief 
 **/
	public function setShortenInfo( $sShortnerQueryName, $sShortnerInfo )
	{
		$this->_setShortenInfo( $sShortnerQueryName, $sShortnerInfo );
	}
/**
 * @brief 
 **/
	public function setNormalTracker()
	{
		$this->_setSessionSource( $this->_getSource() );
		$this->_setSessionMedium( $this->_getMedium() );
		$this->_setSessionCampaign( $this->_getCampaign() );
		$this->_setSessionKeyword( $this->_getKeyword() );
	}
/**
 * @brief 
 **/
	public function getDebugMsg()
	{
		if( strlen( $this->_g_sErrMsg ) > 0 )
			return $this->_g_sErrMsg;
		else
			return 'tracker addon status: OK';
	}
/**
 * @brief 
 **/
	public function getCampaignInfo()
	{
		return $_SESSION['HTTP_INIT_CAMPAIGN'];
	}
/**
 * @brief 
 **/
	public function getShortenerInfo( $sShortnerQueryName )
	{
		if( strlen( $sShortnerQueryName ) == 0 )
			return false;

		foreach( $this->_g_aQuery as $key => $val )
		{
			if( preg_match( "/\b$sShortnerQueryName\b/i", $val ) )
				$aValue = explode( '=', $val );
		}

		if( $aValue === NULL )  // utm_param 이 있는지 검사
		{
			$this->setNormalTracker();
			return false;
		}
		else
		{
			if(getClass('svshortener'))
			{
				$oSvshortenerModel = getModel('svshortener');
				$aShortenerInfo = $oSvshortenerModel->getShortenerInfo( $aValue[1] );
			}
			if( !$aShortenerInfo )
				return false;

			$oSvshortenerController = getController('svshortener');
			$oSvshortenerController->increaseCounter( $aValue[1] );
			$this->_g_oShrtenInfo['source'] = $aShortenerInfo[1];
			$this->_g_oShrtenInfo['medium'] = $aShortenerInfo[2];
			$this->_g_oShrtenInfo['campaign'] = $aShortenerInfo[3];
			$this->_g_oShrtenInfo['keyword'] = $aShortenerInfo[4];
			$this->_setSessionSource( $this->_g_oShrtenInfo['source'] );
			$this->_setSessionMedium( $this->_g_oShrtenInfo['medium'] );
			$this->_setSessionCampaign( $this->_g_oShrtenInfo['campaign'] );
			$this->_setSessionKeyword( $this->_g_oShrtenInfo['keyword'] );
			return $this->_g_oShrtenInfo;
		}
	}
/**
 * @brief compatible with goole analytics tracking code
 * config set: nov_k4;naver;organic;NV_NS_KIN_20151015;방석
 * 패턴에서 \b는 단어를 지시합니다. 단어 "web"만 매치하고,
 * "webbing"이나 "cobweb" 등의 부분적인 경우에는 매치하지 않습니다.
 *  "i"는 대소문자를 구별하지 않게 합니다.
 **/
	private function _setShortenInfo( $sShortnerQueryName, $sShortnerInfo )
	{
		if( strlen( $sShortnerInfo ) == 0 )
			return false;
		
		$aInfo = preg_split ("/\r\n|\n|\r/", $sShortnerInfo);
		if( count( $aInfo ) == 0 )
			return false;
		
		foreach( $this->_g_aQuery as $key => $val )
		{
			if( preg_match( "/\b$sShortnerQueryName\b/i", $val ) )
				$aValue = explode( '=', $val );
		}
		foreach( $aInfo as $key => $val )
		{
			$aTemp = explode( ';', $val );
			if( count( $aTemp ) == 5 )
			{
				if( $aTemp[0] == $aValue[1] )
				{
					$this->_g_oShrtenInfo['source'] = $aTemp[1];
					$this->_g_oShrtenInfo['medium'] = $aTemp[2];
					$this->_g_oShrtenInfo['campaign'] = $aTemp[3];
					$this->_g_oShrtenInfo['keyword'] = $aTemp[4];
					break;
				}
			}
		}
		$this->_setSessionSource( $this->_g_oShrtenInfo['source'] );
		$this->_setSessionMedium( $this->_g_oShrtenInfo['medium'] );
		$this->_setSessionCampaign( $this->_g_oShrtenInfo['campaign'] );
		$this->_setSessionKeyword( $this->_g_oShrtenInfo['keyword'] );
	}
/**
 * @brief 
 **/
	private function _getShortner()
	{
		if( isset( $this->_g_oShrtenInfo['source'] ) )
			return $this->_g_oShrtenInfo;
		else
			return false;		
	}
/**
 * @brief LP에 최종 진입한 source를 session에 기록
 **/
	private function _setSessionSource( $sSource )
	{
		if( strlen( $sSource ) > 0 )
		{
			$sTmp = urldecode( trim( $sSource ) ); 
			$_SESSION['HTTP_INIT_SOURCE'] = $sTmp;
		}
	}
/**
 * @brief LP에 최종 진입한 medium를 session에 기록
 **/
	private function _setSessionMedium( $sMedium )
	{
		if( strlen( $sMedium ) > 0 )
		{
			$sTmp = urldecode( trim( $sMedium ) ); 
			$_SESSION['HTTP_INIT_MEDIUM'] = $sTmp;
		}
	}
/**
 * @brief LP에 최종 진입한 campaign를 session에 기록
 **/
	private function _setSessionCampaign( $sCampaign )
	{
		if( strlen( $sCampaign ) > 0 )
		{
			$sTmp = urldecode( trim( $sCampaign ) ); 
			$_SESSION['HTTP_INIT_CAMPAIGN'] = $sTmp;
		}
	}
/**
 * @brief LP에 최초 진입한 keyword를 session에 기록
 **/
	private function _setSessionKeyword( $sKeyword )
	{
		//if( is_null( $_SESSION['HTTP_INIT_KEYWORD'] ) && strlen( $sKeyword ) > 0 )
		if( strlen( $sKeyword ) > 0 )
			$_SESSION['HTTP_INIT_KEYWORD'] = $sKeyword;
	}
/**
 * @brief compatible with goole analytics tracking code
 **/
	private function _getSource()
	{
		foreach( $this->_g_aQuery as $key => $val )
		{	
			if( preg_match( "/\butm_source\b/i", $val ) ) // prioritize google analytics tracking code
			{
				$aValue = explode( '=', $val );
				return urldecode( $aValue[1] );
			}
		}
		return '';
	}
/**
 * @brief compatible with goole analytics tracking code
 **/
	private function _getMedium()
	{
		foreach( $this->_g_aQuery as $key => $val )
		{	
			if( preg_match( "/\butm_medium\b/i", $val ) ) // prioritize google analytics tracking code
			{
				$aValue = explode( '=', $val );
				return urldecode( $aValue[1] );
			}
		}
		return '';
	}
/**
 * @brief compatible with goole analytics tracking code
 **/
	private function _getCampaign()
	{
		foreach( $this->_g_aQuery as $key => $val )
		{	
			if( preg_match( "/\butm_campaign\b/i", $val ) ) // prioritize google analytics tracking code
			{
				$aValue = explode( '=', $val );
				return urldecode( $aValue[1] );
			}
		}
		return '';
	}
/**
 * @brief 
 **/
	private function _setHttpReferer()
	{
		// LP에 최초 진입한 HTTP_REFERER을 session에 기록함
		if( is_null( $_SESSION['HTTP_INIT_REFERER'] ) )
		{
			if( strlen( $_SERVER['HTTP_REFERER'] ) > 0 )
				$_SESSION['HTTP_INIT_REFERER'] = $_SERVER['HTTP_REFERER'];
			else
			{
				$_SESSION['HTTP_INIT_REFERER'] = '';
				$this->_g_sErrMsg .= 'no HTTP_REFERER header  ';
			}
		}
	}
/**
 * @brief compatible with goole analytics tracking code
 **/
	private function _getKeyword()
	{
		foreach( $this->_g_aQuery as $key => $val )
		{	
			if( preg_match( "/\butm_term\b/i", $val ) ) // prioritize google analytics tracking code
			{
				$aValue = explode( '=', $val );
				return urldecode( $aValue[1] );
			}
			else if( preg_match( "/\bNVADKWD\b/i", $val ) ) // Naver PS
			{
				$aValue = explode( '=', $val );
				return urldecode( $aValue[1] );
			}
			else if( preg_match( "/\bDMKW\b/i", $val ) ) // Daum clix
			{
				$aValue = explode( '=', $val );
				return urldecode( $aValue[1] );
			}
		}
		$this->_g_sErrMsg .= 'no URI utm_term, NVADKWD nor DMKW  ';
		return '';
	}
}