var sCookieName = 'svcampaign';
function layer_open(el, nExpHrs)
{
	bCampaignDisplay = _getCookie( sCookieName );
	if( bCampaignDisplay != 'off' )
	{
		var temp = jQuery('#' + el);
		var bg = temp.prev().hasClass('bg');	//dimmed 레이어를 감지하기 위한 boolean 변수
		if(bg){
			jQuery('.layer').fadeIn();	//'bg' 클래스가 존재하면 레이어가 나타나고 배경은 dimmed 된다. 
		}else{
			temp.fadeIn();
		}
		// 화면의 중앙에 레이어를 띄운다.
		if (temp.outerHeight() < jQuery(document).height() ) temp.css('margin-top', '-'+temp.outerHeight()/2+'px');
		else temp.css('top', '0px');
		if (temp.outerWidth() < jQuery(document).width() ) temp.css('margin-left', '-'+temp.outerWidth()/2+'px');
		else temp.css('left', '0px');

		sendDisplayEventGaectk( 'svtracker_layer_popup' );
		temp.find('a.cbtn').click(function(e){
			if(bg){
				jQuery('.layer').fadeOut(); //'bg' 클래스가 존재하면 레이어를 사라지게 한다. 
			}else{
				temp.fadeOut();
			}
			sendClickEventGaectk( 'button', 'svtracker_layer_popup_close', '#' );
			_setCookie( sCookieName, 'off', nExpHrs );
			e.preventDefault();
		});

		temp.find('a.ccta_btn').click(function(e){
			if(bg){
				jQuery('.layer').fadeOut(); //'bg' 클래스가 존재하면 레이어를 사라지게 한다. 
			}else{
				temp.fadeOut();
			}
			sendClickEventGaectk( 'button', 'svtracker_layer_popup_cta', '#' );
			_setCookie( sCookieName, 'off', nExpHrs );
		});

		jQuery('.layer .bg').click(function(e){	//배경을 클릭하면 레이어를 사라지게 하는 이벤트 핸들러
			jQuery('.layer').fadeOut();
			e.preventDefault();
		});
	}
}

function _setCookie( cname, cvalue, nExpHrs )
{
	if( nExpHrs )
	{
		var d = new Date();
		d.setTime( d.getTime() + nExpHrs*3600000 ); //60*60*1000
		var expires = 'expires=' + d.toUTCString();
		document.cookie = cname + '=' + cvalue + '; ' + expires;
	}
	else 
		document.cookie = cname + '=' + cvalue
}

function _getCookie( cname )
{
	var name = cname + '=';
	var ca = document.cookie.split( ';' );
	for( var i=0; i<ca.length; i++ ) 
	{
		var c = ca[i];
		while( c.charAt(0)==' ' ) 
			c = c.substring(1);
		if( c.indexOf(name) == 0 )
			return c.substring(name.length, c.length);
	}
	return '';
}