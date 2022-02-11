<?php
class singleviewMsgProtocol
{
    private $_g_aMsg=array(
		'OK' => 1, # OK
		'FIN' => 2, # finish 
		'MIHY' => 3, # may i help you?
		'LMKL' => 4, # let me know latest data
		'IWSY' => 5, # I will send you
		'ALD' => 6, # add latest data
		# 'MTG' => 7, # more to go
		# 'IHND' => 8, # i have new data
		'IWWFY' => 9, # i will wait for you
		'IHNI' => 10, # i have no idea
		# 'RRC' => 11, # remaining record count
		'PUP' => 12, # Plz Update Period
		'LMKP' => 13, # Let me know Period
		'WLYK' => 14, # will Let you know
		# 'LMKL' => 1, # let me know latest data + data: requested sync date since
		# 'FIN' => 2, # finish 	
		# 'ALD' => 3, # add latest data + data: doc_srls + com_srls
		'GMDL' => 15, # give me document list  -> data: doc_srls
		'GMCL' => 16, # give me comment list  -> data: com_srls
		'HYA' => 17, # here you are -> data: text list
		);

/**
 * @brief
 */
	public function __construct()
	{
	}
/**
 * @brief
 */
	public function getMsgCode()
	{
		return $this->_g_aMsg;
	}
}
/* End of file b2c.php */
/* Location: ./modules/svestudio/sv_classes/svapi_msg_protocol.php */