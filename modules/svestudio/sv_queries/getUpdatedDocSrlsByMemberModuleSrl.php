<?php if(!defined("__XE__")) exit();
$sBeginYyyymm = $aParam[0];
$sEndYyyymm = $aParam[1];
if(count($aParam[2]))
	$sInStmt = 'and member_srl not in ('.implode(',', $aParam[2]).')';
else
	$sInStmt = '';

if(count($aParam[3]))
	$sInStmt .= ' and module_srl in ('.implode(',', $aParam[3]).')';

$sSqlRaw = "SELECT `document_srl`
FROM `documents`
WHERE `regdate` >= %s and `regdate` <= %s %s";
$sSql = sprintf($sSqlRaw, $sBeginYyyymm, $sEndYyyymm, $sInStmt);