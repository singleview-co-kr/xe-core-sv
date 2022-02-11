<?php
if (!defined("__XE__")) exit();

/**
 * @file: stop_spambot_xe.addon.php
 * @author: KSChung(http://chungfamily.woweb.net/)
 * @brief: protect spambot for a particular action
 * */
$ver = 'Stop_spambot(V2.8)';
$int_01 = 123581321;
$token = time() + $int_01;
$mask_base = $addon_info->mask_text._XE_PATH_;
$mask = $mask_base.$token;
$name_text = "c".md5($mask);
$value = md5($name_text);
$delay = (int)$addon_info->delay;
$notice_msg = '';

if (!$delay)
{
	$delay = 120;
}
$form_id = "";
$str = "";
$act = Context::get('act');

if ($act == 'dispMemberSignUpForm' || $act == 'dispMemberModifyInfo')
{
	$form_id = "form#fo_insert_member";
	$str = "insertMember";
} 
elseif ($act == 'dispBoardWrite') 
{
	$form_id = "form#fo_write";
	$str = "insert";
}
elseif (Context::get('document_srl')) 
{
	$form_id = "form.boardEditor";
	$str = "insert_comment";
}
elseif (Context::get('listStyle')=='planner_weekly') 
{
	$form_id = "form#fo_week";
}
if ($called_position == 'before_module_proc')
{
	// 1st(spam-bot check)
	if ($this->act == 'procMemberInsert' || $this->act == 'procBoardInsertDocument' || $this->act == 'procBoardInsertComment' )
	{
		$ss_cp_in = Context::get('ss_cp');
		$pin_in = Context::get('pin_22');
		$pin = floor($pin_in/1000);
		$token_in = (int)Context::get('token_1l');
		$mask2 = $mask_base.$token_in;
		$name_text2 = "c".md5($mask2);
		$value2 = md5($name_text2);
		if (!class_exists('BaseObject') && class_exists('Object'))
		{
			class_alias('Object', 'BaseObject'); // for old XE
		}

		if (Context::get($name_text2) != $value2 || $pin_in < 1500 || !($token_in + $pin >= $token - $delay && $token_in + $pin <= $token +1 ) )
		{
			if ($this->act == 'procMemberInsert')
			{
				$user_id = '가입시도_id: '.Context::get('user_id');
				$act_type = ' (회원가입) ';
				$sub_title = '(변수,가입)';
				header("HTTP/1.1 404 Not Found");
				if ($addon_info->redirect)
				{
					header('Location: '.$addon_info->redirect);
				}
				else
				{
					$redirect = 'https://www.google.com/search?q=spam&dcr=0&source=lnms&tbm=isch&sa=X&ved=0ahUKEwiQs5-n9tfZAhVJqI8KHe0JB-4Q_AUICigB&biw=1536&bih=734';
					header('Location: '.$redirect);
				}
			}
			else
			{
				$user_id = $logged_info->user_id;
				$board_title = '(게시판: '.$this->module_info->browser_title.') ';
				$sub_title = '(변수,쓰기)';
				if ($this->act == 'procBoardInsertDocument')
				{
					$act_type = ' (문서등록) ';
					$doc_title = ' (문서제목: '.Context::get('title').') ';
				}
				elseif ($this->act == 'procBoardInsertComment') 
				{
					$act_type = ' (댓글등록) ';
					$doc_title = '';
				}
				$message = $addon_info->message_text;
				if (!$message) 
				{
					$message = "Hey buddy, don't try to fool us!";
				}
				$output = new BaseObject(-1, $message);
				$oDisplayHandler = new DisplayHandler();
				$oDisplayHandler->printContent($output);
			}
			if ($addon_info->mgr_id)
			{
				$browser = ' ('.$_SERVER['HTTP_USER_AGENT'].')';
				$ip_arr = explode('.', $_SERVER['REMOTE_ADDR']);
				$oMemberModel = getModel('member');
				$mgr_info = $oMemberModel->getMemberInfoByUserID($addon_info->mgr_id);
				$sender_srl = $mgr_info->member_srl;
				$member_srl = $mgr_info->member_srl;
				$title = $ver.' '.date("y-m-d H:i:s").' 검출'.$sub_title.$ip_arr[0];
				$notice_msg .= '변수불일치(스팸봇?)  Login_ID:('.$user_id.'),  IP:('.$_SERVER['REMOTE_ADDR'].') '.$act_type.$browser.$board_title.$doc_title;
				$oCommunicationController = getController('communication');
				$oCommunicationController->sendMessage($sender_srl, $member_srl, $title, $notice_msg, false);
			}
			Context::Close();
			exit();
		}
	}

	// 2nd(manual-input check)
	if (!$this->grant->manager && Context::get('signature') && strpos($addon_info->signature_url, "remove_tag") !== false)
	{
		$str_signature = Context::get('signature'); //회원정보의 서명(V2.5 추가)
		$tag = 'a';
		if($addon_info->signature_url == "remove_tag")
		{
			$str_signature = preg_replace('/<\\/?' . $tag . '(.|\\s)*?>/', '', $str_signature); //remove <a> tag 
		} 
		elseif($addon_info->signature_url == "remove_tag_and_content") 
		{
			$str_signature = preg_replace("#\<".$tag."(.*)/".$tag.">#iUs", "", $str_signature); //remove <a> tag with content
		}
		Context::set('signature', $str_signature);
	}
	if (!$this->grant->manager && ($this->act == 'procBoardInsertDocument' || $this->act == 'procBoardInsertComment'))
	{
		$_content = Context::get('content');
		if (!strlen($_content)) return;

		if ($addon_info->max_count == '' || $addon_info->max_count == 0)
		{
			$max_cnt = 6;
		}
		else
		{
			$max_cnt = $addon_info->max_count;
		}
		if ($_COOKIE['_ssCnt'] >= $max_cnt) 
		{
			$output = new BaseObject(-1, 'Warning!\n사용 금지된 단어의 등록시도가 '.$max_cnt.'회 이상 있었습니다.\n브라우저를 다시 시작하세요.');
			$oDisplayHandler = new DisplayHandler();
			$oDisplayHandler->printContent($output);
			Context::close();
			exit();
		}

		// add click position for macro (V2.6)
		if ($addon_info->max_click == '' || $addon_info->max_click == 0)
		{
			$max_click = 4;
		}
		else
		{
			$max_click = $addon_info->max_count;
		}
		$tmp_ss_cp = $_COOKIE['_ssCP'.$ss_cp_in] + 1;
		setcookie('_ssCP'.$ss_cp_in, $tmp_ss_cp, 0,'/');
		if ($_COOKIE['_ssCP'.$ss_cp_in] >= $max_click) 
		{
			$output = new BaseObject(-1, 'Warning!\n 매크로로 의심되는 등록시도가 '.$max_click.'회 이상 있었습니다.\n브라우저를 다시 시작하세요.');
			$oDisplayHandler = new DisplayHandler();
			$oDisplayHandler->printContent($output);
			Context::close();
			exit();
		}
		if ($_COOKIE['_ssCP'.$ss_cp_in] >= 3) 
		{
			$ind_CP = 'Y';
			$notice_msg .= '매크로 의심(3회 시도), ';
		}

		// css
		if ($addon_info->target_css != 'none')
		{
			$tmp_content = html_entity_decode($_content);
			$cls_name = '(ui-helper-hidden|ui-datepicker|wfsr|sound_only|display-none)';
			$_style = '(display\s*:\s*none|visibility\s*:\s*hidden)';
			//if (preg_match('/\<.*\s+class\s*=.*?'.$cls_name.'\s?.*?\>(.*?)\<a\s+(.*?)(https?:\/\/)/is', $tmp_content))//링크 있으면
			if (preg_match('/\<.*\s+class\s*=.*?'.$cls_name.'\s*.*?\>(.*?)/is', $tmp_content))//링크 없어도
			{
				$ind_css = 'Y';//(V1.4)추가
				$notice_msg .= 'CSS코드검출됨, ';
			} 
			elseif (preg_match('/\<.*\s+style\s*=.*?'.$_style.'\s*.*?\>(.*?)\<\s*a\s+(.*?)(https?:\/\/)/is', $tmp_content))
			{
				$ind_css = 'Y';//(V1.4)추가
				$notice_msg .= 'CSS코드검출됨, ';
			}
		}

		// empty <a>tag (V2.3)추가
		if ($addon_info->empty_tag != 'none')
		{
			$tmp_content = preg_replace('/[\x00-\x1F\x7F]/', '', html_entity_decode($_content));
			$dom = new DOMDocument;
			@$dom = DOMDocument::loadHTML('<?xml encoding="utf-8" ?>' . $tmp_content);
			$links = $dom->getElementsByTagName('a');
			if (count($links))
			{
				foreach ($links as $link)
				{
					if (!trim($link->nodeValue))
					{
						$ind_empty_tag = 'Y';
						$notice_msg .= '내용없는 "a" 태그 검출됨, ('.$link->getAttribute('href').' '.$link->getAttribute('title').'), ';
						break;
					}
				}
			}
		}

		//스팸필터및 한글조건 검토 대상 사용자(V1.2추가)
		if ($addon_info->target != 'none' || $addon_info->target != '')
		{
			if (($addon_info->target == 'none_user' && !$logged_info->member_srl) || ($addon_info->target == 'none_mgr' && !$this->grant->manager))
			{
				$target_user = 'Y'; 
			}
		}
		if ($target_user == 'Y') //검토 대상자인 경우 실행
		{
			//hangul
			$no_hangul_option = $addon_info->no_hangul_option;
			if ($no_hangul_option != 'allow' && $no_hangul_option != '')
			{
				if (!preg_match('/[ㄱ-ㅣ가-힣]/u', $_content))
				{
					if ($no_hangul_option == 'notallow_link' && preg_match('#<a\s|https?://#is', $_content))
					{
						$ind_hg_spam = 'Y';//한글이 없으면서 링크가 있으면 스팸처리
						$notice_msg .= '한글조건검출됨, ';
					}
					elseif ($no_hangul_option == 'notallow')
					{
						$ind_hg_spam = 'Y';//한글이 없으면 스팸처리
						$notice_msg .= '한글조건검출됨, ';
					}
				}
			}

			//key word
			$spam_word_arr = explode(',', preg_replace('/[^A-Za-zㄱ-ㅣ가-힣0-9,]/', '', $addon_info->spam_word));
			if (strlen($spam_word_arr[0]))
			{
				$_content = strip_tags($_content);//html 태그제거
				$_content = preg_replace('/&nbsp;/', '', $_content);//html 엔티티를 문자로 바꾼 후 공란제거
				$_content = preg_replace('/\s+/', '', html_entity_decode($_content));//html 엔티티를 문자로 바꾼 후 공란제거
				foreach($spam_word_arr as $key => $value) 
				{
					$pattern = '';
					$first = substr($value, 0, 1);
					$last = substr($value, -1);
					if ($first == '/' && $last == '/') { //스팸키워드가 '/'로 시작해서 '/'로 끝나면 정규식으로 간주 
						$pattern = $value;
						if ($value && preg_match($value.'is', $_content))
						{ 
							$ind_word_spam = 'Y';//스팸단어가 있으면 스팸처리
							$notice_msg .= '키워드검출됨('.$value.'), ';
							break;
						}
					} else {
						if (preg_match('/[ㄱ-ㅣ가-힣]/u', $value)) {
							$pattern .= 'ㄱ-ㅣ가-힣';
						}
						if (preg_match('/[A-Za-z]/', $value)) {
							$pattern .= 'A-Za-z';
						}
						if (preg_match('/[0-9]/', $value)) {
							$pattern .= '0-9';
						}
						$pattern = '/[^'.$pattern.']/';
						$tmp_content = preg_replace($pattern, '', $_content);//알파벳, 한글, 숫자 이외의 문자제거
						if ($value && preg_match('/'.preg_quote($value,'/').'/is', $tmp_content))
						{ 
							$ind_word_spam = 'Y';//스팸단어가 있으면 스팸처리
							$notice_msg .= '키워드검출됨('.$value.'), ';
							break;
						}
					}

				}
			}
		}
	}
	if ($ind_hg_spam == 'Y' || $ind_word_spam == 'Y' || $ind_css == 'Y' || $ind_empty_tag == 'Y' || $ind_CP == 'Y')
	{
		$ssCnt = $_COOKIE['_ssCnt'] + 1;
		setcookie('_ssCnt', $ssCnt, 0,'/');
		if ($ind_CP == 'Y') {
			$output = new BaseObject(-1, $_SERVER['REMOTE_ADDR'].'\n매크로 사용을 즉시 중지하시기 바랍니다.');
		} else {
			$output = new BaseObject(-1, '" "'.'는 사용 금지된 단어입니다');
		}
		$oDisplayHandler = new DisplayHandler();
		$oDisplayHandler->printContent($output);
		if ($addon_info->mgr_id)
		{
			$browser = ' ('.$_SERVER['HTTP_USER_AGENT'].')';
			$ip_arr = explode('.', $_SERVER['REMOTE_ADDR']);
			$sub_title = '(필터,쓰기)';
			if ($this->act == 'procBoardInsertDocument')
			{
				$act_type = ' (문서등록) ';
				$doc_title = ' (문서제목: '.Context::get('title').') ';
			}
			elseif ($this->act == 'procBoardInsertComment') 
			{
				$act_type = ' (댓글등록) ';
				$doc_title = '';
			}
			$board_title = '(게시판: '.$this->module_info->browser_title.')';
			$oMemberModel = getModel('member');
			$mgr_info = $oMemberModel->getMemberInfoByUserID($addon_info->mgr_id);
			$sender_srl = $mgr_info->member_srl;
			$member_srl = $mgr_info->member_srl;
			$title = $ver.' '.date("y-m-d H:i:s").' 검출'.$sub_title.$ip_arr[0];
			$notice_msg .= '  Login_ID:('.$logged_info->user_id.'),  IP:('.$_SERVER['REMOTE_ADDR'].') '.$act_type.$browser.$board_title.$doc_title;
			$oCommunicationController = getController('communication');
			$oCommunicationController->sendMessage($sender_srl, $member_srl, $title, $notice_msg, false);
		}
		Context::close();
		exit();
	}
}

