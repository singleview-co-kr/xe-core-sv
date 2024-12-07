<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="ko" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="generator" content="XpressEngine (http://www.xpressengine.com)" />
	<meta http-equiv="imagetoolbar" content="no" />
	<title>XE Data export tool ver 0.7</title>
	<style type="text/css">
		html, body {padding:0; margin:0;}
		html {background:#1F1F1F;}
		body {}
		body { font-family:arial; font-size:9pt; }
		input.input_text { width:400px; }
		blockquote.errMsg { color:red; }
		select.module_list { display:block; width:500px; }

		.content {width:940px; margin:0 auto; color:#FFF; margin-top:20px; font-size:14px;}
		.content>h1:first-child {background:#333; margin:0; padding:0px 16px 0px 16px; height:48px; line-height:48px; border-radius:6px 6px 0px 0px;}
		.content>.hr {display:block; height:2px; background:#222; border-left:2px solid #333; border-right:2px solid #333;}
		.content h3 {background:#210; margin:0; padding:10px 16px; color:#A73;}

		.content>form {position:relative; display:block; border:2px solid #333; background:#333;}
		.content>form::before,
		.content>form::after {display:block; height:0; clear:both; content:" ";}
		.content>address {padding:10px 16px 10px 16px; background:#333; border-radius:0px 0px 6px 6px;}
		.content>.cov {background:#333; padding:20px;}
		.content>.cov>.errMsg {border:1px solid #711; padding:12px 16px 12px 16px; color:#A33; background:#200; text-align:center; margin:0;}

		.checktitle,
		.license_notice,
		.md {padding:10px; border-radius:2px; border:1px solid #35373F; margin-bottom:10px; margin-top:10px; background:rgb(36, 37, 40);}
		.reqitem {padding:5px; padding-left:10px; color:#777;}
		.md {color:#AAA;}
		.md>h1,
		.md>h2 {border-bottom:1px solid #555; padding-bottom:5px;}
		.md li>h1 {font-size:1em;}
		.md a {text-decoration:none; color:#0AF;}
		.md>*:first-child {margin-top:0;}
		.fxname {padding-left:10px; color:#EEE;}
		.OK {display:inline-block; border:1px solid #171; padding:2px 6px 2px 6px; border-radius:2px; color:#3A3; background:#020; width:80px; text-align:center;}
		.WARN {display:inline-block; border:1px solid #741; padding:2px 6px 2px 6px; border-radius:2px; color:#A73; background:#210; width:80px; text-align:center;}
		.FAILED {display:inline-block; border:1px solid #711; padding:2px 6px 2px 6px; border-radius:2px; color:#A33; background:#200; width:80px; text-align:center;}
		.phpinfo {display:inline-block; float:right; color:#FFF; text-decoration:none; border:1px solid #35373F; padding:0px 10px 0px 10px; margin-top:-5px; margin-right:-5px; height:28px; box-sizing:border-box; border-radius:2px; font-size:12px; line-height:26px;}
		/* input[type=submit] {box-sizing:border-box; border:1px solid rgba(247,175,62,.68); background:rgba(247,175,62,.07); display:block; width:100%; color:#FFF; padding:10px; margin-bottom:10px; border-radius:2px;} */
		.checktitle {position:relative;}
		.checktitle form {display:inline;}
		.checktitle form::after {display:inline-block; clear:both; content:" "; margin-right:10px;}
		.checktitle input[type=submit] {float:left; display:inline-block; padding:0px 8px 0px 8px; height:28px; margin:0; margin-top:-5px; margin-left:-5px; width:auto;}
		.warn {border:1px solid #741; padding:2px 6px 2px 6px; border-radius:2px; color:#A73; background:#210; text-align:center; margin-bottom:10px;}
		.progress {border:1px solid rgba(0, 127, 255, .5); background:rgba(0, 127, 255, .2); height:24px; border-radius:2px; position:relative;}
		.progress::after {display:block; color:#FFF; height:24px; line-height:24px; content:attr(data-file); text-align:center; position:absolute; width:100%; top:0px;}
		.progressbar {height:24px; background:rgb(0, 127, 255); width:0%;}
        li > a {color:#fff;}
	</style>
</head>
<body>
	<div class="content">
	<h1>xe data export tool ver 0.8</h1>
	
	<div class="hr"></div>
	<form action="./index.php" method="post">
		<h3>추출 목적 선택</h3>
		<ul>
			<li><a href='./xe_to_xe/index.php'>xe 게시판 모듈로 입력하려고 합니다.</a></li>
            <li><a href='./xe_to_wp_x2b/index.php'>WordPress의 x2b 플러그인으로 입력하려고 합니다.</a></li>
		</ul>
	</form>
	<div class="hr"></div>
	<address>
		powered by zero (xpressengine.com)
	</address>
	</div>
</body>
</html>