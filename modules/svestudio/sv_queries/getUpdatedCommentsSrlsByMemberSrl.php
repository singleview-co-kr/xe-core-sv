<?php if(!defined("__XE__")) exit();
$sBeginYyyymm = $aParam[0];
$sEndYyyymm = $aParam[1];
if(count($aParam[2]))
	$sInStmt = ' and member_srl not in ('.implode(',', $aParam[2]).')';
else
	$sInStmt = '';

$sSqlRaw = "SELECT `comment_srl`
FROM `comments`
WHERE `regdate` >= %s and `regdate` <= %s %s";
$sSql = sprintf($sSqlRaw, $sBeginYyyymm, $sEndYyyymm, $sInStmt);