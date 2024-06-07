<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/**
 * @class  pageView
 * @author XEHub (developers@xpressengine.com)
 * @brief page view class of the module
 */
class pageView extends page
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
		$this->setTemplatePath($this->module_path.'tpl');

		switch($this->module_info->page_type)
		{
			case 'WIDGET' :
				{
					$this->cache_file = sprintf("%sfiles/cache/page/%d.%s.%s.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType(), Context::getSslStatus());
					$this->interval = (int)($this->module_info->page_caching_interval);
					break;
				}
			case 'OUTSIDE' :
				{
					$this->cache_file = sprintf("%sfiles/cache/opage/%d.%s.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getSslStatus());
					$this->interval = (int)($this->module_info->page_caching_interval);
					$this->path = $this->module_info->path;
					break;
				}
		}
	}

	/**
	 * @brief General request output
	 */
	function dispPageIndex()
	{
		// Variables used in the template Context:: set()
		if($this->module_srl) Context::set('module_srl',$this->module_srl);

		// Firt line of defense against RVE-2022-2.
		foreach (Context::getRequestVars() as $key => $val)
		{
			if (preg_match('/[\{\}\(\)<>\$\'"]/', $key) || preg_match('/[\{\}\(\)<>\$\'"]/', $val))
			{
				$this->setError(-1);
				$this->setMessage('msg_invalid_request');
				return;
			}
		}

		$page_type_name = strtolower($this->module_info->page_type);
		$method = '_get' . ucfirst($page_type_name) . 'Content';
		if(method_exists($this, $method)) $page_content = $this->{$method}();
		else return new BaseObject(-1, sprintf('%s method is not exists', $method));

		Context::set('module_info', $this->module_info);
		Context::set('page_content', $page_content);

		$this->setTemplateFile('content');
	}

	function _getWidgetContent()
	{
		if($this->interval>0)
		{
			if(!file_exists($this->cache_file)) $mtime = 0;
			else $mtime = filemtime($this->cache_file);

			if($mtime + $this->interval*60 > $_SERVER['REQUEST_TIME'])
			{
				$page_content = FileHandler::readFile($this->cache_file); 
				$page_content = preg_replace('@<\!--#Meta:@', '<!--Meta:', $page_content);
			}
			else
			{
				$oWidgetController = getController('widget');
				$page_content = $oWidgetController->transWidgetCode($this->module_info->content);
				FileHandler::writeFile($this->cache_file, $page_content);
			}
		}
		else
		{
			if(file_exists($this->cache_file)) FileHandler::removeFile($this->cache_file);
			$page_content = $this->module_info->content;
		}
		return $page_content;
	}

	function _getArticleContent()
	{
		$oTemplate = &TemplateHandler::getInstance();

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument(0, true);

		if($this->module_info->document_srl)
		{
			$document_srl = $this->module_info->document_srl;
			$oDocument->setDocument($document_srl);
			Context::set('document_srl', $document_srl);
		}
		Context::set('oDocument', $oDocument);

		if ($this->module_info->skin)
		{
			$templatePath = (sprintf($this->module_path.'skins/%s', $this->module_info->skin));
		}
		else
		{
			$templatePath = ($this->module_path.'skins/default');
		}

		// begin - set $layout_info for a page module skin
		// Check if nLayoutSrl exists for the module
		if(Mobile::isFromMobilePhone())
			$nLayoutSrl = $this->module_info->mlayout_srl;
		else
			$nLayoutSrl = $this->module_info->layout_srl;
		// if nLayoutSrl is rollback by module, set default layout
		if($nLayoutSrl == -1)
		{
			$viewType = (Mobile::isFromMobilePhone()) ? 'M' : 'P';
			$oLayoutAdminModel = getAdminModel('layout');
			$nLayoutSrl = $oLayoutAdminModel->getSiteDefaultLayout($viewType, $this->module_info->site_srl);
			unset($oLayoutAdminModel);
		}
		if($nLayoutSrl && !$this->getLayoutFile())
		{
			// If nLayoutSrl exists, get information of the layout, and set the location of layout_path/ layout_file
			$oLayoutModel = getModel('layout');
			$oLayoutInfo = $oLayoutModel->getLayout($nLayoutSrl);
			unset($oLayoutModel);
			if($oLayoutInfo)
			{
				// Set menus into context
				if($oLayoutInfo->menu_count)
				{
					foreach($oLayoutInfo->menu as $menu_id => $menu)
					{
						// set default menu set(included home menu)
						if(!$menu->menu_srl || $menu->menu_srl == -1)
						{
							$oMenuAdminController = getAdminController('menu');
							$homeMenuCacheFile = $oMenuAdminController->getHomeMenuCacheFile();
							if(FileHandler::exists($homeMenuCacheFile))
								include($homeMenuCacheFile);

							if(!$menu->menu_srl)
							{
								$menu->xml_file = str_replace('.xml.php', $homeMenuSrl . '.xml.php', $menu->xml_file);
								$menu->php_file = str_replace('.php', $homeMenuSrl . '.php', $menu->php_file);
								$oLayoutInfo->menu->{$menu_id}->menu_srl = $homeMenuSrl;
							}
							else
							{
								$menu->xml_file = str_replace($menu->menu_srl, $homeMenuSrl, $menu->xml_file);
								$menu->php_file = str_replace($menu->menu_srl, $homeMenuSrl, $menu->php_file);
							}
						}
						$php_file = FileHandler::exists($menu->php_file);
						if($php_file)
							include($php_file);
						Context::set($menu_id, $menu);
					}
				}
				// Set layout information into context
				Context::set('layout_info', $oLayoutInfo);
				unset($oLayoutInfo);
			}
		}
		$isLayoutDrop = Context::get('isLayoutDrop');
		if($isLayoutDrop)
		{
			$kind = stripos($this->act, 'admin') !== FALSE ? 'admin' : '';
			if($kind == 'admin')
			{
				$oModule->setLayoutFile('popup_layout');
			}
			else
			{
				$oModule->setLayoutPath('common/tpl');
				$oModule->setLayoutFile('default_layout');
			}
		}
		// end - set $layout_info for a page module skin
		return $oTemplate->compile($templatePath, 'content');
	}

	function _getOutsideContent()
	{
		// check if it is http or internal file
		if($this->path)
		{
			if(preg_match("/^([a-z]+):\/\//i",$this->path)) $content = $this->getHtmlPage($this->path, $this->interval, $this->cache_file);
			else $content = $this->executeFile($this->path, $this->interval, $this->cache_file);
		}

		return $content;
	}

	/**
	 * @brief Save the file and return if a file is requested by http
	 */
	function getHtmlPage($path, $caching_interval, $cache_file)
	{
		// Verify cache
		if($caching_interval > 0 && file_exists($cache_file) && filemtime($cache_file) + $caching_interval*60 > $_SERVER['REQUEST_TIME'])
		{
			$content = FileHandler::readFile($cache_file);
		}
		else
		{
			FileHandler::getRemoteFile($path, $cache_file);
			$content = FileHandler::readFile($cache_file);
		}
		// Create opage controller
		$oPageController = getController('page');
		// change url of image, css, javascript and so on if the page is from external server
		$content = $oPageController->replaceSrc($content, $path);

		// Change the document to utf-8 format
		$buff = new stdClass;
		$buff->content = $content;
		$buff = Context::convertEncoding($buff);
		$content = $buff->content;
		// Extract a title
		$title = $oPageController->getTitle($content);
		if($title) Context::setBrowserTitle($title);
		// Extract header script
		$head_script = $oPageController->getHeadScript($content);
		if($head_script) Context::addHtmlHeader($head_script);
		// Extract content from the body
		$body_script = $oPageController->getBodyScript($content);
		if(!$body_script) $body_script = $content;

		return $content;
	}

	/**
	 * @brief Create a cache file in order to include if it is an internal file
	 */
	function executeFile($target_file, $caching_interval, $cache_file)
	{
		global $G_XE_GLOBALS;
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
			// show deactivated valnerable tag to prevent RVE-2022-2. No tag compile
			$content = FileHandler::readFile($target_file);
			$content = str_replace('<!--%import(', '&lt;!--%import(', $content);
			$content = str_replace(')-->', ')--&gt;', $content);
			$content = str_replace('<include target=', '&lt;include target&#x003d;', $content);
			$content = str_replace('<?', '&lt;?', $content);
			$content = str_replace('?>', '?&gt;', $content);
			// Replace relative path to the absolute path 
			$this->path = str_replace('\\', '/', realpath(dirname($target_file))) . '/';
			//$content = preg_replace_callback('/(target=|src=|href=|url\()("|\')?([^"\'\)]+)("|\'\))?/is',array($this,'_replacePath'),$content);
			//$content = preg_replace_callback('/(<!--%import\()(\")([^"]+)(\")/is',array($this,'_replacePath'),$content);

			FileHandler::writeFile($cache_file, $content);
			// Include and then Return the result
			if(!file_exists($cache_file)) return;

			FileHandler::writeFile($cache_file, $content);
		}

		$__Context = &$G_XE_GLOBALS['__Context__'];
		$__Context->tpl_path = $filepath;

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
/* End of file page.view.php */
/* Location: ./modules/page/page.view.php */
