<?php
/**
 * @class  svncpCloudSearch
 * @author singleview(root@singleview.co.kr)
 * @brief  svncpCloudSearch class
 */

/**
 * @brief ncpCloudSearch의 자식 클래스
 * 추상 메소드 구현
 **/
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

/**
 * @brief NCP cloud search API
 **/
class ncpCloudSearch
{
	protected $_g_sIdxKey = null;
	protected $_g_sQuery = null;
	protected $_g_nStartPos = 1;
	protected $_g_nDisplayCnt = 10;
	private $_g_sApiServer = 'https://cloudsearch.apigw.ntruss.com';
	private $_g_sDomainName = null;
	private $_g_sApiRootUrl = null;
	private $_g_sApiUrlFull = null;
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
		$this->_g_sApiRootUrl = '/CloudSearch/real/v1'; // /domain/'.$this->_g_sDomainNameTag.'/document';
	}
/**
 * @brief API 설정값 입력
 **/
	public function setUserConfig($aConfig)
	{
		$aMandatory = ['ncp_access_key', 'ncp_secret_key']; //, 'idx_title'];
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
		if(array_key_exists('idx_title', $aConfig))
			$this->_g_sIdxKey = $aConfig['idx_title'];
		if(array_key_exists('display_cnt', $aConfig))
			$this->_g_nDisplayCnt = $aConfig['display_cnt'];
	}
/**
 * @brief table position 설정
 **/
	public function setStartPosition($nPos)
	{
		if($nPos > 1)
			$this->_g_nStartPos = $nPos;
	}
/**
 * @brief 검색 키워드 설정
 **/
	public function setQuery($sTerm)
	{
		if(!strlen($sTerm))
		{
			echo "invalid search term<BR>";
			return;
		}
		$this->_g_sQuery = trim($sTerm);
	}
/**
 * @brief 검색 키워드 설정
 **/
	protected function _setHttpMethod($sMethod)
	{
		$sMethod = strtoupper($sMethod);
		$aValidMethod = ['POST', 'GET', 'DELETE', 'PATCH'];

		if(in_array($sMethod, $aValidMethod))
			$this->_g_sApiCallMethod = $sMethod;
		else
			$this->_g_sApiCallMethod = 'POST';

		if($this->_g_sApiCallMethod == 'POST')
			$this->_g_bPostMethod = true;
		else
			$this->_g_bPostMethod = false;
	}
/**
 * @brief 클라우드 서치의 도메인명 입력
 **/
	public function setDomain($sDomainName)
	{
		$sDomainName = trim($sDomainName);
		if(!strlen($sDomainName))
		{
			echo "invalid domain name<BR>";
			return;
		}
		$this->_g_sDomainName = $sDomainName;
	}
/**
 * @brief NCP API 통신을 위한 암호문 생성
 * ref: https://guide.ncloud-docs.com/docs/apigw-myproducts#%EC%97%90%EB%9F%AC-%EC%BD%94%EB%93%9C
 **/
	private function _makeSignature()
	{
		// hmac으로 암호화할 문자열 설정
		$sAuthMsg = 
			$this->_g_sApiCallMethod
			.$this->_g_sSpace
			.$this->_g_sApiUrlFull
			.$this->_g_sNewLine
			.$this->_g_sUnixtimestamp
			.$this->_g_sNewLine
			.$this->_g_sNcpAccessKey;	
		// hmac_sha256 암호화
		$sSignatureMsg = hash_hmac('sha256', $sAuthMsg, $this->_g_sNcpSecretKey, true);
		return base64_encode($sSignatureMsg);
	}
