/**
 * @brief 회원 가입시나 정보 수정시 각 항목의 중복 검사등을 하는 기능을 구현
 * @author XEHub (developer@xpressengine.com)
 **/

// 입력이 시작된 것과 입력후 정해진 시간동안 내용이 변하였을 경우 서버에 ajax로 체크를 하기 위한 변수 설정
var memberCheckObj = { target:null, value:null }

// domready시에 특정 필드들에 대해 이벤트를 걸어 놓음
jQuery(document).ready(memberSetEvent);

function memberSetEvent() {
	jQuery('#fo_insert_member :input')
		.filter('[name=user_id],[name=nick_name],[name=email_address]')
		.blur(memberCheckValue);
}


// 실제 서버에 특정 필드의 value check를 요청하고 이상이 있으면 메세지를 뿌려주는 함수
function memberCheckValue(event) {
	var field  = event.target;
	var _name  = field.name;
	var _value = field.value;
	if(!_name || !_value) return;

	var params = {name:_name, value:_value};
	var response_tags = ['error','message'];

	exec_xml('member','procMemberCheckValue', params, completeMemberCheckValue, response_tags, field);
}

// 서버에서 응답이 올 경우 이상이 있으면 메세지를 출력
function completeMemberCheckValue(ret_obj, response_tags, field) {
	var _id   = 'dummy_check'+field.name;
	var dummy = jQuery('#'+_id);
   
    if(ret_obj['message']=='success') {
        dummy.html('').hide();
        return;
    }

	if (!dummy.length) {
		dummy = jQuery('<p class="checkValue help-inline" style="color:red" />').attr('id', _id).appendTo(field.parentNode);
	}

	dummy.html(ret_obj['message']).show();
}

// 결과 메세지를 정리하는 함수
function removeMemberCheckValueOutput(dummy, obj) {
    dummy.style.display = "none";
}

var _g_$oBtn;

// 핸드폰 번호 인증
function getAuthCode()
{
	var sMobileNumber = jQuery('#mobile').val().trim();
	if(sMobileNumber.length == 0)
	{
		alert('연락처를 입력해 주세요.');
		return;
	}

	const oReg = new RegExp('^[0-9]+$');
	if(!oReg.test(sMobileNumber)){
		alert('숫자만 입력해 주세요.');
		return;
	}

	_disableBtn( '#get_authcode' );
	var params = new Array();
	params['phone_number'] = sMobileNumber;
	var respons = ['success'];
	exec_xml('svauth', 'procSvauthSetAuthCodeMemberAjax', params, function(ret_obj) {
		if( ret_obj['message'] )
			alert(ret_obj['message']);

		if( ret_obj['success'] == -1 )
			_activateBtn();
	}, respons);
}

function _disableBtn(sBtnId)
{
	_g_$oBtn = jQuery(sBtnId);
	_g_$oBtn.prop('disabled', true);
	_g_$oBtn.css('background-color','#323232');
	_g_$oBtn.css('color','#b0b0b0');
	_g_$oBtn.css('border','1px solid #323232');
}

function _activateBtn()
{
	_g_$oBtn.prop('disabled', false);
	_g_$oBtn.css('background-color','#ed1c24');
	_g_$oBtn.css('color','#fff');
	_g_$oBtn.css('border','1px solid #ed1c24');
}