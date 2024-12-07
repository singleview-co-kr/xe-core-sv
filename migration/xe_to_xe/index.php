<?php
/**
 * @brief xe export tool
 * @author zero (zero@xpressengine.com)
 **/
require_once('../lib.inc.php');
require_once('./zMigration.class.php');

// 사용되는 변수의 선언
$path = @$_POST['path'] ? @$_POST['path'] : str_replace('/migration/xe_to_xe','', getcwd());
include('../admin_html.php');
?>