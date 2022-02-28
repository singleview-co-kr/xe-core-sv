<?php
/* Copyright (C) singleview.co.kr <http://singleview.co.kr> */
/**
 * @class  appstoreView
 * @author singleview.co.kr (root@singleview.co.kr)
 * @brief module view class
 */
class appstoreView extends appstore
{
	var $module_srl = 0;
	var $list_count = 20;
	var $page_count = 10;
	var $cache_file;
	var $interval;
	var $path;

	/**
	 * @brief Initialization
	 */
	function init()
	{
		// Get a template path (page in the administrative template tpl putting together)
		// $this->setTemplatePath($this->module_path.'tpl');
        // Force the result output to be of XMLRPC
		
        // $sRequestMethod = Context::getRequestMethod();
        // var_dump($sRequestMethod);

        // var_Dump(Context::getRequestVars());
        // if($sRequestMethod == 'GET')  // POST는 checkCSRF()에서 금지함
        // {
        //     echo __FILE__.':'.__LINE__;
        //     $params = array();
        //     $params["act"] = "getResourceapiLastupdate";
        //     #$params["ddd"] = "ddd";
        //     $body = XmlGenerater::getXmlDoc($params);

        //     var_dump($body);

        //     echo json_encode($aCategoryNode); // model class에서는 output 강제 출력해야 함
        //     $this->add('data', $aCategoryNode);
        //     return 'POST';
        // }
	}

	/**
	 * @brief General request output
	 */
	function dispAppstoreIndex()
	{
        //echo __FILE__.':'.__LINE__.'<BR>';
        Context::setResponseMethod("XMLRPC");
        $oArg = Context::getRequestVars();
        switch($oArg->mode)
        {
            case 'checkdate':
                $this->_checkData();
                break;
            case 'applist':
                $this->_pushAppList();
                break;
        }
        exit;
    }

    function _checkData()
    {
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <response>
        <error>0</error>
        <message>success</message>
        <updatedate><![CDATA[20210805151519]]></updatedate>
        </response>';
    }
    
