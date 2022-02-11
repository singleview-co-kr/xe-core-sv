function jsSubmit()
{	
	var form1 = document.fo_kcbokname;
	var inTpBit = '';
	inTpBit = form1.in_tp_bit.value;
	// 사이트 화면에서 in_tp_bit 입력을 요구하는 유형은 다음의 조합을 따릅니다.
	//  1 : 0001 - 성명
	//  2 : 0010 - 생년월일
	//  3 : 0011 - 생년월일 + 성명 
	//  4 : 0100 - 성별,내외국인구분
	//  5 : 0101 - 성별,내외국인구분 + 성명
	//  6 : 0110 - 성별,내외국인구분 + 생년월일
	//  7 : 0111 - 성별,내외국인구분 + 생년월일 + 성명
	//  8 : 1000 - 통신사,휴대폰번호
	//  9 : 1001 - 통신사,휴대폰번호 + 성명
	// 10 : 1010 - 통신사,휴대폰번호 + 생년월일
	// 11 : 1011 - 통신사,휴대폰번호 + 생년월일 + 성명
	// 12 : 1100 - 통신사,휴대폰번호 + 성별,내외국인구분
	// 13 : 1101 - 통신사,휴대폰번호 + 성별,내외국인구분 + 성명
	// 14 : 1110 - 통신사,휴대폰번호 + 성별,내외국인구분 + 생년월일
	// 15 : 1111 - 통신사,휴대폰번호 + 성별,내외국인구분 + 생년월일 + 성명
	if (inTpBit & 1) 
	{
		if (form1.name.value == "") 
		{
			alert("성명을 입력해주세요");
			return;
		}
	}
	if (inTpBit & 2) 
	{
		if( form1.birthday.value.length != 8 )
		{
			alert("생년월일은 YYYYMMDD 형식으로 입력해주세요");
			return;
		}

		if (form1.birthday.value == "") {
			alert("생년월일을 입력해주세요");
			return;
		}
	}
	if (inTpBit & 8) 
	{
		if (form1.tel_com_cd.value == "") 
		{
			alert("통신사코드를 입력해주세요");
			return;
		}
		if (form1.tel_no.value == "") 
		{
			alert("휴대폰번호를 입력해주세요");
			return;
		}
	}
	// rqst_caus_cd 인증요청사유코드
	//00 회원가입
	//01 성인인증
	//02 회원정보수정
	//03 비밀번호찾기
	//04 상품구매
	//99 기타
	window.open("", "auth_popup", "width=430,height=590,scrollbar=yes");
	var form1 = document.fo_kcbokname;
	//procFilter( form1, submit_auth_review );
	form1.target = "auth_popup";
	form1.submit();
}

function completeKcboknameReviewAuth(ret_obj) 
{
	if (ret_obj['error']==0)
	{
		var tpl = ret_obj.tpl.replace(/<enter>/g, '\n');
		jQuery('#cashExtendForm').html(tpl);

		obj = document.getElementById('fo_kcbokname');
		obj.target = 'auth_popup';
		setTimeout("obj.submit();", 1000);
	}
}