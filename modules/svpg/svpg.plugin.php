<?php
/**
 * @class svpgPlugin
 * @author singleview(root@singleview.co.kr)
 * @brief plugin abstract class
 **/
class SvpgPlugin extends svpg 
{
	function SvpgPlugin() { }
	function getFormData() { }
	function processPayment() { }
	function processReview() { }
	function processReport() { }
	function getReceipt($pg_tid, $paymethod = NULL) { return Context::getLang('unable_to_issue_receipt'); }
	function getReport() { }
	function dispExtra1(&$svpgObj) { }
	function dispExtra2(&$svpgObj) { }
	function dispExtra3(&$svpgObj) { }
	function dispExtra4(&$svpgObj) { }
	function procExtra1() { }
	function procExtra2() { }
	function procExtra3() { }
	function procExtra4() { }
	function dispEscrowDelivery() { return "<script>alert('에스크로를 지원하지 않는 결제건 입니다.');window.close();</script>"; }
	function dispEscrowConfirm() { return "<script>alert('에스크로를 지원하지 않는 결제건 입니다.');window.close();</script>"; }
	function procEscrowDelivery() { }
	function procEscrowConfirm() { }
}
/* End of file svpg.plugin.php */
/* Location: ./modules/svpg/svpg.plugin.php */