    function _pushAppList()
    {
        echo '<?xml version="1.0" encoding="UTF-8"?>
<response>
	<error>0</error>
	<message>success</message>
	<packageList>
		<item>
			<category_srl>18322925</category_srl>
			<package_srl>22657234</package_srl>
			<path>
				<![CDATA[./addons/xdt_google_analytics]]>
			</path>
			<title>
				<![CDATA[xe111 design team Google analytics Addon]]>
			</title>
			<homepage>
				<![CDATA[http://www.xedesignteam.com/]]>
			</homepage>
			<package_description>
				<![CDATA[싱글뷰의 코드를 달 수 있는 애드온입니다. Google, Google Analytics는 Google inc.의 상표입니다.]]>
			</package_description>
			<package_voter>6</package_voter>
			<package_voted>60</package_voted>
			<package_downloaded>1039</package_downloaded>
			<package_regdate>
				<![CDATA[20140327011542]]>
			</package_regdate>
			<package_last_update>
				<![CDATA[20210805151519]]>
			</package_last_update>
			<nick_name>
				<![CDATA[도라미]]>
			</nick_name>
			<item_srl>22756278</item_srl>
			<item_screenshot_url>
				<![CDATA[https://download.xpressengine.com/xedownload/app/22657234/thumbnails/md.png]]>
			</item_screenshot_url>
			<item_version>
				<![CDATA[1.2]]>
			</item_version>
			<item_voter>0</item_voter>
			<item_voted>0</item_voted>
			<item_downloaded>147</item_downloaded>
			<item_regdate>
				<![CDATA[20210805151519]]>
			</item_regdate>
			<package_star>5</package_star>
		</item>
		<item>
			<category_srl>18322923</category_srl>
			<package_srl>21374711</package_srl>
			<path>
				<![CDATA[./modules/ncenterlite]]>
			</path>
			<title>
				<![CDATA[XE 알림센터 Lite]]>
			</title>
			<homepage>
				<![CDATA[http://github.com/xe-public/xe-module-ncenterlite]]>
			</homepage>
			<package_description>
				<![CDATA[XE 새 글, 댓글 알림 ## XE 1.7 이상, PHP 5.3 이상에서만 동작합니다.]]>
			</package_description>
			<package_voter>75</package_voter>
			<package_voted>735</package_voted>
			<package_downloaded>12156</package_downloaded>
			<package_regdate>
				<![CDATA[20121204032939]]>
			</package_regdate>
			<package_last_update>
				<![CDATA[20210413095848]]>
			</package_last_update>
			<nick_name>
				<![CDATA[XEPublic]]>
			</nick_name>
			<item_srl>22756275</item_srl>
			<item_screenshot_url>
				<![CDATA[https://download.xpressengine.com/xedownload/app/21374711/thumbnails/md.png]]>
			</item_screenshot_url>
			<item_version>
				<![CDATA[3.0.9]]>
			</item_version>
			<item_voter>0</item_voter>
			<item_voted>0</item_voted>
			<item_downloaded>343</item_downloaded>
			<item_regdate>
				<![CDATA[20210413095848]]>
			</item_regdate>
			<package_star>4.5</package_star>
			<depfrom>
				<![CDATA[22753393,22753394,22753399,22726124]]>
			</depfrom>
		</item>
	</packageList>
	<page_navigation>
		<total_count>1350</total_count>
		<total_page>135</total_page>
		<cur_page>1</cur_page>
		<page_count>10</page_count>
		<first_page>1</first_page>
		<last_page>135</last_page>
		<point>0</point>
	</page_navigation>
</response>';
        
        // exit;
		// // Variables used in the template Context:: set()
		// if($this->module_srl) Context::set('module_srl',$this->module_srl);

		// // $page_type_name = strtolower($this->module_info->page_type);
		// // $method = '_get' . ucfirst($page_type_name) . 'Content';
		// // if(method_exists($this, $method)) $page_content = $this->{$method}();
		// // else return new BaseObject(-1, sprintf('%s method is not exists', $method));

		// Context::set('module_info', $this->module_info);
		// Context::set('page_content', $page_content);

		// $this->setTemplateFile('content');
	}

	/**
	 * @brief Create a cache file in order to include if it is an internal file
	 */
	function executeFile($target_file, $caching_interval, $cache_file)
	{
		// Cancel if the file doesn't exist
		if(!file_exists(FileHandler::getRealPath($target_file))) return;

		// Get a path and filename
		$tmp_path = explode('/',$cache_file);
		$filename = $tmp_path[count($tmp_path)-1];
		$filepath = preg_replace('/'.$filename."$/i","",$cache_file);
		$cache_file = FileHandler::getRealPath($cache_file);

		$level = ob_get_level();
		// Verify cache
		if($caching_interval <1 || !file_exists($cache_file) || filemtime($cache_file) + $caching_interval*60 <= $_SERVER['REQUEST_TIME'] || filemtime($cache_file)<filemtime($target_file))
		{
			if(file_exists($cache_file)) FileHandler::removeFile($cache_file);

			// Read a target file and get content
			ob_start();
			include(FileHandler::getRealPath($target_file));
			$content = ob_get_clean();
			// Replace relative path to the absolute path 
			$this->path = str_replace('\\', '/', realpath(dirname($target_file))) . '/';
			$content = preg_replace_callback('/(target=|src=|href=|url\()("|\')?([^"\'\)]+)("|\'\))?/is',array($this,'_replacePath'),$content);
			$content = preg_replace_callback('/(<!--%import\()(\")([^"]+)(\")/is',array($this,'_replacePath'),$content);

			FileHandler::writeFile($cache_file, $content);
			// Include and then Return the result
			if(!file_exists($cache_file)) return;
			// Attempt to compile
			$oTemplate = &TemplateHandler::getInstance();
			$script = $oTemplate->compileDirect($filepath, $filename);

			FileHandler::writeFile($cache_file, $script);
		}

		$__Context = &$GLOBALS['__Context__'];
		$__Context->tpl_path = $filepath;

		ob_start();
		include($cache_file);

		$contents = '';
		while (ob_get_level() - $level > 0) {
			$contents .= ob_get_contents();
			ob_end_clean();
		}
		return $contents;
	}

	function _replacePath($matches)
	{
		$val = trim($matches[3]);
		// Pass if the path is external or starts with /, #, { characters
		// /=absolute path, #=hash in a page, {=Template syntax
		if(strpos($val, '.') === FALSE || preg_match('@^((?:http|https|ftp|telnet|mms)://|(?:mailto|javascript):|[/#{])@i',$val))
		{
				return $matches[0];
			// In case of  .. , get a path
		}
		else if(strncasecmp('..', $val, 2) === 0)
		{
			$p = Context::pathToUrl($this->path);
			return sprintf("%s%s%s%s",$matches[1],$matches[2],$p.$val,$matches[4]);
		}

		if(strncasecmp('..', $val, 2) === 0) $val = substr($val,2);
		$p = Context::pathToUrl($this->path);
		$path = sprintf("%s%s%s%s",$matches[1],$matches[2],$p.$val,$matches[4]);

		return $path;
	}
}
/* End of file appstore.view.php */
/* Location: ./modules/appstore/appstore.view.php */
