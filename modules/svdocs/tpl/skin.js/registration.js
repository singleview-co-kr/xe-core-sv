var _g_$oBtn;
var g_timeEntryTime = _getCurrentDatetime();
function getAuthCode()
{
	var sApplicantPhoneNumber = jQuery('#applicant_phone_number').val();
	sApplicantPhoneNumber = sApplicantPhoneNumber.trim(); 

	if( sApplicantPhoneNumber.length == 0 )
	{
		alert('연락처를 입력해 주세요.');
		return;
	}

	_disableBtn( '#get_authcode' );

	var nModuleSrl = jQuery('#module_srl').val();
	var params = new Array();
	params['module_srl'] = nModuleSrl;
	params['applicant_phone_number'] = sApplicantPhoneNumber;

	var respons = ['success'];
	exec_xml('svdocs', 'procSvdocsSetAuthCode', params, function(ret_obj) {
		if( ret_obj['message'] )
			alert(ret_obj['message']);

		if( ret_obj['success'] == -1 )
			_activateBtn();
	},respons);
}

function _disableBtn( sBtnId )
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

function askUpdate()
{
	var nModuleSrl = jQuery('#module_srl').val();
	if( !nModuleSrl )
	{
		alert('이벤트가 지정되지 않았습니다.');
		return;
	}
	var params = new Array();
	params['module_srl'] = nModuleSrl;
	
	var respons = ['is_update_allowed','target_mid'];
	exec_xml('svdocs', 'getSvdocsUpdatePermission', params, function(ret_obj) {
		if( ret_obj['message'] == 'success' )
		{
			if( ret_obj['is_update_allowed'] == 1 )
				window.location.href='/'+ret_obj['target_mid']+'/?act=dispSvdocsUpdate';
			else
			{
				alert('응모하신 내용을 수정할 수 없습니다.');
				return;
			}
		}
	},respons);
}

function doUpdate()
{
	var nSvdocsSrl = jQuery('#svdocs_srl').val();
	if( !nSvdocsSrl )
	{
		alert('문서가 지정되지 않았습니다.');
		return;
	}
	var params = new Array();
	params['svdocs_srl'] = nSvdocsSrl;
	
	params['module_srl'] = jQuery('#module_srl').val();
	var aExtraVars = _getAllExtraVars();
	for(var sName in aExtraVars) 
	{
		var sTempName = sName.replace( "[]", ""); // param 인지가 XML로 전환될 때 param 이름에 []가 남아있으면 xml parser가 오류
		params[sTempName] = aExtraVars[sName];
	}
	//_disableBtn( '#btnSubmit' );
	var respons = ['result'];
	exec_xml('svdocs', 'procSvdocsUpdate', params, function(ret_obj) {
		if( ret_obj['message'] == 'success' )
		{
			_disableBtn( '#btnSubmit' );
			if( jQuery('#layer1').length ) // for pc
			{
				jQuery('#layer1').fadeOut(); // 신청양식 숨기기
				setTimeout("layer_open('layer_thankyou')", 500); 
			}
			else // for jquery mobile loaded page
				alert('신청해 주셔서 감사드립니다.');	
		}
	},respons);
}