/**
 * @brief API 통신
 **/
	private function _requestHttp($sJsonQueryDSL=null)
	{
		if(!strlen($this->_g_sApiUrlFull))
			return false;
		if($this->_g_bPostMethod && !strlen($sJsonQueryDSL))
		{
			echo __FILE__.':'.__LINE__.'<BR>';
			return false;
		}
		$aNotNull = ['_g_sNcpAccessKey', '_g_sNcpSecretKey'];
		foreach($aNotNull as $sLbl)
		{
			if(!$this->{$sLbl})
			{
				echo $sLbl." must be filled<BR>";
				return false;
			}
		}

		// http 호출 헤더값 설정
		$aHttpHeader = [];
		$aHttpHeader[] = "x-ncp-apigw-timestamp: ".$this->_g_sUnixtimestamp;
		$aHttpHeader[] = "x-ncp-iam-access-key: ".$this->_g_sNcpAccessKey;
		$aHttpHeader[] = "x-ncp-apigw-signature-v2: ".$this->_makeSignature();
		$aHttpHeader[] = "Content-Type: application/json";
		//echo __FILE__.':'.__LINE__.'<BR>';
		//var_dump($this->_g_sApiUrlFull);
		//echo '<BR>';
		//var_dump($this->_g_bPostMethod);
		//echo '<BR>';
		// api 호출
		$oCh = curl_init();
		// curl_setopt($oCh, CURLOPT_URL, $this->_g_sApiServer.$this->_g_sApiRootUrl.$this->_g_sOperationTarget);
		curl_setopt($oCh, CURLOPT_URL,  $this->_g_sApiServer.$this->_g_sApiUrlFull);
		curl_setopt($oCh, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($oCh, CURLOPT_RETURNTRANSFER, TRUE);	
		curl_setopt($oCh, CURLOPT_HTTPHEADER, $aHttpHeader);
		// https://zzdd1558.tistory.com/294   Request Body 방식의 검색질의
		curl_setopt($oCh, CURLOPT_POST, $this->_g_bPostMethod);
		if($this->_g_bPostMethod)
		{
			curl_setopt($oCh, CURLOPT_POSTFIELDS, $sJsonQueryDSL);
			//echo __FILE__.':'.__LINE__.'<BR>';
			//var_dump($sJsonQueryDSL);
			//echo '<BR>';
		}
		$sResponse = curl_exec($oCh);
		curl_close($oCh);
		unset($oCh);
		unset($aHttpHeader);
		// {"message":"there is no such service : search_yuhanclorox"
		
		//echo __FILE__.':'.__LINE__.'<BR>';
		//var_dump($sResponse);
		//exit;
		return $sResponse;
	} 
/**
 * @brief QueryDSL로 개별 문서 업로드
 * plain text로만 구성된 문서 업로드 용
 * ref: https://api.ncloud-docs.com/docs/analytics-cloudsearch-managedocument
 **/
	public function upsertDoc($oReqParam)
	{
		$oNcpRst = new stdClass();
		$oNcpRst->result = null;
		if(!$this->_g_sDomainName)
		{
			$oNcpRst->result = 'domain name not defined';
			return $oNcpRst;
		}
		
		$aRemoveMark = ['\'', '"', PHP_EOL];
		$this->_g_sApiUrlFull = $this->_g_sApiRootUrl.'/domain/'.$this->_g_sDomainName.'/document/manage';
		$sJsonQueryDSL = '{
		  "requests": [{
			  "type": "upsert",
			  "content": {
				"document_srl": '.$oReqParam->nDocSrl.',
				"title": "'.str_replace($aRemoveMark, '', strip_tags($oReqParam->sTitle)).'",
				"content": "'.str_replace($aRemoveMark, '', strip_tags($oReqParam->sContent)).'",
				"tag": "'.str_replace($aRemoveMark, '', strip_tags($oReqParam->sTags)).'"
			  }
		   }]
		}';
		unset($aRemoveMark);
		$sResponse = $this->_requestHttp($sJsonQueryDSL);
		if($sResponse)
			$oNcpRst = json_decode($sResponse);
		else
			$oNcpRst->result = 'bad';
		return $oNcpRst;
	}
/**
 * @brief QueryDSL로 개별 문서 삭제
 * ref: https://api.ncloud-docs.com/docs/analytics-cloudsearch-managedocument
 **/
	public function deleteDoc($nDocSrl)
	{
		$oNcpRst = new stdClass();
		$oNcpRst->result = null;
		if(!$this->_g_sDomainName)
		{
			$oNcpRst->result = 'domain name not defined';
			return $oNcpRst;
		}
		
		$this->_g_sApiUrlFull = $this->_g_sApiRootUrl.'/domain/'.$this->_g_sDomainName.'/document/manage';
		$sJsonQueryDSL = '{
		   "requests" : [
			  {
				 "type" : "delete",
				 "key" : "'.$nDocSrl.'"
			  }
		  ]
		}';
		$sResponse = $this->_requestHttp($sJsonQueryDSL);
		if($sResponse)
			$oNcpRst = json_decode($sResponse);
		else
			$oNcpRst->result = 'bad';
		return $oNcpRst;
	}