if ($called_position == 'before_display_content' && $form_id != "")
{

	if (!isset($_COOKIE['_ssCnt'])) 
	{
		setcookie('_ssCnt', 0, 0, '/');
	}

	// add button animation for macro(V2.6)
	if ($addon_info->animation != 'N' && Mobile::isFromMobilePhone() === false)
	{
		if ($addon_info->animation == 'Y' || $addon_info->animation == '') {
			$ind_member = 'Y';
			$ind_board = 'Y';
		} elseif ($addon_info->animation == 'M') { 
			$ind_member = 'Y';// member only
			$ind_board = '';
		} elseif ($addon_info->animation == 'B') { 
			$ind_member = '';
			$ind_board = 'Y';//board only
		}

		$str_submit_border = '';
		if ($addon_info->btn_border == 'N' || $addon_info->btn_border == '' ) {
			$ind_btn_border = '';
		} elseif ($addon_info->btn_border == 'random' ) {
			$ind_btn_border = 'R';
		} else {
			$ind_btn_border = $addon_info->btn_border;
			//$str_submit_border = 'border: 1px '.$addon_info->btn_border.' solid';
		}

		$lang_type =  Context::get('lang_type');
		if ($lang_type == 'ko') {
			$cmd_back = '이전화면';
			$cmd_submit = '등록';
		} else {
			$cmd_back = 'Previous Page';
			$cmd_submit = 'Submit';
		}
		$css_btn_ss ='<style type="text/css">.btn_ss {display: inline-block;*display: inline;margin: 0;padding: 0 12px!important;height: 24px!important;overflow: visible;border: 1px solid #bbb;border-color: #e6e6e6 #e6e6e6 #bfbfbf;border-color: rgba(0,0,0,.1) rgba(0,0,0,.1) rgba(0,0,0,.25);border-bottom-color: #a2a2a2;border-radius: 2px;text-decoration: none!important;text-align: center;text-shadow: 0 1px 1px rgba(255,255,255,.75);vertical-align: top;line-height: 24px!important;font-family: inherit;font-size: 12px;color: #333;*zoom: 1;cursor: pointer;box-shadow: inset 0 1px 0 rgba(255,255,255,.2), 0 1px 2px rgba(0,0,0,.05);background-color: #f5f5f5;*background-color: #e6e6e6;background-image: -moz-linear-gradient(top,#fff,#e6e6e6);background-image: -webkit-linear-gradient(top,#fff,#e6e6e6);background-image: -webkit-gradient(top,#fff,#e6e6e6);background-image: -o-linear-gradient(top,#fff,#e6e6e6);background-image: linear-gradient(top,#fff,#e6e6e6);background-repeat: repeat-x;filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#ffffff", endColorstr="#e6e6e6", GradientType=0);filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);}</style>';// XEDITION btn 충돌방지를 위해 ㅜㅜ

	$form_jS_2 = '
	<script type="text/javascript">
	//<![CDATA[
	jQuery(function($) 
	{
		var cmd_back = "'.$cmd_back.'";
		var cmd_submit = "'.$cmd_submit.'";
		var ind_member = "'.$ind_member.'";
		var ind_board = "'.$ind_board.'";
		var ind_btn_border = "'.$ind_btn_border.'";

		$(document).ready(function() 
		{
			$("form").each(function()
			{
				var tmp_str = $(this).attr("onsubmit");
				if (tmp_str)
				{
					if (tmp_str.indexOf("insert_comment") > -1)
					{
						var ind_01 = "comment";
					}
					if (tmp_str.indexOf("window.insert") > -1)
					{
						var ind_01 = "write";
					}
				}
				if ($(this).attr("id") == "fo_insert_member")
				{
					var ind_01 = "member";
				}

				if (ind_01 == "comment" && ind_board == "Y" || ind_01 == "write" && ind_board == "Y" || ind_01 == "member" && ind_member == "Y")
				{
					var tmp_str = $(this).find(":submit").attr("class");
					if (tmp_str)
					{
						if (tmp_str.indexOf("btn_insert") > -1)
						{
							var str_class = "";
						} else {
							var str_class = "btn";
						}
					} else {
						var str_class = "btn";
					}

					if (ind_01 == "comment")
					{
						var node_btn = $(this).find(":submit");
						var node_btn_parent = node_btn.parent();
						var tmp_class = node_btn.closest("div").attr("class");
						if (tmp_class == "boardNavigation" || tmp_class == "btnArea")
						{
							if (node_btn_parent[0].nodeName == "SPAN")
							{
								node_btn_parent.attr("class", "willberemove");
							} else {
								node_btn.attr("class", "willberemove");
							}
							node_btn.closest("div").append(\'<input type="submit" value="\'+cmd_submit+\'"><button type="button" style="position:relative; top:0px left:0px;" onclick="history.back()">\'+cmd_back+\'</button>\');
							var tmp_width = node_btn.closest("div").width() - 50;
						} else {
							if (node_btn_parent[0].nodeName == "SPAN")
							{
								node_btn_parent.attr("class", "willberemove");
							} else {
								node_btn.attr("class", "willberemove");
							}
							$(this).append(\'<div id="ss_comment_div"style="width:100%"><input type="submit" value="\'+cmd_submit+\'"><button type="button" style="position:relative; top:0px left:0px;" onclick="history.back()">\'+cmd_back+\'</button></div>\');
							var tmp_width = $("#ss_comment_div").width() - 70;
						}
						$(".willberemove").remove();
					}

					if (ind_01 == "write")
					{
						var tmp_width = $(this).width() - 180;
						$(this).find(":submit").closest("div").append(\'<button type="button" style="position:relative; top:0px left:0px;" onclick="history.back()">\'+cmd_back+\'</button>\');
					}

					if (ind_01 == "member")
					{
						var tmp_width = $(this).width() - 180; 
						var element_str = $(this).find("a.btn").addClass("btn_ss");
						var element_str = $(this).find("a.btn").removeClass("btn");
						var element_str = $(this).find("a.btn_ss").prop("outerHTML");
						$(this).find("a.btn_ss").replaceWith( \'<button type="button">\'+element_str+\'</button>\' );
						$(this).find(":submit").closest("div").append(\'<button type="button" style="position:relative; top:0px left:0px;" onclick="history.back()">\'+cmd_back+\'</button>\');
					}

					// button_area
					var ss_btn_area = $(this).find(":submit").closest("div");
					ss_btn_area.css({background: "#f6f6f6", border: "1px #ddd solid", float: "none", height: "100px", margin: "5px 0", padding:"0px", overflow:"hidden"});
					ss_btn_area.css("text-align", "left");
					var tmp_height = ss_btn_area.height();

					// button
					var tmp_forms = this;
					var cntX = 0;
					ss_btn_area.find(":submit, :button").each(function(){
						var tmp_node_parent = $(this).parent();
						if (tmp_node_parent[0].nodeName == "SPAN")
						{
							tmp_node_parent.remove();
							ss_btn_area.append(this);
						}
						$(this).css({position:"relative", float: "none", top:"0px", left:"0px"});
						cntX += 1;
						var tmp_p = $(this).position();
						var tmp_left = tmp_p.left;
						if ($(this).attr("type") == "submit") {
							$(this).css({position:"relative", float: "none", left: "-"+tmp_left+"px"});
							if (ind_btn_border && ind_btn_border != "R") {
								$(this).css({border: "1px " +ind_btn_border+ " solid"});
							}						
						} else {
							$(this).css({position:"relative", float: "none", left: "-"+tmp_left+"px"});
						}
						$(this).removeClass("btn btn_insert btn-inverse blue ab-btn ab-point-color");
						$(this).addClass("btn_ss " + str_class);
						$(this).addClass(" ss_button_" + cntX);
						animateDiv(".ss_button_"+ cntX, tmp_height, tmp_width);
					});
					$(".willberemove").remove();
				}
			});
		});

		function makeNewPosition(h,w){
			var h = h - 20;
			var w = w - 20;
			var nh = Math.floor(Math.random() * h);
			var nw = Math.floor(Math.random() * w);
			return [nh,nw];    
		}

		function animateDiv(myclass,h,w){
			var back = ["#2300cf","#cf5505","#ff00ff","	#599969","#000000","#FF5733","#ad1976","#951540","#1595bf","#f86a08","#00c3e3","#ffce26","#40bf95"];
			var rand = back[Math.floor(Math.random() * back.length)];
			$(myclass).css({color: rand});
			if (ind_btn_border == "R") {
				$(myclass).css({border: "1px "+rand+" solid"});
			}
			var newq = makeNewPosition(h,w);
			$(myclass).animate({ top: newq[0], left: newq[1] }, 5000, function(){
				animateDiv(myclass,h,w);        
			});
		};
	});
	//]]>
	</script>
	';
	}

	$form_jS = '
	<script type="text/javascript">
	//<![CDATA[
	jQuery(function($) 
	{
		$(document).ready(function() 
		{
			$("input:text").keydown(function (key) {
				if (key.keyCode == 13){ //enter
					key.preventDefault(); // donot submit form
				}
			});
			$("#WzTtDiV_ss").text($.now());
			$(":submit").mousedown(function(e) 
			{
				if (!$("input[name='.$name_text.']").val() && !$("input[name=token_1l]").val() && e.pageX && e.pageY) 
				{
					var val_ss_cp = e.pageX*e.pageX + "" + e.pageY*e.pageY;
					if ($("'.$form_id.'").length > 0){
						$("'.$form_id.'").append(\'<input type="hidden" name="'.$name_text.'" value=""/>\');
						$("'.$form_id.'").append(\'<input type="hidden" name="token_1l" value="'.$token.'"/>\');
						$("'.$form_id.'").append(\'<input type="hidden" name="pin_22" value=""/>\');
						$("'.$form_id.'").append(\'<input type="hidden" name="ss_cp" value=""/>\');
						$(this).closest("form").children("input[name='.$name_text.']").val("'.$value.'");
						$("'.$form_id.'").children("input[name=pin_22]").val( $.now()-$("#WzTtDiV_ss").text() );
						$("'.$form_id.'").children("input[name=ss_cp]").val(val_ss_cp);
					}
					else
					{
						$("form").each(function()
						{
						//var txt = $(this).attr(\'onsubmit\'); 
						//if (/'.$str.'/i.test(txt))
						//{
							$(this).append(\'<input type="hidden" name="'.$name_text.'" value=""/>\');
							$(this).append(\'<input type="hidden" name="token_1l" value="'.$token.'"/>\');
							$(this).append(\'<input type="hidden" name="pin_22" value=""/>\');
							$(this).append(\'<input type="hidden" name="ss_cp" value=""/>\');
							$(this).children("input[name='.$name_text.']").val("'.$value.'");
							$(this).children("input[name=pin_22]").val( $.now()-$("#WzTtDiV_ss").text() );
							$(this).children("input[name=ss_cp]").val(val_ss_cp);
						//}
						});
					}
				}
			});
		});
	});
	//]]>
	</script>
	';

	Context::addHtmlHeader('<div id="WzTtDiV_ss" style="visibility:hidden; position: absolute; overflow: hidden; padding: 0px; width: 0px; left: 0px; top: 0px;"></div>'.$css_btn_ss);
	Context::addHtmlFooter($form_jS_2.$form_jS);
	unset($form_jS, $form_jS_2, $name_text, $value, $form_id, $message, $token, $tmp_content, $css_btn_ss);
}
/* End of file stop_spambot_xe.addon.php */
/* Location: ./addons/stop_spambot_xe/stop_spambot_xe.addon.php */
