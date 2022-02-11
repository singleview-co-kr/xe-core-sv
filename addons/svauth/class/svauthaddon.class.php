<?php
/**
* @brief svauth ��⿡ ������
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
 * @brief ȭ�鿡 ���÷��� �Ҷ��� ���� , �α������̸� �ߴ�
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
 * @brief ��ȸ�� �������� ���� ���� �α��� act�� ����
 **/
	private function _checkNonMemberAuth()
	{
		if($this->_g_oConfig->use_nm == 'Y' && Context::get('act') == "dispMemberLoginForm") 
		{
			header ("Location: ".getNotEncodedUrl('act','dispSvauthLoginForm'));
			return FALSE;
		}

		//���������� �� �ڿ� ���Խõ����̶�� ������������, �ƴ϶�� 'act'�� ����
		//post ���� ss �� ���� üũ������ �������� �������� �ƴ��� �˱����� ����.
		if($_POST['ss'] == 'nm')
		{
			//���������� �ִ°��
			if($this->_g_oConfig->limit_age)
			{
				if($this->_g_oConfig->limit_type == 'down' && $this->_g_aAuth['age'] >= $this->_g_oConfig->limit_age) 
					$unset = true;
				if(($this->_g_oConfig->limit_type == 'up' || !$this->_g_oConfig->limit_type) &&  $this->_g_aAuth['age'] < $this->_g_oConfig->limit_age) 
					$unset = true;
				if($unset)
				{
//unset($_SESSION['auth_info']);
					$limit_type = ($this->_g_oConfig->limit_type == 'down') ? "�̸�" : "�̻�";
					echo "<script> alert('�� {$this->_g_oConfig->limit_age}�� {$limit_type}�� �̿��Ͻ� �� �����ϴ�.'); document.location.href = '".getNotEncodedUrl('act',$this->_g_sLoginAct)."' </script>";
				}
			}
			if($_POST['signupform'] == 'true')
			{
				header('Location: '.getNotEncodedUrl('act','dispMemberSignUpForm'), $_SERVER['REQUEST_URI']);
				return FALSE;
			}
			else //��ȸ�� �������� ���� �����ϴºκ�.
			{
				header('Location: '.getNotEncodedUrl('','mid',Context::get('mid'),'act',''), $_SERVER['REQUEST_URI']);
				return FALSE;
			}
		}
		return TRUE;
	}
/**
 * @brief �������̰ų� �������϶� ��ä���, ����/�������� ó��
 * join_extend module ����.
 **/
	private function _checkAuthInprogess()
	{
		if(Context::get('act')=='dispMemberSignUpForm' || Context::get('act')=='dispMemberModifyInfo')
		{
			//Context::addHtmlHeader(json_encode($_SESSION['auth_info']));
			// �������� �� �������ó��
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
 * @brief ������ �� ������ ������ ���
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
		//���Խ� �������� ��� - �������ε� ���������� ������ ����â����
		if($this->_g_oConfig->use_join == 'Y' && Context::get('act') == 'dispMemberSignUpForm' && !$this->_g_aAuth)
		{
			header('Location: '.getNotEncodedUrl('act','dispSvauthLoginForm','signup','true'), $_SERVER['REQUEST_URI']);
			return FALSE;
		}
		else if( $this->_g_oConfig->interception == 'Y' && !$oMemberModel->isLogged() && !strpos(Context::get('act'),"Member") 
				&& !$this->_g_aAuth && !strpos(Context::get('act'),"Svauth") )
		{//��ȸ�� ���� ���ܽ� - ȸ�������� ���� ȸ������ �Լ��� �ƴѵ� ���� ������������ �α���â����
			header ("Location: ".getNotEncodedUrl('act',$this->_g_sLoginAct));
			return FALSE;
		}
	}
}