function _getAllExtraVars()
{
	var aExtVars = [];
	var inputValues = jQuery('#exForm :input').map(function() {
		var type = jQuery(this).prop("type");
//console.log( 'name:'+jQuery(this).attr('name'));
//console.log( 'type:'+jQuery(this).prop("type"));
//console.log( 'value:'+ jQuery(this).val());
		// all other fields, except buttons
		if(type != "button" || type != "submit") 
		{
			// checked radios/checkboxes
			if(type == "checkbox") 
			{ 
				if(jQuery(this).is(':checked'))
				{
					//console.log( jQuery(this).val() ); 
					if (typeof aExtVars[jQuery(this).attr('name')] !== 'undefined')
						var sTempCbVal = aExtVars[jQuery(this).attr('name')]+'|@|'+jQuery(this).val();
					else
						var sTempCbVal = jQuery(this).val();
//console.log( 'sTempCbVal:'+sTempCbVal);
					aExtVars[jQuery(this).attr('name')] = sTempCbVal;//jQuery(this).val();
				}
			}
			else if(type == "radio")
			{ 
				//console.log( 'radio:'+jQuery(this).attr('name'));
				if(jQuery(this).is(':checked'))
				{
					//console.log( jQuery(this).val() ); 
					aExtVars[jQuery(this).attr('name')] = jQuery(this).val();
				}//서울시 마포구 창전동 태영아파트
			}
			else
			{ 
//console.log( 'other_name:'+jQuery(this).attr('name'));
//console.log( 'other_id:'+jQuery(this).attr('id'));
//console.log( 'value:'+typeof  jQuery(this).val());
				if(typeof jQuery(this).attr('name') !== 'undefined' && typeof jQuery(this).val() !== 'undefined' )
				{
//console.log( 'value:'+typeof  jQuery(this).val());
					if(jQuery(this).attr('name').indexOf('[]') > 0 )
					{
						if (typeof aExtVars[jQuery(this).attr('name')] !== 'undefined')
							var sTempVarVal = aExtVars[jQuery(this).attr('name')]+'|@|'+jQuery(this).val();
						else
							var sTempVarVal = jQuery(this).val();
//console.log( 'sTempVarVal:'+sTempVarVal);
						aExtVars[jQuery(this).attr('name')] = sTempVarVal;
					}
					else if(jQuery(this).val())
						aExtVars[jQuery(this).attr('name')] = jQuery(this).val();
				}
				//else if(jQuery(this).val())
				//{
//console.log( jQuery(this).val() ); 
//					aExtVars[jQuery(this).attr('name')] = jQuery(this).val();
//				}
			}
		}
	});
//console.log( 'aExtVars:');
//console.log(aExtVars);
	for (var sName in aExtVars) 
	{
		if( sName.indexOf('[]') > 0 )
		{
			var aTempVal = aExtVars[sName].split('|@|');
			if( aTempVal.length == 1 ) // 단일 원소 배열을 extravar 클래스가 처리 오류
				aExtVars[sName] = aTempVal[0];
			else
				aExtVars[sName] = aTempVal;
		}
	}
//console.log( 'aExtVars:');
//console.log(aExtVars);
	return aExtVars;
}

function doRegistration( sVirtualConversionPrefix ) 
{
	var params = new Array();
	if( jQuery('#applicant_name').length )
	{
		if( !jQuery('#applicant_name').val().length )
		{
			alert('신청자명을 입력해 주세요.');
			return;
		}
		params['applicant_name'] = jQuery('#applicant_name').val();
	}

	if( jQuery('#applicant_phone_number').length )
	{
		if( !jQuery('#applicant_phone_number').val().length )
		{
			alert('인증받은 전화번호를 입력해 주세요.');
			return;
		}
		jQuery('#applicant_phone_number').val().replace('-', '');
		params['applicant_phone_number'] = jQuery('#applicant_phone_number').val();
	}

	if( jQuery('#authcode').length )
	{
		if( !jQuery('#authcode').val().length )
		{
			alert('인증번호를 입력해 주세요.');
			return;
		}
		jQuery('#authcode').val().replace('-', '');
		params['authcode'] = jQuery('#authcode').val();
	}

	var timeFinalTime = _getCurrentDatetime();
	var bCollectionAgreement = jQuery('#privacy_collection').is(':checked') ? 1 : 0;
	var bSharingAgreement = jQuery('#privacy_sharing').is(':checked')? 1 : 0;
	
	params['module_srl'] = jQuery('#module_srl').val();
	params['privacy_collection'] = bCollectionAgreement;
	params['privacy_sharing'] = bSharingAgreement;
	params['timestamp_entry'] = g_timeEntryTime;
	params['timestamp_final'] = timeFinalTime;
	var aExtraVars = _getAllExtraVars();
	for(var sName in aExtraVars) 
	{
		var sTempName = sName.replace( "[]", ""); // param 인지가 XML로 전환될 때 param 이름에 []가 남아있으면 xml parser가 오류
		params[sTempName] = aExtraVars[sName];
	}

	//_disableBtn( '#btnSubmit' );
//console.log( params );

	var respons = ['cleee','result'];
	exec_xml('svdocs', 'procSvdocsRegistration', params, function(ret_obj) {
		if( ret_obj['message'] == 'success' )
		{
			_disableBtn( '#btnSubmit' );
			if( jQuery('#layer1').length ) // for pc
			{
				jQuery('#layer1').fadeOut(); // 신청양식 숨기기
				setTimeout("layer_open('layer_thankyou')", 500); 
			}
			else // for jquery mobile loaded page
			{
				jQuery('#layer_finish').fadeOut(); // 신청양식 숨기기
				setTimeout("layer_open('layer_thankyou')", 500); 
				//alert('신청해 주셔서 감사드립니다.1334');
			}
				
			if( typeof checkNonEcConversionGaectk === 'function' )
				checkNonEcConversionGaectk( '/'+sVirtualConversionPrefix+'.html', sVirtualConversionPrefix+' page' );
		}
		//var sDisctype = ret_obj['cleee'];
	},respons);
}

