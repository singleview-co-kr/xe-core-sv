<?php
/**
 * @class  svncpCloudSearch
 * @author singleview(root@singleview.co.kr)
 * @brief  svncpCloudSearch class
 */
class svNcpCloudSearch extends ncpCloudSearch
{
/**
 * @brief 검색 결과 캐쉬 가져오기
 **/
	protected function _getNcpSearchCacheAbs($sNcpRst=null)
	{
		$sCacheByDatePath = _XE_PATH_.'files/cache/integration_search/ncp_cloud_search/'.date('Ymd');
		if(!is_readable($sCacheByDatePath))
			FileHandler::makeDir($sCacheByDatePath);
		$sCacheByHash = md5($this->_g_sIdxKey.'_'.$this->_g_sQuery.'_'.$this->_g_nStartPos.'_'.$this->_g_nDisplayCnt);
		// $sCacheByHourHash = $sHash.'_'.date('hi');
		$oFinalRst = new stdClass();
		$oFinalRst->bCacheFound = false;
		$oFinalRst->oNcpReturn = null;
		$sFullCachePath = $sCacheByDatePath.'/'.$sCacheByHash;
		if(is_readable($sFullCachePath))
		{
			// echo __FILE__.':'.__LINE__.'<BR>';
			$oFinalRst->bCacheFound = true;
			$oFinalRst->oNcpReturn = FileHandler::readFile($sFullCachePath);
			//if( (int)$date < (int)$this->_g_sToday - 7 ) // 7일 전 까지 실시간 계산
		}
		elseif($sNcpRst)
			FileHandler::writeFile($sFullCachePath, $sNcpRst);
		return $oFinalRst;
	}
/**
 * @brief 검색 결과 캐쉬 지우기
 **/
	protected function _removeNcpSearchCacheAbs() 
	{
	}
/**
 * @brief 검색 결과 재구성 abstract
 **/
	protected function _constructNcpSearchRstAbs($sNcpSearchRst)
	{
		$oNcpRst = json_decode($sNcpSearchRst);
		$oRst = new stdClass();
		// $oRst->sVersion = $oNcpRst->version;  // string(5) "3.2.0"
		// $oRst->nStatus = $oNcpRst->status; // int(200)
		// $oRst->sType = $oNcpRst->type; // string(8) "response"
		// $oRst->sTimeZone = $oNcpRst->type; // string(6) "+09:00"
		// $oRst->fElapsedTime = $oNcpRst->elapsed_time; // float(0.003)
		// $oRst->oTerm = $oNcpRst->term;
		// ["term"]=> object(stdClass)#5 (1) 
		// { 
		// 	["idx_title_content"]=> object(stdClass)#4 (1) 
		// 	{ 
		// 		["main"]=> object(stdClass)#3 (2) 
		// 		{	["term_count"]=> int(2) 
		// 			["term_list"]=> array(2) 
		// 			{ 
		// 				[0]=> string(6) "락스" [1]=> string(6) "냄새" 
		// 			} 
		// 		} 
		// 	} 
		// }
		$oRst->sTerm = $this->_g_sQuery;
		$oRst->nStartPos = $oNcpRst->result->start; // int(1)
		$oRst->nDisplay = $oNcpRst->result->display; // int(5)
		// $oRst->sRanking = $oNcpRst->result->ranking; // string(5) "clous"
		// $oRst->sSortBy = $oNcpRst->result->sort_by; // string(3) "qds"
		$oRst->nTotalCount = $oNcpRst->result->total_count; // int(1070)
		// $oRst->nRemovedCount = $oNcpRst->result->removed_count; // int(0)
		// $oRst->nMissedCount = $oNcpRst->result->missed_count; // int(0)
		$oRst->nItemCount = $oNcpRst->result->item_count; // int(5)
		$oRst->bLast = $oNcpRst->result->is_last; // bool(false)
		$oRst->aItems = [];  // $oNcpRst->result->items; // array(5)
		foreach($oNcpRst->result->items as $nIdx=>$oVal)
			$oRst->aItems[] = (int)$oVal->document_srl;
		// [0]=> object(stdClass)#7 (10)
		// { 
		// 	["_rank"]=> int(1)
		// 	["_key"]=> string(6) "100538"
		// 	["_qds"]=> int(2)
		// 	["_quality"]=> int(1)
		// 	["_relevance"]=> float(1.5)
		// 	["_similarity"]=> int(2)
		// 	["content"]=> string(1091) "2ㅡ3주전부터 <br />"
		// 	["document_srl"]=> string(6) "100538"
		// 	["title"]=> string(65) "수영장냄새 벌레들이 싫어하나요"
		// }
		// echo __FILE__.':'.__LINE__.'<BR>';
		// var_dump($oRst->aItems);
		unset($oNcpRst);
		return $oRst;
	}
}

