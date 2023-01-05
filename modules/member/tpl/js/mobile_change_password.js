function requestSmsAuth(sMode){
    if(sMode == 'find_id')
    {
        sMobile = $('#mobile_user_id').val().trim();
        sUserName = $('#user_name_sms').val().trim();
        if(sUserName.length && sMobile.length)
        {
            var params = new Array();
            params['user_name'] = sUserName;
            params['mobile'] = sMobile;
            params['req_mode'] = sMode;
            var response = ['isValid', 'nMemberSrl', 'sAuthKey'];
            exec_xml('member', 'procMemberRequestSmsAuthAjax', params, function(ret_obj) {
                if(ret_obj['message'] == 'success')
                {
                    if(ret_obj['isValid'] == 1){
                        alert('인증 번호를 전송했습니다.');
                        $('#member_srl').val(ret_obj['nMemberSrl']);
                        $('#auth_key').val(ret_obj['sAuthKey']);
                    }else{
                        alert('잘못된 요청입니다.');
                    }
                }
            }, response);
        }
    }
    else if(sMode == 'reset_pw')
    {
        sMobile = $('#mobile_pw_rst').val().trim();
        sUserId = $('#user_id_sms').val().trim();
        console.log(sUserId);
        if(sUserId.length && sMobile.length)
        {
            var params = new Array();
            params['user_id'] = sUserId;
            params['mobile'] = sMobile;
            params['req_mode'] = sMode;
            var response = ['isValid', 'nMemberSrl', 'sAuthKey'];
            exec_xml('member', 'procMemberRequestSmsAuthAjax', params, function(ret_obj) {
                if(ret_obj['message'] == 'success')
                {
                    if(ret_obj['isValid'] == 1){
                        alert('인증 번호를 전송했습니다.');
                        $('#member_srl').val(ret_obj['nMemberSrl']);
                        $('#auth_key').val(ret_obj['sAuthKey']);
                    }else{
                        alert('잘못된 요청입니다.');
                    }
                }
            }, response);
        }
    }
}

function validateSmsAuth(sMode){
    sAuthKey = $('#auth_key').val();
    nMemberSrl = $('#member_srl').val();
// console.log(sAuthKey, nMemberSrl, sMobile, sSmsPhrase);
    if(sAuthKey.length && nMemberSrl)
    {
        var params = new Array();
        params['auth_key'] = sAuthKey;
        params['member_srl'] = nMemberSrl;
        if(sMode == 'find_id')
        {
            params['mobile'] = $('#mobile_user_id').val().trim();
            params['sms_phrase'] = $('#sms_phrase_user_id').val().trim();
// console.log(params['sms_phrase']);
            params['req_mode'] = sMode;
            var response = ['isValid', 'user_id'];
            exec_xml('member', 'getSmsAuthValdationAjax', params, function(ret_obj) {
                if(ret_obj['message'] == 'success')
                {
// console.log(ret_obj['isValid']);
// console.log(ret_obj['user_id']);
                    if(ret_obj['isValid'] == 1){
                        $('#user_id_revealed').html(ret_obj['user_id']);
                        jQuery('#reveal_user_id').slideToggle('slow', function(){});
                    }else{
                        alert('만료된 요청입니다.');
                    }
                }
            }, response);
        }
        else if(sMode == 'reset_pw')
        {
            params['mobile'] = $('#mobile_pw_rst').val().trim();
            params['sms_phrase'] = $('#sms_phrase_pw_rst').val().trim();;
            params['req_mode'] = sMode;
            var response = ['isValid'];
            exec_xml('member', 'getSmsAuthValdationAjax', params, function(ret_obj) {
                if(ret_obj['message'] == 'success')
                {
                    if(ret_obj['isValid'] == 1){
                        jQuery('#reset_password').slideToggle('slow', function(){});
                    }else{
                        alert('만료된 요청입니다.');
                    }
                }
            }, response);
        }
    }
}

function changePassword(){
	sPassword1 = $('#password_1').val().trim();
	sPassword2 = $('#password_2').val().trim();
	if(sPassword1 != sPassword2)
	{
		alert('패스워드가 일치하지 않습니다.');
		return;
	}

	sAuthKey = $('#auth_key').val();
	nMemberSrl = $('#member_srl').val();
	sMobile = $('#mobile_pw_rst').val().trim();
	sSmsPhrase = $('#sms_phrase_pw_rst').val().trim();
	if(sAuthKey.length && nMemberSrl && sMobile.length && sSmsPhrase && sPassword1)
	{
		var params = new Array();
		params['auth_key'] = sAuthKey;
		params['member_srl'] = nMemberSrl;
		params['mobile'] = sMobile;
		params['sms_phrase'] = sSmsPhrase;
		params['new_password'] = sPassword1;
		var response = ['isChanged'];
		exec_xml('member', 'procMemberModifyPasswordAjax', params, function(ret_obj) {
			if(ret_obj['message'] == 'success')
			{
				if(ret_obj['isChanged'] == 1){
					jQuery('#reset_password').slideToggle('slow', function(){});
                    $('#user_id_sms').val('');
                    $('#mobile_pw_rst').val('');
                    $('#auth_key').val('');
                    $('#member_srl').val('');
					$('#password_1').val('');
					$('#password_2').val('');
					$('#sms_phrase_pw_rst').val('');
					alert('비밀번호가 변경되었습니다.');
				}else{
					alert('비밀번호를 변경할 수 없습니다.');
				}
			}
		}, response);
	}
}

function onlyNumberCheck(obj){
	if(isNull(obj.value)) return; 
	if(!isInteger(obj.value)){
		var alertMsg  = "정수 숫자만 입력하실 수 있습니다.\n\n";
			alertMsg += "입력범위 : 0 ~ 9\n";
			alertMsg += "입력예시 : 011, 2003, 1234567890, etc.";
		alert(alertMsg);
		obj.value = "";

	}
}

function isInteger(objValue)
{
	var bool = true;
	if(objValue == null || objValue == "")
		bool = false;
	else
	{
		for (var i=0; i<objValue.length; i++)
		{
			ch = objValue.charCodeAt(i);
				if(!(ch >= 0x30 && ch <= 0x39))
				{
					bool = false;
					break;
				}
		}
	}
	return bool;
}

function isNull(str){
	str = $.trim(str);
	if(str == null || str == 'undefined' || str.length == 0) { 
		return true; 
	}
	return false;
}