function doCertified( sVirtualConversionPrefix ) 
{
	var params = new Array();
	params['module_srl'] = jQuery('#module_srl').val();
	params['timestamp_entry'] = g_timeEntryTime;
	params['timestamp_final'] = _getCurrentDatetime();
//	var aExtraVars = _getAllExtraVars();
//	for(var sName in aExtraVars) 
//	{
//		var sTempName = sName.replace( "[]", ""); // param 인지가 XML로 전환될 때 param 이름에 []가 남아있으면 xml parser가 오류
//		params[sTempName] = aExtraVars[sName];
//	}
	var respons = ['cleee','result'];
	exec_xml('svdocs', 'procSvdocsCertified', params, function(ret_obj) {
		if( ret_obj['message'] == 'success' )
		{
			_disableBtn( '#btnSubmit' );
			if( jQuery('#layer1').length ) // for pc
			{
				jQuery('#layer1').fadeOut(); // 신청양식 숨기기
				setTimeout("layer_open('layer_thankyou')", 500); 
			}
			else // for jquery mobile loaded page
			{
				jQuery('#layer_finish').fadeOut(); // 신청양식 숨기기
				setTimeout("layer_open('layer_thankyou')", 500); 
				ddddd
			}
			if( typeof checkNonEcConversionGaectk === 'function' )
				checkNonEcConversionGaectk( '/'+sVirtualConversionPrefix+'.html', sVirtualConversionPrefix+' page' );
		}
		//var sDisctype = ret_obj['cleee'];
	},respons);
}

function _getCurrentDatetime()
{
	var currentdate = new Date();
	var timeFinalTime = currentdate.getFullYear() + '-' + (currentdate.getMonth()+1) + '-' + currentdate.getDate() + ' ' + currentdate.getHours() + ":" + currentdate.getMinutes() + ":" + currentdate.getSeconds();
	return timeFinalTime;
}

function getUrlVars()
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

function layer_open(el)
{
	var temp = jQuery('#' + el); //레이어의 id를 temp변수에 저장
	var bg = temp.prev().hasClass('bg'); //dimmed 레이어를 감지하기 위한 boolean 변수
	if(bg)
		jQuery('.layer').fadeIn();
	else
		temp.fadeIn();	//bg 클래스가 없으면 일반레이어로 실행한다.

	// 화면의 중앙에 레이어를 띄운다.
	//if (temp.outerHeight() < jQuery(document).height() ) temp.css('margin-top', '-'+temp.outerHeight()/2+'px');
	//else temp.css('top', '0px');
	temp.css('top', '60px');
	//temp.css('top', '100px');
	if (temp.outerWidth() < jQuery(document).width() ) temp.css('margin-left', '-'+temp.outerWidth()/2+'px');
	else temp.css('left', '0px');

	temp.find('a.cbtn').click(function(e){
		if(bg){
			jQuery('.layer').fadeOut();
		}else{
			temp.fadeOut();		//'닫기'버튼을 클릭하면 레이어가 사라진다.
		}
		e.preventDefault();
	});

	jQuery('.layer .bg').click(function(e){
		jQuery('.layer').fadeOut();
		e.preventDefault();
	});
}			

function _thankyou_facebook_user()
{
	var nModuleSrl = jQuery('#module_srl').val();
	var sGatkThankyouPageName = jQuery('#gatk_thankyou_page_name').val();
	if( typeof sGatkThankyouPageName == 'undefined' )
		checkNonEcConversionGaectk( '/mod_'+ nModuleSrl + '_thankyou.html', 'mod_' + nModuleSrl + '_thankyou_page' );
	else
	{
		if( sGatkThankyouPageName.length == 0 )
			checkNonEcConversionGaectk( '/mod_'+ nModuleSrl + '_thankyou.html', 'mod_' + nModuleSrl + '_thankyou_page' );
		else
			checkNonEcConversionGaectk( sGatkThankyouPageName + '.html', sGatkThankyouPageName );
	}
	//ga('send', 'pageview', {
	 // 'page': '/thankyou.html',
	  //'title': 'thankyou page'
	//});
	// CompleteRegistration
	// Track when a registration form is completed (ex. complete subscription, sign up for a service)

	if( typeof fbq != 'undefined' )
	{
		fbq('track', 'CompleteRegistration');
	}
}