class ncpCloudSearch
{
	protected $_g_sIdxKey = null;
	protected $_g_sQuery = null;
	protected $_g_nStartPos = 1;
	protected $_g_nDisplayCnt = 10;
	private $_g_sApiServer = 'https://cloudsearch.apigw.ntruss.com';
	private $_g_sDomainNameTag = '[%domain_name%]';
	private $_g_sApiUrl = null;
	private $_g_sOperationTarget = null;
	private $_g_sSpace = ' ';
	private $_g_sNewLine = "\n";
	private $_g_sApiCallMethod = 'POST';
	private $_g_bPostMethod = true;
	private $_g_sUnixtimestamp = null;
	private $_g_sNcpAccessKey = null;
	private $_g_sNcpSecretKey = null;
/**
 * @brief 생성자
 **/
	public function __construct()
	{
		// [출처] [이렇게 사용하세요!] Cloud Search 활용하여 검색환경 손쉽게 구현하기|작성자 NAVER Cloud Platform
		// https://blog.naver.com/n_cloudplatform/222189638931
		// https://docs.3rdeyesys.com/api/ncloud_api_call_php_sample.html
		// https://cloud.skill.or.kr/72
		// https://github.com/NaverCloudPlatform/cloudsearch-sample/
		// 기본 데이터 설정
		$this->_g_sUnixtimestamp = round(microtime(true) * 1000);
		$this->_g_sApiUrl = '/CloudSearch/real/v1/domain/'.$this->_g_sDomainNameTag.'/document';
	}

	public function setUserConfig($aConfig)
	{
		$aMandatory = ['ncp_access_key', 'ncp_secret_key', 'idx_title'];
		foreach($aMandatory as $sKey)
		{
			if(!array_key_exists($sKey, $aConfig))
			{
				echo $sKey." not exists in config info<BR>";
				return;
			}
		}
		$this->_g_sNcpAccessKey = $aConfig['ncp_access_key'];
		$this->_g_sNcpSecretKey = $aConfig['ncp_secret_key'];
		$this->_g_sIdxKey = $aConfig['idx_title'];
		if(array_key_exists('display_cnt', $aConfig))
			$this->_g_nDisplayCnt = $aConfig['display_cnt'];
	}

	public function setStartPosition($nPos)
	{
		if($nPos > 1)
			$this->_g_nStartPos = $nPos;
	}

	public function setQuery($sTerm)
	{
		if(!strlen($sTerm))
		{
			echo "invalid search term<BR>";
			return;
		}
		$this->_g_sQuery = trim($sTerm);
	}

	public function setHttpMethod($sMethod)
	{
		$sMethod = strtoupper($sMethod);
		$aValidMethod = ['POST', 'GET'];

		if(in_array($sMethod, $aValidMethod))
			$this->_g_sApiCallMethod = $sMethod;
		else
			$this->_g_sApiCallMethod = 'POST';

		if($this->_g_sApiCallMethod == 'GET')
			$this->_g_bPostMethod = false;
		else
			$this->_g_bPostMethod = true;
	}

	public function setDomain($sDomainName)
	{
		if(!strlen($sDomainName))
		{
			echo "invalid domain name<BR>";
			return;
		}
		$this->_g_sApiUrl = str_replace($this->_g_sDomainNameTag, $sDomainName, $this->_g_sApiUrl);
	}