/**
 * @brief cloud search API가 문서 DB 서버에 직접 접속해서 추출 문서 업로드
 * ref: https://api.ncloud-docs.com/docs/analytics-cloudsearch-postdbupload
 **/	
	public function uploadDb($oReqParam)
	{
		$oNcpRst = new stdClass();
		$oNcpRst->result = null;
		if(!$this->_g_sDomainName)
		{
			$oNcpRst->result = 'domain name not defined';
			return $oNcpRst;
		}
		$this->_setHttpMethod('post');
		$this->_g_sApiUrlFull = $this->_g_sApiRootUrl.'/domain/'.$this->_g_sDomainName.'/document/manage/db_upload';
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
			"connectTimeout": 4
		}';
		// "indexTypeField": "[필드명]",  // optional, upsert if null
		// "connectTimeout": 4 뒤에 , 붙이면 "{"failedValidation":true, 반환
		//var_dump($sJsonQueryDSL);
		$sResponse = $this->_requestHttp($sJsonQueryDSL);
		if($sResponse)
			$oNcpRst = json_decode($sResponse);
		else
			$oNcpRst->result = 'bad';
		return $oNcpRst;
	}
/**
 * @brief cloud search API가 문서 DB 서버에 접속 테스트
 * ref: https://api.ncloud-docs.com/docs/analytics-cloudsearch-postdbuploadcheckconnection
 **/
	public function checkDbConnection($oReqParam)
	{
		$this->_setHttpMethod('post');
		$this->_g_sApiUrlFull = $this->_g_sApiRootUrl.'/db_upload/check_connection';
		$sJsonQueryDSL = '{
			"dbKind": "mysql",
			"host": "'.$oReqParam->oSourceDbInfo->sHostname.'",
			"port": '.$oReqParam->oSourceDbInfo->sPort.',
			"user": "'.$oReqParam->oSourceDbInfo->sUserid.'",
			"password": "'.$oReqParam->oSourceDbInfo->sPassword.'",
			"db": "'.$oReqParam->oSourceDbInfo->sDatabase.'",
			"charset": "utf8",
			"connectTimeout": 4
		}';
		$sResponse = $this->_requestHttp($sJsonQueryDSL);
		var_dump($sResponse);
		if($sResponse)
		{
			;
		}
	}
/**
 * @brief cloud search API가 문서 DB 서버에 직접 접속해서 추출 문서 업로드한 내역 가져오기
 * ref: 
 **/
	public function inquiryDbUploadList()
	{
		$oNcpRst = new stdClass();
		$oNcpRst->result = null;
		if(!$this->_g_sDomainName)
		{
			$oNcpRst->result = 'domain name not defined';
			return $oNcpRst;
		}
		$this->_setHttpMethod('get');
		$this->_g_sApiUrlFull = $this->_g_sApiRootUrl.'/domain/'.$this->_g_sDomainName.'/document/manage/db_upload?limit=10&page=0';
		$sResponse = $this->_requestHttp();
		var_dump($sResponse);
		if($sResponse)
		{
			;
		}
	}
/**
 * @brief 검색 결과 가져오기
 * ref: https://api.ncloud-docs.com/docs/analytics-cloudsearch-searchdocument
 **/
	public function getSearchList($bUseCache=false)
	{
		if(!$this->_g_sDomainName)
		{
			$oNcpRst = new stdClass();
			$oNcpRst->result = 'domain name not defined';
			return $oNcpRst;
		}
		$this->_setHttpMethod('post');
		$this->_g_sApiUrlFull = $this->_g_sApiRootUrl.'/domain/'.$this->_g_sDomainName.'/document/search';
		
		if($bUseCache)
			$oRst = $this->_getNcpSearchCacheAbs();
		if($bUseCache && $oRst->bCacheFound)
			$oNcpRst = $this->_constructNcpSearchRstAbs($oRst->oNcpReturn);
		else
		{
			//echo __FILE__.':'.__LINE__.'<BR>';
			//return;
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
			$sResponse = $this->_requestHttp($sJsonQueryDSL);
			if($sResponse)
			{
				if($bUseCache)
					$this->_setNcpSearchCacheAbs($sResponse);
				$oNcpRst = $this->_constructNcpSearchRstAbs($sResponse);
			}
		}
		unset($oRst);
		return $oNcpRst;
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
// $oSvNcpCloudSearch->setDomain('search_yuhanclorox');
// $oSvNcpCloudSearch->setStartPosition(1);
// $oSvNcpCloudSearch->setQuery('락스 냄새');
// $oResponse = $oSvNcpCloudSearch->getSearchListXml();
// unset($oSvNcpCloudSearch);

/* End of file svncp.cloud_search.php */
/* Location: ./modules/integration_search/svncp.cloud_search.php */