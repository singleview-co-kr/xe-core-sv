<?php if(!defined("__XE__")) exit();
$nDocSrl = $aParam[0];
if(count($aParam[1]))
	$sInStmt = 'and member_srl in ('.implode(',', $aParam[1]).')';
else
	$sInStmt = '';

$sSqlRaw = "SELECT `content`
FROM `comments`
WHERE `document_srl` = %s AND `parent_srl` = 0 %s";
$sSql = sprintf($sSqlRaw, $nDocSrl, $sInStmt);