	private function _requestHttp($sJsonQueryDSL)
	{
		if(!strlen($sJsonQueryDSL) || !strlen($this->_g_sOperationTarget))
			return false;

		if(!$this->_validateConfiguration())
			return false;

		// http 호출 헤더값 설정
		$aHttpHeader = [];
		$aHttpHeader[] = "x-ncp-apigw-timestamp:".$this->_g_sUnixtimestamp;
		$aHttpHeader[] = "x-ncp-iam-access-key:".$this->_g_sNcpAccessKey;
		$aHttpHeader[] = "x-ncp-apigw-signature-v2:".$this->_makeSignature();
		$aHttpHeader[] = "Content-Type: application/json";
		echo __FILE__.':'.__LINE__.'<BR>';
		var_dump($this->_g_sApiServer.$this->_g_sApiUrl.$this->_g_sOperationTarget);
		// api 호출
		$oCh = curl_init();
		curl_setopt($oCh, CURLOPT_URL, $this->_g_sApiServer.$this->_g_sApiUrl.$this->_g_sOperationTarget);
		curl_setopt($oCh, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($oCh, CURLOPT_RETURNTRANSFER, TRUE);	
		curl_setopt($oCh, CURLOPT_POST, $this->_g_bPostMethod);
		curl_setopt($oCh, CURLOPT_HTTPHEADER, $aHttpHeader);
		// https://zzdd1558.tistory.com/294   Request Body 방식의 검색질의
		curl_setopt($oCh, CURLOPT_POSTFIELDS, $sJsonQueryDSL);
		$sResponse = curl_exec($oCh);
		curl_close($oCh);
		unset($oCh);
		unset($aHttpHeader);
		// {"message":"there is no such service : search_yuhanclorox"
		echo __FILE__.':'.__LINE__.'<BR>';
		var_dump($sResponse);
		exit;
		return $sResponse;
	} 

	public function upsertDoc($oReqParam)
	{
		$this->_g_sOperationTarget = '/manage/db_upload';
		// https://api.ncloud-docs.com/docs/analytics-cloudsearch-postdbupload
		$sJsonQueryDSL = '{
			"dbKind": "mysql",
			"host": "'.$oReqParam->oSourceDbInfo->sHostname.'",
			"port": '.$oReqParam->oSourceDbInfo->sPort.',
			"user": "'.$oReqParam->oSourceDbInfo->sUserid.'",
			"password": "'.$oReqParam->oSourceDbInfo->sPassword.'",
			"db": "'.$oReqParam->oSourceDbInfo->sDatabase.'",
			"charset": "utf8",
			"keyField": "'.$oReqParam->oSqlInfo->sKeyField.'",
			"sql": "'.$oReqParam->oSqlInfo->sSqlStmt.'",
			"connectTimeout": 4,
		}';
		// "indexTypeField": "index_type",  // optional field
		var_dump($sJsonQueryDSL);
		$sResponse = $this->_requestHttp($sJsonQueryDSL);
		if($sResponse)
		{
			;
		}
	}

