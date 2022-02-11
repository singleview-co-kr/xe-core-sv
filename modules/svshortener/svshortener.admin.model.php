<?php
/**
 * @class  svdocsAdminModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svdocsAdminModel
**/ 
class svshortenerAdminModel extends svshortener
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 *
	 */
	public function getMaxIndex()
	{
		$output = executeQueryArray('svshortener.getSvShortenersMaxIndex' );

		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svshortener_db_query');

		if( count( $output->data ) == 0 )  // 최초 입력시 ++인덱스가 0이 되도록
			return -1;

		foreach( $output->data as $key => $val )
			return $val->shortener_srl;
	}

	/**
	 *
	 */
	public function isExistingUriValue( $sQueryValue )
	{
		$args->shorten_uri_value = $sQueryValue;
		$output = executeQueryArray('svshortener.getSvshortenersUriValue', $args );

		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svshortener_db_query');

		if( count( $output->data ) == 0 )  // 새로운 uri value이면
			return false;
		else
			return true;
	}
}
/* End of file board.admin.model.php */
/* Location: ./modules/board/board.admin.model.php */