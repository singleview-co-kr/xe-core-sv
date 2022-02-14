<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nucommon
 * @author NURIGO(contact@nurigo.net)
 * @brief  nucommon
 */ 
class nucommon
{
	/**
	 * @brief parse an xml file and generate administrator's menus.
	 */
	function getMenu(&$in_xml_obj, $depth=0,&$parent_item=null) 
	{
		if(!is_array($in_xml_obj)) 
		{
			$xml_obj = array($in_xml_obj);
		} else {
			$xml_obj = $in_xml_obj;
		}
		$act = Context::get('act');

		$menus = array();
		foreach ($xml_obj as $it) {
			$obj = new StdClass();
			$obj->id = $it->id->body;
			if($parent_item) 
			{
				$obj->parent_id = $parent_item->id;
			}
			$obj->title = $it->title->body;
			$obj->action = array();
			if(is_array($it->action))
			{
				foreach ($it->action as $action)
				{
					$obj->action[] = $action->body;
				}
			}
			else
			{
				$obj->action[] = $it->action->body;
			}
			$obj->description = $it->description->body;
			$obj->selected = false;
			if(in_array($act, $obj->action)) 
			{
				$obj->selected = true;
				if($parent_item) 
				{
					$parent_item->selected = true;
				}
			}
			if($it->item && ($it->attrs->modinst != 'true'||Context::get('module_srl'))) 
			{
				$obj->submenu = nucommon::getMenu($it->item, $depth+1, $obj);
				if($obj->selected && $parent_item) 
				{
					$parent_item->selected= true;
				}
				if($obj->selected) 
				{
					Context::set('selected_menu', $obj);
				}
			}
			$menus[$obj->id] = $obj;
			unset($obj);
		}
		return $menus;
	}

	function getNotice()
	{
		//Retrieve recent news and set them into context
		$newest_news_url = sprintf("http://singleview.co.kr/?module=broadcast&act=getNewsagencyArticle&inst=notice&top=6&loc=%s", _XE_LOCATION_);
		$cache_file = sprintf("%sfiles/cache/svec_notice.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
		if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time())
		{
			// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing textmessageistration page
			// Ensure to access the textmessageistration page even though news cannot be displayed
			FileHandler::writeFile($cache_file,'');
			FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
		}

		if(file_exists($cache_file)) 
		{
			$oXml = new XeXmlParser();
			$buff = $oXml->parse(FileHandler::readFile($cache_file));

			$item = $buff->zbxe_news->item;
			if($item) 
			{
				if(!is_array($item)) 
				{
					$item = array($item);
				}

				foreach($item as $key => $val) {
					$obj = null;
					$obj->title = $val->body;
					$obj->date = $val->attrs->date;
					$obj->url = $val->attrs->url;
					$news[] = $obj;
				}
				Context::set('news', $news);
			}
			Context::set('released_version', $buff->zbxe_news->attrs->released_version);
			Context::set('download_link', $buff->zbxe_news->attrs->download_link);
		}
	}
}
/* End of file nucommon.class.php */
/* Location: ./modules/nproduct/nucommon.class.php */
