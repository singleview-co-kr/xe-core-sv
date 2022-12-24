<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
require_once(_XE_PATH_.'modules/page/page.view.php');

class pageMobile extends pageView
{
	function init()
	{
		// Get a template path (page in the administrative template tpl putting together)
		$this->setTemplatePath($this->module_path.'tpl');

		switch($this->module_info->page_type)
		{
			case 'WIDGET' :
				{
					$this->cache_file = sprintf("%sfiles/cache/page/%d.%s.%s.m.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType(), Context::getSslStatus());
					$this->interval = (int)($this->module_info->page_caching_interval);
					break;
				}
			case 'OUTSIDE' :
				{
					$this->cache_file = sprintf("./files/cache/opage/%d.%s.m.cache.php", $this->module_info->module_srl, Context::getSslStatus()); 
					$this->interval = (int)($this->module_info->page_caching_interval);
					$this->path = $this->module_info->mpath;
					break;
				}
		}
	}

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
		if (method_exists($this, $method)) $page_content = $this->{$method}();
		else return new BaseObject(-1, sprintf('%s method is not exists', $method));

		Context::set('module_info', $this->module_info);
		Context::set('page_content', $page_content);

		$this->setTemplateFile('mobile');
	}

	function _getWidgetContent()
	{
		// Arrange a widget ryeolro
		if($this->module_info->mcontent)
		{
			$cache_file = sprintf("%sfiles/cache/page/%d.%s.m.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType());
			$interval = (int)($this->module_info->page_caching_interval);
			if($interval>0)
			{
				if(!file_exists($cache_file) || filesize($cache_file) < 1)
				{
					$mtime = 0;
				}
				else
				{
					$mtime = filemtime($cache_file);
				}

				if($mtime + $interval*60 > $_SERVER['REQUEST_TIME']) 
				{
					$page_content = FileHandler::readFile($cache_file); 
					$page_content = preg_replace('@<\!--#Meta:@', '<!--Meta:', $page_content);
				} 
				else 
				{
					$oWidgetController = getController('widget');
					$page_content = $oWidgetController->transWidgetCode($this->module_info->mcontent);
					FileHandler::writeFile($cache_file, $page_content);
				}
			} 
			else 
			{
				if(file_exists($cache_file))
				{
					FileHandler::removeFile($cache_file);
				}
				$page_content = $this->module_info->mcontent;
			}
		}
		else
		{
			$page_content = $this->module_info->content;
		}

		return $page_content;
	}

	function _getArticleContent()
	{
		$oTemplate = &TemplateHandler::getInstance();

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument(0, true);

		if($this->module_info->mdocument_srl)
		{
			$document_srl = $this->module_info->mdocument_srl;
			$oDocument->setDocument($document_srl);
			Context::set('document_srl', $document_srl);
		}
		if(!$oDocument->isExists())
		{
			$document_srl = $this->module_info->document_srl;
			$oDocument->setDocument($document_srl);
			Context::set('document_srl', $document_srl);
		}
		Context::set('oDocument', $oDocument);

		if($this->module_info->mskin)
		{
			$templatePath = (sprintf($this->module_path.'m.skins/%s', $this->module_info->mskin));
		}
		else
		{
			$templatePath = ($this->module_path.'m.skins/default');
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
		return $oTemplate->compile($templatePath, 'mobile');
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
}
/* End of file page.mobile.php */
/* Location: ./modules/page/page.mobile.php */
