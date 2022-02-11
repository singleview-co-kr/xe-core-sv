<?php
/**
* @brief svauth 모듈에 의존성
* @author singleview.co.kr
* @developer root@singleview.co.kr
*/
class svAuthAddon
{
	var $_g_bRun = FALSE;
	var $_g_oConfig = null;
	var $_g_aAuth = null;
	var $_g_sLoginAct = '';
/**
 * @brief 화면에 디스플레이 할때만 동작 , 로그인중이면 중단
 **/
	public function svAuthAddon($sCalledPosition)
	{
		if($sCalledPosition == "before_display_content" && Context::get('module') != "admin") 
			$this->_g_bRun = TRUE;
	}
/**
 * @brief 
 **/
	public function doAuthAddonProc()
	{
		if( !$this->_g_bRun )
			return;
		$oModuleModel = &getModel('module');
		$this->_g_oConfig = $oModuleModel->getModuleConfig('svauth');
		$this->_g_sLoginAct = ($this->_g_oConfig->use_nm == 'Y') ? 'dispSvauthLoginForm' : "dispMemberLoginForm";

		if( !$this->_checkNonMemberAuth() )
			return;

		$this->_checkAuthInprogess();

		if( $this->_checkAuthLogExist() )
			return;

		$this->_checkMeberRegistration();
	}

/**
 * @brief 비회원 본인인증 사용시 기존 로그인 act를 변경
 **/
	private function _checkNonMemberAuth()
	{
		if($this->_g_oConfig->use_nm == 'Y' && Context::get('act') == "dispMemberLoginForm") 
		{
			header ("Location: ".getNotEncodedUrl('act','dispSvauthLoginForm'));
			return FALSE;
		}

		//본인인증을 한 뒤에 가입시도중이라면 가입페이지로, 아니라면 'act'를 제거
		//post 변수 ss 는 세션 체크이전에 본인인증 직후인지 아닌지 알기위한 변수.
		if($_POST['ss'] == 'nm')
		{
			//나이제한이 있는경우
			if($this->_g_oConfig->limit_age)
			{
				if($this->_g_oConfig->limit_type == 'down' && $this->_g_aAuth['age'] >= $this->_g_oConfig->limit_age) 
					$unset = true;
				if(($this->_g_oConfig->limit_type == 'up' || !$this->_g_oConfig->limit_type) &&  $this->_g_aAuth['age'] < $this->_g_oConfig->limit_age) 
					$unset = true;
				if($unset)
				{
//unset($_SESSION['auth_info']);
					$limit_type = ($this->_g_oConfig->limit_type == 'down') ? "미만" : "이상";
					echo "<script> alert('만 {$this->_g_oConfig->limit_age}세 {$limit_type}은 이용하실 수 없습니다.'); document.location.href = '".getNotEncodedUrl('act',$this->_g_sLoginAct)."' </script>";
				}
			}
			if($_POST['signupform'] == 'true')
			{
				header('Location: '.getNotEncodedUrl('act','dispMemberSignUpForm'), $_SERVER['REQUEST_URI']);
				return FALSE;
			}
			else //비회원 본인인증 사용시 동작하는부분.
			{
				header('Location: '.getNotEncodedUrl('','mid',Context::get('mid'),'act',''), $_SERVER['REQUEST_URI']);
				return FALSE;
			}
		}
		return TRUE;
	}
/**
 * @brief 가입중이거나 수정중일때 값채우고, 수정/노출제한 처리
 * join_extend module 참고.
 **/
	private function _checkAuthInprogess()
	{
		if(Context::get('act')=='dispMemberSignUpForm' || Context::get('act')=='dispMemberModifyInfo')
		{
			//Context::addHtmlHeader(json_encode($_SESSION['auth_info']));
			// 수정금지 및 노출안함처리
			$i = 1;
			if($this->_g_aAuth['birthday']) 
				$js_string = " fixed['birthday2'] = new Array(); fixed['birthday2']['name'] = 'birthday2'; fixed['birthday2']['value'] = '".zdate($this->_g_aAuth['birthday'],"Y-m-d")."'";
			foreach($this->_g_oConfig->extra_vars as $key => $val)
			{
				if(!$this->_g_oConfig->extra_vars[$key]['id']) 
					continue;
				if($this->_g_aAuth[$key])
				{
					$js_string .= "fixed['$key'] = new Array(); \n";
					$js_string .= "fixed['$key']['name'] = '{$this->_g_oConfig->extra_vars[$key]['id']}'; \n";
					$js_string .= "fixed['$key']['value'] = '{$this->_g_aAuth[$key]}' \n";
				}
				$js_string .= "no_mod_okname['$key'] = new Array(); \n";
				$js_string .= "no_mod_okname['$key']['id'] = '{$this->_g_oConfig->extra_vars[$key]['id']}'; \n";
				$js_string .= "no_mod_okname['$key']['ty'] = '{$this->_g_oConfig->extra_vars[$key]['ty']}';\n";
				$i++;
			}
			Context::addHtmlHeader(sprintf('<script type="text/javascript"> var no_mod_okname = new Array(); var fixed = new Array(); %s </script>', $js_string));
			Context::addJsFile('./modules/svauth/tpl/js/no_mod_okname.js',false);
		}
		return TRUE;
	}
/**
 * @brief 인증을 한 세션이 있으면 통과
 **/
	private function _checkAuthLogExist()
	{
		if( $_COOKIE['sv_auth_info'] )
		{
			$oSvauthModel = getModel('svauth');
			$this->_g_aAuth = $oSvauthModel->getAuthLog($_COOKIE['sv_auth_info']);
			if( $this->_g_aAuth )
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}
/**
 * @brief 
 **/
	private function _checkMeberRegistration()
	{
		$oMemberModel = &getModel('member');
		//가입시 본인인증 사용 - 가입중인데 인증정보가 없으면 인증창으로
		if($this->_g_oConfig->use_join == 'Y' && Context::get('act') == 'dispMemberSignUpForm' && !$this->_g_aAuth)
		{
			header('Location: '.getNotEncodedUrl('act','dispSvauthLoginForm','signup','true'), $_SERVER['REQUEST_URI']);
			return FALSE;
		}
		else if( $this->_g_oConfig->interception == 'Y' && !$oMemberModel->isLogged() && !strpos(Context::get('act'),"Member") 
				&& !$this->_g_aAuth && !strpos(Context::get('act'),"Svauth") )
		{//비회원 접속 차단시 - 회원정보도 없고 회원관련 함수도 아닌데 인증 정보도없으면 로그인창으로
			header ("Location: ".getNotEncodedUrl('act',$this->_g_sLoginAct));
			return FALSE;
		}
	}
}