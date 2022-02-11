<?php
//define('INIPAY_HOME', _XE_PATH_.'files/svpg/iniescrow');
//define('INIPAY_LOGDIR', _XE_PATH_.'files/svpg/iniescrow/log');
//define('INIPAY_KEYDIR', _XE_PATH_.'files/svpg/iniescrow/key');

class kcb_okname extends SvauthPlugin 
{
	var $_g_oPluginInfo;
/**
 * @brief
 */
	function pluginInstall($args) 
	{
		// mkdir
		FileHandler::makeDir(_XE_PATH_.'files/svauth/kcb_okname/'.$args->plugin_srl.'/log');
		// copy files
		//FileHandler::copyFile(_XE_PATH_.'modules/svpg/plugins/iniescrow/.htaccess',sprintf(_XE_PATH_."files/svpg/%s/.htaccess",$args->plugin_srl));
		//FileHandler::copyFile(_XE_PATH_.'modules/svpg/plugins/iniescrow/readme.txt',sprintf(_XE_PATH_."files/svpg/%s/readme.txt",$args->plugin_srl));
		//FileHandler::copyFile(_XE_PATH_.'modules/svpg/plugins/iniescrow/key/pgcert.pem',sprintf(_XE_PATH_."files/svpg/%s/key/pgcert.pem",$args->plugin_srl));
	}
/**
 * @brief
 */
	public function kcb_okname() 
	{
		parent::svauthPlugin();
	}
/**
 * @brief
 */
	public function init(&$args)
	{
		$this->_g_oPluginInfo = new StdClass();
		foreach ($args as $key=>$val)
			$this->_g_oPluginInfo->{$key} = $val;
		foreach ($args->extra_var as $key=>$val)
			$this->_g_oPluginInfo->{$key} = $val->value;
		Context::set('plugin_info', $this->_g_oPluginInfo);
	}
/**
 * @brief
 */
	public function getFormData($args)
	{
		if( !$this->_g_oPluginInfo->plugin_srl )
			return new BaseObject(-1,'plugin_srl not defined');

		Context::set('plugin_srl', $this->_g_oPluginInfo->plugin_srl);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/svauth/plugins/kcb_okname/tpl";
		$tpl_file = 'formdata.html';
		$form_data = $oTemplate->compile($tpl_path, $tpl_file);

		$output = new BaseObject();
		$output->data = $form_data;
		return $output;
	}
/**
 * @brief 파일명: hs_cnfrm_popup2.php
 * 본인확인서비스 개인 정보 입력 화면(고객 인증정보 KCB팝업창에서 입력용)
 * ※주의
 * 실제 운영시에는 response.write를 사용하여 화면에 보여지는 데이터를 
 * 삭제하여 주시기 바랍니다. 방문자에게 사이트데이터가 노출될 수 있습니다.
 */
	public function processReview()
	{
		$oArgs = Context::getRequestVars();
		// okname 본인확인서비스 파라미터
		$name = "x"; // 성명
		$birthday = "x"; // 생년월일 
		$sex = "x"; // 성별
		$nation="x"; // 내외국인구분 
		$telComCd="x"; // 이동통신사코드 
		$telNo="x"; // 휴대폰번호 

		// * 파라미터에 대한 유효성여부를 검증한다.
		$inTpBit = $oArgs->in_tp_bit;// $_POST["in_tp_bit"];	// 입력구분코드(0:없음, 1:기본정보, 2:내외국인, 4:휴대폰정보)
		if (preg_match('~[^0-9]~', $inTpBit, $match)) 
		{
			echo ("<script>alert('입력구분코드에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
			exit;
		}
		$inTpBitVal = intval($inTpBit, 0);
		if (($inTpBitVal & 1) == 1) 
		{
			$name = $oArgs->name; //$_POST["name"]; // 성명
			if (preg_match('~[^\x{ac00}-\x{d7af}a-zA-Z ]~u', $name, $match)) 
			{	// UTF-8인 경우
				echo ("<script>alert('성명에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
		}
		if (($inTpBitVal & 2) == 2) 
		{
			$birthday = $oArgs->birthday;//$_POST["birthday"]; // 생년월일
			if (preg_match('~[^0-9]~', $birthday, $match)) 
			{
				echo ("<script>alert('생년월일에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
		}
		if (($inTpBitVal & 4) == 4) 
		{
			$sex = $oArgs->sex;// $_POST["sex"]; // 성별
			$nation = $oArgs->nation;// $_POST["nation"]; // 내외국인구분
			if (preg_match('~[^01]~', $sex, $match)) 
			{
				echo ("<script>alert('성별에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
			if (preg_match('~[^12]~', $nation, $match)) 
			{
				echo ("<script>alert('내외국인 구분에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
		}
		if (($inTpBitVal & 8) == 8) 
		{
			$telComCd = $oArgs->tel_com_cd;// $_POST["tel_com_cd"]; // 통신사코드
			$telNo = $oArgs->tel_no;// $_POST["tel_no"]; // 휴대폰번호

			if (preg_match('~[^0-9]~', $telComCd, $match)) 
			{
				echo ("<script>alert('통신사코드에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
			if (preg_match('~[^0-9]~', $telNo, $match)) 
			{
				echo ("<script>alert('휴대폰번호에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
		}
		$rqstCausCd = $oArgs->rqst_caus_cd;//$_POST["rqst_caus_cd"]; // 인증요청사유코드 2byte  (00:회원가입, 01:성인인증, 02:회원정보수정, 03:비밀번호찾기, 04:상품구매, 99:기타)
		if (preg_match('~[^0-9]~', $rqstCausCd, $match)) 
		{
			echo ("<script>alert('인증요청사유코드에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
			exit;
		}
		if( strlen( $this->_g_oPluginInfo->shop_id ) == 0 || is_null($this->_g_oPluginInfo->shop_id) )
		{
			echo ("<script>alert('샵ID를 입력해주세요.'); self.close();</script>");
			exit;
		}

		if( strlen( $this->_g_oPluginInfo->domain ) == 0 || is_null($this->_g_oPluginInfo->domain) )
		{
			echo ("<script>alert('사용 도메인 입력해주세요.'); self.close();</script>");
			exit;
		}

		$svcTxSeqno = $this->_generateSvcTxSeqno();	// 거래번호. 동일문자열을 두번 사용할 수 없음. (최대 30자리의 문자열. 0-9,A-Z,a-z 사용)
		// # KCB로부터 부여받은 회원사코드(아이디) 설정 (12자리)
		$memId = $this->_g_oPluginInfo->shop_id ? $this->_g_oPluginInfo->shop_id : 'P00000000000';// 회원사코드(아이디)
		// # 회원사 모듈설치서버 IP 및 회원사 도메인 설정
		$serverIp = 'x'; // 모듈이 설치된 서버IP (서버IP검증을 무시하려면 'x'로 설정)
		$siteDomain = $this->_g_oPluginInfo->domain ? $this->_g_oPluginInfo->domain : 'ok-name.co.kr'; // 회원사 도메인. (휴대폰인증번호 발송시 제휴사명에 노출)
		$rsv1 = '0'; // 예약 항목
		$rsv2 = '0'; // 예약 항목
		$rsv3 = '0'; // 예약 항목
		$hsCertMsrCd = '10'; // 인증수단코드 2byte  (10:핸드폰)
		$returnMsg = 'x'; // 리턴메시지 (고정값 'x') 
		
		// # 리턴 URL 설정
		// opener(hs_cnfrm_popup1.php)의 도메일과 일치하도록 설정해야 함. 
		// (http://www.test.co.kr과 http://test.co.kr는 다른 도메인으로 인식하며, http 및 https도 일치해야 함)
		//$returnUrl = "http://".$_SERVER['HTTP_HOST']."/okname/hs_cnfrm_popup3.php";// 본인인증 완료후 리턴될 URL (도메인 포함 full path)
		//http://balanceseat.co.kr/index.php?module=svauth&act=dispSvauthResult&plugin_srl=45988
		$returnUrl = getNotEncodedFullUrl('','module','svauth','act','dispSvauthResult', 'plugin_srl',$oArgs->plugin_srl );
		// # 운영전환시 변경 필요
		//$endPointURL = "http://tsafe.ok-name.co.kr:29080/KcbWebService/OkNameService";	// 테스트 서버
		$endPointURL = "http://safe.ok-name.co.kr/KcbWebService/OkNameService"; // 운영 서버 

		// # 로그 경로 지정 및 권한 부여 (절대경로)
		$logPath = _XE_PATH_.'files/svauth/kcb_okname/'.$oArgs->plugin_srl.'/log';//"/okname/log";

		// # 옵션값에 'L'을 추가하는 경우에만 로그(logPath변수에 설정된)가 생성됨.
		// # 시스템(환경변수 LANG설정)이 UTF-8인 경우 'U'옵션 추가 ex)$option='QLU'
		$options = "QU";		// Q:인증요청데이터 암호화

		$cmd = array($svcTxSeqno, $name, $birthday, $sex, $nation, $telComCd,
					$telNo, $rsv1, $rsv2, $rsv3, $returnMsg, $returnUrl, $inTpBit,
					$hsCertMsrCd, $rqstCausCd, $memId, $serverIp, $siteDomain,
					$endPointURL, $logPath, $options);
		// okname 실행
		$output = NULL;
		$ret = okname($cmd, $output);

		// okname 응답 정보
		$retcode = ''; // 결과코드
		$retmsg = ''; // 결과메시지
		$e_rqstData = ''; // 암호화된요청데이터
		
		if ($ret == 0) //성공일 경우 변수를 결과에서 얻음
		{
			$result = explode("\n", $output);
			$retcode = $result[0];
			$retmsg  = $result[1];
			$e_rqstData = $result[2];
		}
		else 
		{
			if($ret <=200)
				$retcode=sprintf("B%03d", $ret);
			else
				$retcode=sprintf("S%03d", $ret);
		}
		
		// * hs_cnfrm_popup3.php 실행 정보
		$targetId = ''; // 타겟ID (결과를 전달할 팝업이 따로 있을 경우 해당 팝업명(window.name 설정값)을 설정. 일반적으로 ""으로 설정)

		// # 운영전환시 변경 필요
		//$commonSvlUrl = "https://tsafe.ok-name.co.kr:2443/CommonSvl";	// 테스트 URL
		$commonSvlUrl = "https://safe.ok-name.co.kr/CommonSvl";	// 운영 URL

		Context::set('e_rqstData', $e_rqstData);
		Context::set('retcode', $retcode);
		Context::set('targetId', $targetId);
		Context::set('commonSvlUrl', $commonSvlUrl);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/svauth/plugins/kcb_okname/tpl";
		$tpl_file = 'review.html';
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
/**
 * @brief 서비스거래번호를 생성한다.
 */
	private function _generateSvcTxSeqno() 
	{   
		$numbers  = "0123456789";   
		$svcTxSeqno = date("YmdHis");   
		$nmr_loops = 6;   
		while ($nmr_loops--) {
			$svcTxSeqno .= $numbers[mt_rand(0, strlen($numbers)-1)];   
		}   
		return $svcTxSeqno;   
	}   
/**
 * @brief 파일명 : hs_cnfrm_popup3.php
 * 본인확인서비스 결과 화면(return url)
 */
	public function processResult()
	{
		$oArgs = Context::getRequestVars();

		// 공통 리턴 항목 
		$rqstSiteNm	 = $oArgs->rqst_site_nm;//$_POST["rqst_site_nm"]; // 접속도메인	
		$rqstCausCd	= $oArgs->hs_cert_rqst_caus_cd;//$_POST["hs_cert_rqst_caus_cd"]; // 인증요청사유코드 2byte  (00:회원가입, 01:성인인증, 02:회원정보수정, 03:비밀번호찾기, 04:상품구매, 99:기타)

		// 모듈 호출; 본인확인서비스 결과 데이터를 복호화한다.
		// 인증결과 암호화 데이터
		$encInfo = $oArgs->encInfo;//$_POST["encInfo"];
		//KCB서버 공개키
		$WEBPUBKEY = trim($oArgs->WEBPUBKEY);//$_POST["WEBPUBKEY"]);
		//KCB서버 서명값
		$WEBSIGNATURE = trim($oArgs->WEBSIGNATURE);//$_POST["WEBSIGNATURE"]);

		// 파라미터에 대한 유효성여부를 검증한다.
		if(preg_match('~[^0-9a-zA-Z+/=]~', $encInfo, $match)) {echo "입력 값 확인이 필요합니다"; exit;}
		if(preg_match('~[^0-9a-zA-Z+/=]~', $WEBPUBKEY, $match)) {echo "입력 값 확인이 필요합니다"; exit;}
		if(preg_match('~[^0-9a-zA-Z+/=]~', $WEBSIGNATURE, $match)) {echo "입력 값 확인이 필요합니다"; exit;}

		// # KCB로부터 부여받은 회원사코드(아이디) 설정 (12자리)
		$memId = $this->_g_oPluginInfo->shop_id ? $this->_g_oPluginInfo->shop_id : 'P00000000000';// 회원사코드(아이디)
		// # 운영전환시 변경 필요
		//$endPointUrl = "http://tsafe.ok-name.co.kr:29080/KcbWebService/OkNameService";//EndPointURL, 테스트 서버
		$endPointUrl = "http://safe.ok-name.co.kr/KcbWebService/OkNameService";// 운영 서버
		// # 암호화키 파일 설정 (절대경로) - 파일은 주어진 파일명으로 자동 생성되며 생성되지 않으면 S211오류가 발생됨
		// # 파일은 매월초에 갱신되며 만일 파일이 갱신되지 않으면 복화화데이터가 깨지는 현상이 발생됨.
		//$keyPath = "/okname/safecert_".$memId."_test.key";	// 테스트 키파일
		$keyPath = _XE_PATH_.'files/svauth/kcb_okname/'.$oArgs->plugin_srl.'/safecert_'.$memId.'.key'; //"/okname/safecert_".$memId.".key"; // 운영 키파일

		// # 로그 경로 지정 및 권한 부여 (hs_cnfrm_popup2.asp에서 설정된 값과 동일하게 설정)
		$logPath = _XE_PATH_.'files/svauth/kcb_okname/'.$oArgs->plugin_srl.'/log';//"/okname/log";
		// # 옵션값에 'L'을 추가하는 경우에만 로그(logPath변수에 설정된)가 생성됨.
		// # 시스템(환경변수 LANG설정)이 UTF-8인 경우 'U'옵션 추가 ex)$option='SLU'
		$options = "SUL"; // S:인증결과복호화
		// 명령어
		$cmd = array($keyPath, $memId, $endPointUrl, $WEBPUBKEY, $WEBSIGNATURE, $encInfo, $logPath, $options);
		// okname 실행
		$output = NULL;
		$ret = okname($cmd, $output);
//$ret= 0;
		$retcode = '';
		if($ret == 0) 
		{
			$result = explode("\n", $output);
/*$result[0]= "B000";
$result[1]="본인인증 완료";
$result[2]="20170312142253327166";
$result[3]="20170312142302";
$result[4]="MC0GCCqGSIb3DQIJAyEAmKmc1NumzrbpxeXFvMH8x2XCN15T/6h1Aj0hqx9zos4=";
$result[5]="Fvkt9MBfFHm6TFXFFe03bp7rkxN8bzIRgq8ZWGPr4WBk5EaQ+9jIKL/oIlCdt0svHNzCEOGI/YWy4vvVuFxtoA==";
$result[6]=" ";
$result[7]="성이름";
$result[8]="19790225";
$result[9]="1";
$result[10]="1";
$result[11]="01";
$result[12]="01031751234";
$result[13]=" ";
$result[14]=" ";
$result[15]=" ";
$result[16]="x";
$result[17]="";*/
			$retcode = $result[0];
		}
		else 
		{
			if($ret <=200)
				$retcode=sprintf("B%03d", $ret);
			else
				$retcode=sprintf("S%03d", $ret);
		}

		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/svauth/plugins/kcb_okname/tpl";

		//만약 중복가입을 방지하고 있다면, DI조회후 결과값있는경우 다른페이지 set
		if($config->free_di != "Y")
		{
			$oSvauthModel = getModel('svauth');
			$aRst = $oSvauthModel->getAuthLog($result[4]);
			if( count( $aRst ) )
				return $oTemplate->compile($tpl_path, 'result_duplicated.html');
		}
		//인증 성공하고 중복 인증이 아니면 세션에 저장
		if( $retcode == 'B000' )
		{
			setcookie('sv_auth_info', $result[4], 0, '/');
			$aResult["resultCd"] = $retcode; //처리결과코드
			$aResult["resultMsg"] = $result[1]; //처리결과메시지
			$aResult["hsCertSvcTxSeqno"] = $result[2]; //거래일련번호 (sequence처리)
			$aResult["auth_date"] = $result[3]; //인증일시
			$aResult["DI"] = $result[4]; //DI
			$aResult["CI"] = $result[5]; //CI
			$aResult["user_name"] = $result[7]; //성명
			$aResult["birthday"] = $result[8]; //생년월일
			//$aResult["age"] = substr(date('Ymd')-$result[8],0,2); //만 나이
			switch( $result[9] ) //성별 1:남, 0:여
			{
				case '0':
					$sGender = 'f';
					break;
				case '1':
					$sGender = 'm';
					break;
				default:
					$sGender = 'n';
			}
			$aResult["gender"] = $sGender;

			switch( $result[10] ) //내외국인구분 1:내국인, 2:외국인 
			{
				case '1':
					$sNationality = 'd';
					break;
				case '2':
					$sNationality = 'f';
					break;
				default:
					$sNationality = 'n';
			}
			$aResult["nationality"] = $sNationality;
			
			switch( $result[11] ) //통신사코드 01:SKT, 02:KT, 03:LGU+, 04:SKT알뜰폰, 05:KT알뜰폰, 06:LGU+알뜰폰
			{
				case '01':
					$sIsp = 'SKT';
					break;
				case '02':
					$sIsp = 'KT';
					break;
				case '03':
					$sIsp = 'LGU+';
					break;
				case '04':
					$sIsp = 'SKT_ECO';
					break;
				case '05':
					$sIsp = 'KT_ECO';
					break;
				case '06':
					$sIsp = 'LGU+_ECO';
					break;
				default:
					$sIsp = 'N/A';
			}
			$aResult["ISP"] = $sIsp; 
			$aResult["mobile"] = $result[12]; //휴대폰번호
			$oSvauthController = getController('svauth');
			$oSvauthController->addAuthLog($aResult);
		}
		else
			setcookie('sv_auth_info', '', 0, '/');

		Context::set('ret', $ret);
		Context::set('retcode', $retcode);
		return $oTemplate->compile($tpl_path, 'result.html');
	}
}
/* End of file kcb_okname.plugin.php */
/* Location: ./modules/svauth/plugins/kcb_okname/kcb_okname.plugin.php */