	public function getSearchList()
	{
		$this->_g_sOperationTarget = '/search';

		// if(!$this->_validateConfiguration())
		// 	return false;

		$oRst = $this->_getNcpSearchCacheAbs();
		if($oRst->bCacheFound)
			$oNcpRst = $this->_constructNcpSearchRstAbs($oRst->oNcpReturn);
		else
		{
			// echo __FILE__.':'.__LINE__.'<BR>';
			// return;
			$sJsonQueryDSL = '{
				"start": '.$this->_g_nStartPos.',
				"display": '.$this->_g_nDisplayCnt.',
				"search": {
					"'.$this->_g_sIdxKey.'": {
						"main": {
						"query": "'.$this->_g_sQuery.'"
						}
					}
				}
			}';
			// var_dump($sJsonQueryDSL);
			// exit;
			// http 호출 헤더값 설정
			// $aHttpHeader = [];
			// $aHttpHeader[] = "x-ncp-apigw-timestamp:".$this->_g_sUnixtimestamp;
			// $aHttpHeader[] = "x-ncp-iam-access-key:".$this->_g_sNcpAccessKey;
			// $aHttpHeader[] = "x-ncp-apigw-signature-v2:".$this->_makeSignature();
			// $aHttpHeader[] = "Content-Type: application/json";
			// // api 호출
			// $oCh = curl_init();
			// curl_setopt($oCh, CURLOPT_URL, $this->_g_sApiServer.$this->_g_sApiUrl);
			// curl_setopt($oCh, CURLOPT_SSL_VERIFYPEER, FALSE);
			// curl_setopt($oCh, CURLOPT_RETURNTRANSFER, TRUE);	
			// curl_setopt($oCh, CURLOPT_POST, $this->_g_bPostMethod);
			// curl_setopt($oCh, CURLOPT_HTTPHEADER, $aHttpHeader);
			// // https://zzdd1558.tistory.com/294   Request Body 방식의 검색질의
			// curl_setopt($oCh, CURLOPT_POSTFIELDS, $sJsonQueryDSL);
			// $sResponse = curl_exec($oCh);
			// curl_close($oCh);
			// unset($oCh);
			// unset($aHttpHeader);
			// echo __FILE__.':'.__LINE__.'<BR>';
			$sResponse = $this->_requestHttp($sJsonQueryDSL);
			if($sResponse)
			{
				$this->_setNcpSearchCacheAbs($sResponse);
				$oNcpRst = $this->_constructNcpSearchRstAbs($sResponse);
			}
		}
		unset($oRst);
		return $oNcpRst;
	}

	// https://guide.ncloud-docs.com/docs/apigw-myproducts#%EC%97%90%EB%9F%AC-%EC%BD%94%EB%93%9C
	private function _makeSignature()
	{
		// hmac으로 암호화할 문자열 설정
		$sAuthMsg = 
			$this->_g_sApiCallMethod
			.$this->_g_sSpace
			.$this->_g_sApiUrl.$this->_g_sOperationTarget
			.$this->_g_sNewLine
			.$this->_g_sUnixtimestamp
			.$this->_g_sNewLine
			.$this->_g_sNcpAccessKey;	
		// hmac_sha256 암호화
		$sSignatureMsg = hash_hmac('sha256', $sAuthMsg, $this->_g_sNcpSecretKey, true);
		return base64_encode($sSignatureMsg);
	}

	private function _validateConfiguration()
	{
		if(strpos($this->_g_sApiUrl, $this->_g_sDomainNameTag))
		{
			echo "invalid domain name<BR>";
			return false;
		}
		$aNotNull = ['_g_sNcpAccessKey', '_g_sNcpSecretKey', '_g_sIdxKey']; //, '_g_sQuery'];
		foreach($aNotNull as $sLbl)
		{
			if(!$this->{$sLbl})
			{
				echo $sLbl." must be filled<BR>";
				return false;
			}
		}
		return true;
	}
/**
 * @brief 검색 결과 캐쉬 가져오기 abstract
 **/
	protected function _setNcpSearchCacheAbs($sResponse)
	{
		$this->_getNcpSearchCacheAbs($sResponse);
	}
/**
 * @brief 검색 결과 캐쉬 기록하기
 **/
	protected function _getNcpSearchCache()
	{
	}
/**
 * @brief 검색 결과 캐쉬 지우기 abstract
 **/
	protected function _removeNcpSearchCacheAbs() 
	{
	}
/**
 * @brief 검색 결과 재구성 abstract
 **/	
	protected function _constructNcpSearchRstAbs($sNcpSearchRst)
	{
	}
}

// $oSvNcpCloudSearch = new svNcpCloudSearch();
// $oSvNcpCloudSearch->setUserConfig(['ncp_access_key' => 'tpsmT8q1EZiFje1Jb8qb',
// 								 'ncp_secret_key' => 'UtxreayWlZnbRtQZ6dWgBi0ZgTYPjL2iQAwSGCG2',
// 								 'idx_title' => 'idx_title_content',
// 								 'display_cnt' => 5]);
// //$oSvNcpCloudSearch->setHttpMethod('post');
// $oSvNcpCloudSearch->setDomain('search_yuhanclorox');
// $oSvNcpCloudSearch->setStartPosition(1);
// $oSvNcpCloudSearch->setQuery('락스 냄새');
// $oResponse = $oSvNcpCloudSearch->getSearchListXml();
// unset($oSvNcpCloudSearch);

/* End of file svncp.cloud_search.php */
/* Location: ./modules/integration_search/svncp.cloud_search.php */