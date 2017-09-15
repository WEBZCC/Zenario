<?php
/*
 * Copyright (c) 2017, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

class zenario_plugin_nest extends module_base_class {
	
	protected static $addedSubtitle = false;
	
	protected $firstTab = false;
	protected $lastTab = false;
	protected $slideNum = false;
	protected $slideId = false;
	protected $state = false;
	protected $usesConductor = false;
	protected $commands = array();
	protected $statesToSlides = array();
	protected $editingTabNum = false;
	protected $mergeFields = array();
	protected $sections = array();
	protected $tabs = array();
	protected $modules = array();
	protected $show = false;
	protected $minigrid = array();
	protected $minigridInUse = false;
	protected $usedColumns = 0;
	protected $groupingColumns = 0;
	protected $maxColumns = false;
	
	public $banner_canvas = false;
	public $banner_width = 0;
	public $banner_height = 0;
	public $banner__enlarge_image = false;
	public $banner__enlarge_canvas = false;
	public $banner__enlarge_width = 0;
	public $banner__enlarge_height = 0;
	
	protected $currentRequests = array();

	public function getSlides() {
		return $this->slides;
	}
	public function getSlideNum() {
		return $this->slideNum;
	}
	
	
	public function init() {
		//Flag that this plugin is actually a nest
		cms_core::$slotContents[$this->slotName]['is_nest'] = true;
		
		$conductorEnabled = $this->setting('enable_conductor');
		
		$this->loadFramework();
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		if ($this->specificEgg()) {
			$this->slideNum = ifNull(getRow('nested_plugins', 'slide_num', $this->specificEgg()), 1);
			$this->slideId = getRow('nested_plugins', 'id', array('is_slide' => 1, 'slide_num' => $this->slideNum));
			$this->loadTabs();
		
		} else {
		
			if ($this->loadTabs()) {
				
				//Check to see if a slide or a state is requested in the URL
				$lookForState =
				$lookForSlideId =
				$lookForSlideNum = 
				$defaultState = false;
				
				if ($conductorEnabled
				 && !empty($_REQUEST['state'])
				 && preg_match('/^[AB]?[A-Z]$/', $_REQUEST['state'])) {
					$lookForState = $_REQUEST['state'];
				
				} elseif ($lookForSlideId = (int) ($_REQUEST['slideId'] ?? false)) {
				} elseif ($lookForSlideNum = (int) ($_REQUEST['slideNum'] ?? false)) {
				}
				
				
				$tabOrd = 0;
				foreach ($this->slides as $slide) {
					++$tabOrd;
					$this->lastTab = $slide['id'];
					
					//By default, show the first slide that the visitor can see...
					if ($tabOrd == 1) {
						$this->firstSlide = $slide['id'];
						$this->slideNum = $slide['slide_num'];
						$this->slideId = $slide['id'];
						$this->state = $slide['states'][0];
						$defaultState = $slide['states'][0];
					}
					
					//...but change this to the one mentioned in the request, if we see it
					if ($lookForState && in_array($lookForState, $slide['states'])) {
						$this->slideNum = $slide['slide_num'];
						$this->slideId = $slide['id'];
						$this->state = $lookForState;
					
					} elseif ($lookForSlideId == $slide['id']) {
						$this->slideNum = $slide['slide_num'];
						$this->slideId = $slide['id'];
						$this->state = $slide['states'][0];
					
					} elseif ($lookForSlideNum == $slide['slide_num']) {
						$this->slideNum = $slide['slide_num'];
						$this->slideId = $slide['id'];
						$this->state = $slide['states'][0];
					}
					
					$tabIds[$slide['slide_num']] = $slide['id'];
					
					if (($this->checkFrameworkSectionExists($section = 'Tab_'. $slide['slide_num']))
					 || ($section = 'Tab')) {
						
						if (!isset($this->sections[$section])) {
							$this->sections[$section] = array();
						}
						
						$tabMergeFields = array(
							'TAB_ORDINAL' => $tabOrd);
						
						if (!$slide['invisible_in_nav']) {
							$tabMergeFields['Class'] = 'tab_'. $tabOrd. ' tab';
							$tabMergeFields['Tab_Link'] = $this->refreshPluginSlotTabAnchor('slideId='. $slide['id'], false);
							$tabMergeFields['Tab_Name'] = $this->formatTitleText($slide['name_or_title'], true);
						}
						
						if ($conductorEnabled) {
							$tabMergeFields['Show_Back'] = (bool) $slide['show_back'];
							$tabMergeFields['Show_Refresh'] = (bool) $slide['show_refresh'];
							$tabMergeFields['Show_Auto_Refresh'] = (bool) $slide['show_auto_refresh'];
							$tabMergeFields['Auto_Refresh_Interval'] = (int) $slide['auto_refresh_interval'];
							$tabMergeFields['Last_Updated'] = formatTimeNicely(time(), '%H:%i:%S');
						}
						
						//Set up the embed link
						if ($slide['show_embed']) {
							
							if (!in(setting('xframe_options'), 'all', 'specific')) {
								$tabMergeFields['Show_Embed_Disabled'] = true;
							
							} else {
								$embedLink = linkToItem(
									cms_core::$cID, cms_core::$cType, $fullPath = true, $request = '&zembedded=1&method_call=showSingleSlot&slotName='. $this->slotName,
									cms_core::$alias, $autoAddImportantRequests = true, $useAliasInAdminMode = true);
								
								$mergefields = [
									'title' => $this->phrase('Embed this plugin on a third-party website'),
									'desc' => $this->phrase('You can display this plugin (part of this page) on another website.'),
									'link' => $embedLink,
									'copy' => $this->phrase('Copy'),
									'copied' => $this->phrase('Copied to clipboard')
								];
								
								if ('public' != $slide['privacy']
								 || 'public' != sqlFetchValue("
													SELECT privacy
													FROM ". DB_NAME_PREFIX. "translation_chains
													WHERE equiv_id = ". (int) cms_core::$equivId. "
													  AND type = '". sqlEscape(cms_core::$cType). "'")
								) {
									$mergefields['auth_warning'] = $this->phrase('Warning: this page is password-protected, so users will need to be authenticated to this site before they can view the content.');
								}
								
								
								$tabMergeFields['Show_Embed'] = true;
								$tabMergeFields['Embed'] = json_encode($mergefields);
								
								requireJsLib('libraries/mit/toastr/toastr.min.js', 'libraries/mit/toastr/toastr.min.css', true);
							}
						}
						
						$this->sections[$section][$slide['slide_num']] = $tabMergeFields;
					}
				}
				
				if ((isset($this->sections[$section = 'Tab'][$this->slideNum]['Class']))
				 || (isset($this->sections[$section = 'Tab_'. $this->slideNum][$this->slideNum]['Class']))) {
					$this->sections[$section][$this->slideNum]['Class'] .= '_on';
				}
				
				
				$nextSlideId = false;
				if ($this->lastTab == $this->slideId) {
					if (!$this->setting('next_prev_buttons_loop')) {
						$this->mergeFields['Next_Disabled'] = '_disabled';
					} else {
						$nextSlideId = $this->firstSlide;
					}
				} else {
					foreach ($this->slides as $slideNum => $slide) {
						if ($slideNum > $this->slideNum) {
							$nextSlideId = $slide['id'];
							break;
						}
					}
				}
				
				if ($nextSlideId) {
					$this->mergeFields['Next_Link'] = $this->refreshPluginSlotTabAnchor('slideId='. $nextSlideId, false);
				}
				
				
				$prevSlideId = false;
				if ($this->firstSlide == $this->slideId) {
					if (!$this->setting('next_prev_buttons_loop')) {
						$this->mergeFields['Prev_Disabled'] = '_disabled';
					} else {
						$prevSlideId = $this->lastTab;
					}
				} else {
					foreach ($this->slides as $slideNum => $slide) {
						if ($slideNum >= $this->slideNum) {
							break;
						} else {
							$prevSlideId = $slide['id'];
						}
					}
				}
				
				if ($prevSlideId) {
					$this->mergeFields['Prev_Link'] = $this->refreshPluginSlotTabAnchor('slideId='. $prevSlideId, false);
				}
				
				$this->registerGetRequest('slideId', $this->firstSlide);
				$this->registerGetRequest('state', $defaultState);
			}
		}
		
		//Load all of the paths from the current state
		if ($conductorEnabled && $this->state) {
			
			//Loop through each slide, checking if they have any states or global commands
			$hadCommands = array();
			foreach ($this->slides as $slideNum => $slide) {
				
				//If a global command is set on a slide, it should point to the first state on that slide.
				$first = true;
				foreach ($slide['states'] as $state) {
					if ($state) {
						if ($first) {
							$first = false;
						
							//If this slide has a global command set, note it down
							//N.b. if two slides have the same global command, then go to the slide with the lowest ordinal.
							if (($command = $slide['global_command'])
							 && !isset($hadCommands[$command])) {
								
								//N.b. don't allow the link if we're already in that state...
								if ($state != $this->state) {
									$this->commands[$command] = [$state, $slide['request_vars']];
									$this->usesConductor = true;
								}
								
								//...but do block it, so we get consistent logic if two slides have the same global command.
								$hadCommands[$command] = true;
							}
						}
					
					
						//Note down which states are on which slides
						$this->statesToSlides[$state] = $slideNum;
					}
				}
			}
			unset($hadCommands);
			
			//Look through the nested paths that lead from this slide, and note each down
			//as long as it leads to another slide that we can see.
			$sql = "
				SELECT to_state, equiv_id, content_type, commands
				FROM ". DB_NAME_PREFIX. "nested_paths
				WHERE instance_id = ". (int) $this->instanceId. "
				  AND from_state = '". sqlEscape($this->state). "'
				ORDER BY to_state";
			
			foreach (sqlFetchAssocs($sql) as $path) {
				foreach (explodeAndTrim($path['commands']) as $command) {
					
					//Handle links to other content items
					if ($path['equiv_id']) {
						
						$cID = $path['equiv_id'];
						$cType = $path['content_type'];
						langEquivalentItem($cID, $cType);
						
						$this->commands[$command] = [
							$path['to_state'],
							null,
							$cID,
							$cType
						];
					
					//Handle links to other slides
					} elseif (isset($this->statesToSlides[$path['to_state']])) {
						
						$slideNum = $this->statesToSlides[$path['to_state']];
						
						$this->commands[$command] = [
							$path['to_state'],
							$this->slides[$slideNum]['request_vars']
						];
					}
					$this->usesConductor = true;
				}
			}
			
			if ($this->usesConductor) {
				$this->callScript('zenario_conductor', 'setCommands', $this->slotName, $this->commands, cms_core::$vars);
				
				//Add the current title of the current conductor slide to the page title
				//(Though use a static variable to stop this happening twice if there are two nests on the same page.)
				if (!self::$addedSubtitle) {
					self::$addedSubtitle = true;
					$this->setPageTitle(cms_core::$pageTitle. ': '. $this->formatTitleText($this->slides[$this->slideNum]['name_or_title']));
				}
			}
		}
		
		
		//If all tabs were hidden, don't show anything
		if ($this->slideNum !== false && $this->loadTab($this->slideNum)) {
			$this->show = true;
		
		//...except if no tabs exist, don't hide anything
		} elseif (!checkRowExists('nested_plugins', array('instance_id' => $this->instanceId, 'is_slide' => 1)) && $this->loadTab($this->slideNum = 1)) {
			$this->show = true;
		}
		
		//Set up some things for the conductor
		if ($this->usesConductor) {
			
			//Work out an array of the current requests, by looking in cms_core::$importantGetRequests
			$this->currentRequests = array();
			foreach(cms_core::$importantGetRequests as $var => $defaultValue) {
				if (isset($_REQUEST[$var])) {
					$this->currentRequests[$var] = $_REQUEST[$var];
				
				} elseif (isset(cms_core::$vars[$var])) {
					$this->currentRequests[$var] = cms_core::$vars[$var];
				}
			}

			$this->callScript('zenario_conductor', 'registerGetRequest', $this->slotName, $this->currentRequests);
		}
		
		return $this->show;
	}
	
	//Get an array of details on the back links, e.g. for use in breadcrumbs
	public function getBackLinks($addCurrent = true) {
		
		$backs = array();
		
		if ($this->usesConductor && $this->state) {
		
			if ($addCurrent) {
				$backs[$this->state] = ['state' => $this->state, 'slide' => $this->slides[$this->slideNum]];
			}
		
		
			$backToState = false;
			if (!empty($this->commands['back'][0])
			 && empty($this->commands['back'][2])) {
				$backToState = $this->commands['back'][0];
			}
		
			while ($backToState
			 && !isset($backs[$backToState])
			 && isset($this->statesToSlides[$backToState])
			) {
				$backs[$backToState] = ['state' => $backToState, 'slide' => $this->slides[$this->statesToSlides[$backToState]]];
			
				$backToState = sqlFetchValue("
					SELECT to_state
					FROM ". DB_NAME_PREFIX. "nested_paths
					WHERE instance_id = ". (int) $this->instanceId. "
					  AND from_state = '". sqlEscape($backToState). "'
					  AND commands IN ('back', 'close')
					ORDER BY to_state
					LIMIT 1
				");
			}
		
			$backs = array_reverse($backs);
			
			//Define requests for each link
			foreach ($backs as &$back) {
				$requests = array();
				
				//If we're generating a link to the current state, keep all of the registered get requests
				if ($back['state'] == $this->state) {
					foreach(cms_core::$importantGetRequests as $reqVar => $defaultValue) {
						if (isset($_GET[$reqVar]) && $_GET[$reqVar] != $defaultValue) {
							$requests[$reqVar] = $_GET[$reqVar];
						}
					}
				}
				
				//Loop through each of the variables needed by the destination
				foreach ($back['slide']['request_vars'] as $reqVar => $dummy) {
					//Check the settings on the destination to see if it needs that variable.
					//If so then try to add it from the core variables.
					if (empty($requests[$reqVar]) && !empty(cms_core::$vars[$reqVar])) {
						$requests[$reqVar] = cms_core::$vars[$reqVar];
					}
				}
			
				$requests['state'] = $back['state'];
				unset($requests['slideId']);
				unset($requests['slideNum']);
				$back['requests'] = $requests;
			}
		}
		
		//echo '<pre>'; var_dump($backs); exit;
		
		return $backs;
	}
	
	public function formatTitleText($text, $htmlescape = false) {
		
		//The old Tribiq frameworks need things escaped, so put this case in for them.
		//(Note that for backwards compatability reasons the new Twig frameworks are also working like this)
		if ($htmlescape) {
			$text = htmlspecialchars($text);
		}
		
		//If this is a library plugin, and therefore multilingual, we need to translate the text here
		if ($this->inLibrary) {
			$text = $this->phrase($text);
		}

		$frags = explode('[[', $text);
		$count = count($frags);
	
		if ($count > 1) {
			$text = $frags[0];
			for ($i = 1; $i < $count; ++$i) {
			
				$part = explode(']]', $frags[$i], 2);
			
				if (isset($part[1])) {
					if ($htmlescape) {
						$text = htmlspecialchars(requestVarMergeField($part[0])). $part[1];
					} else {
						$text .= requestVarMergeField($part[0]). $part[1];
					}
				} else {
					$text .= $part[0];
				}
			}
		}
		
		return $text;
	}

	
	public function showSlot() {
		
		$this->mergeFields['TAB_ORDINAL'] = $this->slideNum;
		
		//Show a single plugin in the nest
		if ($this->checkShowInFloatingBoxVar()) {
			if ($this->show) {
				
				$ord = 0;
				foreach ($this->modules[$this->slideNum] as $id => $slotNameNestId) {
					$this->mergeFields['PLUGIN_ORDINAL'] = ++$ord;
					
					if (!empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
						if (cms_core::$slotContents[$slotNameNestId]['class']->checkShowInFloatingBoxVar()) {
							$this->showPlugin($slotNameNestId);
						}
					}
				}
			}
		
		//Show all of the plugins on this slideId
		} elseif ($this->zAPIFrameworkIsTwig) {
			$this->mergeFields['Tabs'] = $this->sections['Tab'];
			
			if ($this->show) {
				$this->mergeFields['Tabs'][$this->slideNum]['Plugins'] = $this->modules[$this->slideNum];
			}
			$this->twigFramework($this->mergeFields);
		
		//Backwards compatability for old Tribiq frameworks
		} else {
			$this->sections['Tabs'] = $this->setting('show_tabs');
			$this->sections['Next'] = true;
			$this->sections['Prev'] = true;
			
			// Replace phrase codes with phrases in heading text
			if ($this->sections['Show_Title'] = (bool) $this->setting('show_heading')) {
				$this->mergeFields['Title'] = $this->formatTitleText($this->setting('heading_text'), true);
			}
			
			$this->frameworkHead(
				'Outer',
				'Plugins',
				$this->mergeFields,
				$this->sections);
			
			$this->frameworkHead(
				'Plugins',
				'Plugin',
				$this->mergeFields,
				$this->sections);
		
			if ($this->show) {
				$ord = 0;
				foreach ($this->modules[$this->slideNum] as $id => $slotNameNestId) {
					$this->mergeFields['PLUGIN_ORDINAL'] = ++$ord;
				
					$this->showPlugin($slotNameNestId);
				}
			}
			
			$this->frameworkFoot(
				'Plugins',
				'Plugin',
				$this->mergeFields,
				$this->sections);
		
			$this->frameworkFoot(
				'Outer',
				'Plugins',
				$this->mergeFields,
				$this->sections);
		}
	}
	
	
	protected function loadTabs() {
		
		$sql = "
			SELECT
				id, id AS slide_id,
				slide_num, name_or_title,
				states, show_back, show_embed, show_refresh, show_auto_refresh, auto_refresh_interval, request_vars, global_command,
				invisible_in_nav,
				privacy, smart_group_id, module_class_name, method_name, param_1, param_2, always_visible_to_admins
			FROM ". DB_NAME_PREFIX. "nested_plugins
			WHERE instance_id = ". (int) $this->instanceId. "
			  AND is_slide = 1
			ORDER BY slide_num";
		
		$result = sqlQuery($sql);
		$sqlNumRows = sqlNumRows($result);
		
		if (!$sqlNumRows) {
			//When a nest is first inserted, it will be empty.
			//This also sometimes happens after a site migration.
			//In this case, call the resyncNest function,
			//e.g. to ensure there is at least one slide and fix any other possibly invalid date
			call_user_func(array($this->moduleClassName, 'resyncNest'), $this->instanceId);
			$result = sqlQuery($sql);
			$sqlNumRows = sqlNumRows($result);
		}
		
		if (!$sqlNumRows) {
			return false;
		} else {
			while ($row = sqlFetchAssoc($result)) {
				$row['states'] = explode(',', $row['states']);
				$row['request_vars'] = arrayValuesToKeys(explodeAndTrim($row['request_vars']));
				
				$this->slides[$row['slide_num']] = $row;
			}
			
			
			$this->mergeFields['Nest'] = '';
			
			$this->removeHiddenTabs($this->slides, $this->cID, $this->cType, $this->cVersion, $this->instanceId);
			
			
			if ($this->setting('banner_canvas')
			 && $this->setting('banner_canvas') != 'unlimited') {
				$this->banner_canvas = $this->setting('banner_canvas');
				$this->banner_width = (int) $this->setting('banner_width');
				$this->banner_height = (int) $this->setting('banner_height');
			}
			
			if ($this->setting('enlarge_image')) {
				$this->banner__enlarge_image = true;
				$this->banner__enlarge_canvas = $this->setting('enlarge_canvas');
				$this->banner__enlarge_width = (int) $this->setting('enlarge_width');
				$this->banner__enlarge_height = (int) $this->setting('enlarge_height');
			}
			
			
			return !empty($this->slides);
		}
	}
	
	protected function loadTab($slideNum) {
		
		$sql = "
			SELECT np.id, np.slide_num, np.ord, np.module_id, np.framework, np.css_class, np.cols, np.small_screens
			FROM ". DB_NAME_PREFIX. "nested_plugins AS np
			WHERE np.instance_id = ". (int) $this->instanceId. "
			  AND np.is_slide = 0
			  AND np.slide_num = ". (int) $slideNum;
		
		if ($this->specificEgg()) {
			$sql .= "
			  AND np.id = ". (int) $this->specificEgg();
		}
		
		$sql .= "
			ORDER BY np.ord";
		
		$this->modules[$slideNum] = array();
		$lastSlotNameNestId = false;
		
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			$missingPlugin = false;
			if (($details = getModuleDetails($row['module_id']))
			 && (includeModuleAndDependencies($details['class_name'], $missingPlugin))
			 && (method_exists($details['class_name'], 'showSlot'))) {
				
				$eggId = $row['id'];
				$baseCSSName = $details['css_class_name'];
				
				$this->modules[$slideNum][$eggId] = $slotNameNestId = $this->slotName. '-'. $eggId;
				
				cms_core::$slotContents[$slotNameNestId] = $details;
				cms_core::$slotContents[$slotNameNestId]['instance_id'] = $this->instanceId;
				cms_core::$slotContents[$slotNameNestId]['egg_id'] = $eggId;
				cms_core::$slotContents[$slotNameNestId]['egg_ord'] = $row['ord'];
				cms_core::$slotContents[$slotNameNestId]['slide_num'] = $slideNum;
				cms_core::$slotContents[$slotNameNestId]['framework'] = ifNull($row['framework'], $details['default_framework']);
				cms_core::$slotContents[$slotNameNestId]['css_class'] = $details['css_class_name'];
				
				
				if ($row['css_class']) {
					cms_core::$slotContents[$slotNameNestId]['css_class'] .= ' '. $row['css_class'];
				} else {
					cms_core::$slotContents[$slotNameNestId]['css_class'] .= ' '. $baseCSSName. '__default_style';
				}
				
				
				//Add a CSS class for this version controller plugin, or this library plugin
				if ($this->isVersionControlled) {
					if (cms_core::$cID !== -1) {
						cms_core::$slotContents[$slotNameNestId]['css_class'] .=
							' '. cms_core::$cType. '_'. cms_core::$cID. '_'. $this->slotName.
							'_'. $baseCSSName.
							'_'. $row['slide_num']. '_'. $row['ord'];
					}
				} else {
					cms_core::$slotContents[$slotNameNestId]['css_class'] .=
						' '. $baseCSSName.
						'_'. $this->instanceId.
						'_'. $eggId;
				}
				
				
				
				
				if ($this->isVersionControlled) {
					cms_core::$slotContents[$slotNameNestId]['content_id'] = $this->cID;
					cms_core::$slotContents[$slotNameNestId]['content_type'] = $this->cType;
					cms_core::$slotContents[$slotNameNestId]['content_version'] = $this->cVersion;
					cms_core::$slotContents[$slotNameNestId]['slot_name'] = $this->slotName;
				} else {
					cms_core::$slotContents[$slotNameNestId]['content_id'] = 0;
					cms_core::$slotContents[$slotNameNestId]['content_type'] = '';
					cms_core::$slotContents[$slotNameNestId]['content_version'] = 0;
					cms_core::$slotContents[$slotNameNestId]['slot_name'] = '';
				}
				
				cms_core::$slotContents[$slotNameNestId]['cache_if'] = array();
				cms_core::$slotContents[$slotNameNestId]['clear_cache_by'] = array();
				
				
				//Read the minigrid information
				$row['cols'] = (int) $row['cols'];
				
				//If this plugin should be grouped with the previous plugin (-1)...
				if ($row['cols'] < 0) {
					if ($lastSlotNameNestId && isset($this->minigrid[$lastSlotNameNestId])) {
						//...flag it on the previous plugin so we know to open the grouping
						$this->minigrid[$lastSlotNameNestId]['group_with_next'] = true;
					} else {
						//...catch the case where there was no previous plugin by converting this to a full-width plugin
						$row['cols'] = 0;
					}
				}
				
				//If there are nothing but "full width" and "show on small screens" plugins,
				//then we don't actually need to use a grid and can just leave the HTML alone.
				//But as soon as we see a column that's not full width, or has responsive options,
				//then enable the grid!
				if (!$this->minigridInUse && ($row['cols'] > 0 || $row['small_screens'] != 'show')) {
					$this->minigridInUse = true;
					
					//Look up how many columns the current slot has, or just guess 12 if we can't find out
					$this->maxColumns = ifNull(
						(int) getRow('template_slot_link',
							'cols',
							array(
								'family_name' => cms_core::$templateFamily,
								'file_base_name' => cms_core::$templateFileBaseName,
								'slot_name' => $this->slotName)),
						12);
				}
				
				$this->minigrid[$slotNameNestId] = array(
					'cols' => min($row['cols'], $this->maxColumns),
					'small_screens' => $row['small_screens'],
					'group_with_next' => false
				);
				
				$lastSlotNameNestId = $slotNameNestId;
			}
		}
		
		$beingEdited =
		$showInMenuMode =
		$addedJavaScript = false;
		foreach ($this->modules[$slideNum] as $id => $slotNameNestId) {
			cms_core::$slotContents[$slotNameNestId]['instance_id'] = $this->instanceId;
			setInstance(cms_core::$slotContents[$slotNameNestId], $this->cID, $this->cType, $this->cVersion, $this->slotName, true, false, $id, $this->slideId);
			
			if (initPluginInstance(cms_core::$slotContents[$slotNameNestId])) {
				
				//Check for the forcePageReload and headerRedirect options in modules
				if ($reload = cms_core::$slotContents[$slotNameNestId]['class']->checkForcePageReloadVar()) {
					$this->forcePageReload($reload);
				}
				if ($url = cms_core::$slotContents[$slotNameNestId]['class']->checkHeaderRedirectLocation()) {
					$this->headerRedirect($url);
				}
				
				//Ensure that the JavaScript libraries is there for modules on reloads
				if ($this->needToAddCSSAndJS()) {
					$this->callScriptBeforeAJAXReload('zenario_plugin_nest', 'addJavaScript', cms_core::$slotContents[$slotNameNestId]['class_name'], cms_core::$slotContents[$slotNameNestId]['module_id']);
					$addedJavaScript = true;
				}
			}
			
			if (checkPriv() && !empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
				if (!$beingEdited) {
					if ($beingEdited = cms_core::$slotContents[$slotNameNestId]['class']->beingEdited()) {
						$this->editingTabNum = $slideNum;
					}
				}
				if (!$showInMenuMode) {
					$showInMenuMode = cms_core::$slotContents[$slotNameNestId]['class']->shownInMenuMode();
				}
			}
		}
		
		//Add any Plugin JavaScript calls
		foreach ($this->modules[$slideNum] as $id => $slotNameNestId) {
			if (!empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
				//Check to see if any Eggs want to scroll to the top of the slot
				$scrollToTop = cms_core::$slotContents[$slotNameNestId]['class']->checkScrollToTopVar();
				if ($scrollToTop !== null) {
					$this->scrollToTopOfSlot($scrollToTop);
				}
				
				//Check to see if any Eggs want to show themselves in a Floating Box, or stop showing themselves in a Floating Box
				if (cms_core::$slotContents[$slotNameNestId]['class']->checkShowInFloatingBoxVar()) {
					$this->showInFloatingBox(true);
				}
			}
		}
		
		//If an Egg wanted to show themselves in a Floating Box, hide the ones that didn't want this.
		if ($this->checkShowInFloatingBoxVar()) {
			$unsets = array();
			foreach ($this->modules[$slideNum] as $id => $slotNameNestId) {
				if (!empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
					if (!cms_core::$slotContents[$slotNameNestId]['class']->checkShowInFloatingBoxVar()) {
						unset(cms_core::$slotContents[$slotNameNestId]);
						$unsets[] = $id;
					}
				}
			}
			foreach ($unsets as $id) {
				unset($this->modules[$slideNum][$id]);
			}
		}
		
		
		if (checkPriv()) {
			$this->markSlotAsBeingEdited($beingEdited);
			$this->showInMenuMode($showInMenuMode);
		}
		
		return true;
	}
	
	
	public function showPlugin($slotNameNestId) {
		
		//Flag that we're no longer running Twig code, if this was called from a Twig Framework
		if ($this->zAPIFrameworkIsTwig) {
			cms_core::$isTwig = false;
		}
		
		if ($this->minigridInUse) {
			$minigrid = $this->minigrid[$slotNameNestId];
			$cols = $minigrid['cols'];
			$groupWithNext = $minigrid['group_with_next'];
			
			//"-1" means group with the previous plugin
			$groupWithPrevious = $cols < 0;
			
			//"0" means max-width
			if ($cols == 0
			 || $cols > $this->maxColumns) {
				$cols = $this->maxColumns;
			}
			
			//If we are not in the grouping, or are just starting a grouping,
			//we need to output a grid-slot.
			if (!$groupWithPrevious) {
			
				//Was there a previous cell?
				if ($this->usedColumns) {
					//Is this cell too big to fit the line..?
					if ($this->usedColumns + $cols > $this->maxColumns) {
						//Put a line break in
						$this->usedColumns = 0;
						echo '
				<div class="grid_clear"></div>';
					}
				}
			
				//Output the div for this 
				echo '
				<div class="minigrid '. rationalNumberGridClass($cols, $this->maxColumns);
			
				//Add the "alpha" class for the first cell on a line
				if ($this->usedColumns == 0) {
					echo ' alpha';
				}
				
				//Increase the number of columns that we have used by the width of this plugin
				$this->usedColumns += $cols;
			
				//Add the "omega" class if the cell goes right up to the end of a line
				if ($this->usedColumns >= $this->maxColumns) {
					echo ' omega';
				}
				
				//Add responsive classes on max-width columns
				//(Unless this is the start of a grouping, in which case the classes should be
				// added on to the nested-grid-slot.)
				if (!$groupWithNext) {
					if ($cols == $this->maxColumns) {
						switch ($minigrid['small_screens']) {
							case 'hide':
								echo ' responsive';
								break;
							case 'only':
								echo ' responsive_only';
								break;
						}
					}
				
				//If this is the start of a grouping, note down how many columns it has
				} else {
					$this->groupingColumns = $cols;
				}
				echo '">';
			
			} else {
				//Nested slots in minigrids are always full-width,
				//so if we are in a grouping, always put a line break in between slots.
				echo '
					<div class="grid_clear"></div>';
			}
			
			//If we are in a grouping, output a nested grid-slot
			if ($groupWithPrevious || $groupWithNext) {
				echo '
					<div class="minigrid '. rationalNumberGridClass($this->groupingColumns, $this->groupingColumns);
				
				//Add responsive classes
				switch ($minigrid['small_screens']) {
					case 'hide':
						echo ' responsive';
						break;
					case 'only':
						echo ' responsive_only';
						break;
				}
				
				//At the moment, nested grid-slots in minigrids are always full width
				echo ' alpha omega">';
			}
		}
		
		
		$p = checkPriv();
		$status = false;
		if (isset(cms_core::$slotContents[$slotNameNestId]['init'])) {
			$status = cms_core::$slotContents[$slotNameNestId]['init'];
		}
		
		$noPermsMsg = ($status === ZENARIO_401_NOT_LOGGED_IN || $status === ZENARIO_403_NO_PERMISSION) && $p;
		
		if ($p) {
			echo '
				<span class="';
			
			if ($noPermsMsg) {
				echo 'zenario_slotWithContents zenario_slotWithNoPermission">';
			
			} elseif ($status) {
				echo 'zenario_slotWithContents">';
			
			} else {
				echo 'zenario_slotWithNoContents">';
			}
		}
		
		if ($status || $p) {
			//Backwards compatability for old Tribiq frameworks
			if (!$this->zAPIFrameworkIsTwig) {
				$this->frameworkHead(
					'Plugin',
					'Show_Slot',
					$this->mergeFields);
			}
			
			cms_core::$slotContents[$slotNameNestId]['class']->show(false);
			
			//Backwards compatability for old Tribiq frameworks
			if (!$this->zAPIFrameworkIsTwig) {
				$this->frameworkFoot(
					'Plugin',
					'Show_Slot',
					$this->mergeFields);
			}
		}
		
		if ($p) {
			echo '
				</span>';
		}
		
		
		if ($this->minigridInUse) {
			//We'll need various different closing divs, depending on whether this is the
			//end of a normal slot, the end of a nested slot, or the end of both.
			if ($groupWithPrevious || $groupWithNext) {
				echo '
				</div>';
			}
			
			if (!$groupWithNext) {
				echo '
			</div>';
			}
		}
		
		
		if ($this->needToAddCSSAndJS()
		 && !empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
			//Add the script of a Nested Plugin to the Nest
			$scriptTypes = array();
			cms_core::$slotContents[$slotNameNestId]['class']->zAPICheckRequestedScripts($scriptTypes);
			
			foreach ($scriptTypes as $scriptType => &$scripts) {
				foreach ($scripts as &$script) {
					$this->zAPICallScriptWhenLoaded($scriptType, $script);
				}
			}
		}
		
		//Flag that we're going back to running Twig code, if this was called from a Twig Framework
		if ($this->zAPIFrameworkIsTwig) {
			cms_core::$isTwig = true;
		}
	}
	
	
	//Allow one specific Egg to be shown for the showFloatingBox/showRSS methods
	protected function specificEgg() {
		
		if (!empty($_REQUEST['method_call'])) {
			switch ($_REQUEST['method_call']) {
				case 'handlePluginAJAX':
				case 'showFloatingBox':
				case 'showRSS':
				case 'fillVisitorTUIX':
				case 'formatVisitorTUIX':
				case 'validateVisitorTUIX':
				case 'saveVisitorTUIX':
					return (int) ($_REQUEST['eggId'] ?? false);
			}
		}
		
		return false;
	}
	
	//Version of refreshPluginSlotAnchor, that doesn't automatically set the slide id
	public function refreshPluginSlotTabAnchor($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = false) {
		return
			$this->linkToItemAnchor($this->cID, $this->cType, $fullPath = false, '&slotName='. $this->slotName. urlRequest($requests)).
			' onclick="'.
				$this->refreshPluginSlotJS($requests, $scrollToTopOfSlot, $fadeOutAndIn).
				' return false;"';
	}
	
	
	public function showFloatingBox() {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->showFloatingBox();
		}
	}
	public function showRSS() {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->showRSS();
		}
	}
	public function handlePluginAJAX() {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->handlePluginAJAX();
		}
	}
	
	public function returnVisitorTUIXEnabled($path) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->returnVisitorTUIXEnabled($path);
		}
	}
	
	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->fillVisitorTUIX($path, $tags, $fields, $values);
		}
	}
	
	public function formatVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->formatVisitorTUIX($path, $tags, $fields, $values, $changes);
		}
	}
	
	public function validateVisitorTUIX($path, &$tags, &$fields, &$values, &$changes, $saving) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->validateVisitorTUIX($path, $tags, $fields, $values, $changes, $saving);
		}
	}
	
	public function saveVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->saveVisitorTUIX($path, $tags, $fields, $values, $changes);
		}
	}
	
	public function returnGlobalName() {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->returnGlobalName();
		}
	}
	
	protected function getSpecificEgg(&$class) {
		if ($this->show
		 && ($eggId = $this->specificEgg())
		 && ($slotNameNestId = arrayKey($this->modules[$this->slideNum], $eggId))
		 && (!empty(cms_core::$slotContents[$slotNameNestId]['init']))) {
			return cms_core::$slotContents[$slotNameNestId]['class'];
		}
		return false;
	}
	
	
	protected function needToAddCSSAndJS() {
		return ($_REQUEST['method_call'] ?? false) == 'refreshPlugin';
	}
	
	public function fillAdminSlotControls(&$controls) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__, 'organizer', $path)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__, 'organizer', $path)) {
			return $c->organizerPanelDownload($path, $ids, $refinerName, $refinerId);
		}
	}
	
	
	
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillAdminBox($path, $settingGroup, $box, $fields, $values);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->adminBoxSaveCompleted($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	
	
	
	
	protected function addPluginConfirm($addId, $instanceId, $copyingInstance = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function removePluginConfirm($eggIds, $instanceId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function duplicatePluginConfirm($eggId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function removeSlideConfirm($eggIds, $instanceId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function addPluginInstance($addPluginInstance, $instanceId, $slideNum = false, $inputIsSlideId = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function addPlugin($addPlugin, $instanceId, $slideNum = false, $displayName = false, $inputIsSlideId = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected static function addBanner($imageId, $instanceId, $slideNum = false, $inputIsSlideId = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected static function addTwigSnippet($moduleClassName, $snippetName, $instanceId, $slideNum = false, $inputIsSlideId = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	//Create a new, empty slide at the end of the nest
	public static function addSlide($instanceId, $title = false, $slideNum = false) {
		
		if ($slideNum === false) {
			$slideNum = 1 + (int) self::maxTab($instanceId);
		}
		
		if ($title === false) {
			$title = adminPhrase('Slide [[num]]', array('num' => $slideNum));
		}
		
		return insertRow(
			'nested_plugins',
			array(
				'instance_id' => $instanceId,
				'slide_num' => $slideNum,
				'ord' => 0,
				'module_id' => 0,
				'is_slide' => 1,
				'name_or_title' => $title));
	}
	
	public static function duplicatePlugin($eggId, $instanceId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function removePlugin($className, $eggId, $instanceId) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function removeSlide($className, $eggId, $instanceId) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	

	public static function reorderNest($ids) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function resyncNest($instanceId) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function maxTab($instanceId) {
		return sqlFetchValue("
			SELECT MAX(slide_num) AS slide_num
			FROM ". DB_NAME_PREFIX. "nested_plugins
			WHERE is_slide = 1
			  AND instance_id = ". (int) $instanceId);
	}
	
	protected static function maxOrd($instanceId, $slideNum) {
		return sqlFetchValue("
			SELECT MAX(ord) AS ord
			FROM ". DB_NAME_PREFIX. "nested_plugins
			WHERE slide_num = ". (int) $slideNum. "
			  AND is_slide = 0
			  AND instance_id = ". (int) $instanceId);
	}
	
	
	
	
	protected function removeHiddenTabs(&$tabs, $cID, $cType, $cVersion, $instanceId) {
		
		$unsets = array();
		foreach ($tabs as $slideNum => $slide) {
			if (!($slide['always_visible_to_admins'] && checkPriv())) {
				
				switch ($slide['privacy']) {
					case 'call_static_method':
					case 'send_signal':
						$this->allowCaching(false);
				}
				
				if (!checkItemPrivacy($slide, $slide, cms_core::$cID, cms_core::$cType, cms_core::$cVersion)) {
					$unsets[] = $slideNum;
				}
			}
		}
		
		foreach ($unsets as $unset) {
			unset($tabs[$unset]);
		}
	}
	
	
	
	public function cEnabled() {
		return $this->usesConductor;
	}
	
	public function cCommandEnabled($command) {
		return !empty($this->commands[$command][0]);
	}
	
	public function cLink($command, $requests = array()) {
		if (empty($this->commands[$command][0])) {
			return false;
		
		//Handle links to other slides
		} elseif (empty($this->commands[$command][2])) {
			
			//Loop through each of the variables needed by the destination
			foreach ($this->commands[$command][1] as $reqVar => $dummy) {
				//Check the settings on the destination to see if it needs that variable.
				//If so then try to add it from the core variables.
				if (empty($requests[$reqVar]) && !empty(cms_core::$vars[$reqVar])) {
					$requests[$reqVar] = cms_core::$vars[$reqVar];
				}
			}
			
			$requests['state'] = $this->commands[$command][0];
			unset($requests['slideId']);
			unset($requests['slideNum']);
			
			//If we're generating a link to the current state, keep all of the registered get requests
			$autoAddImportantRequests = $requests['state'] == $this->state;
			
			return linkToItem(cms_core::$cID, cms_core::$cType, false, $requests, cms_core::$alias, $autoAddImportantRequests);
		
		//Handle links to other content items
		} else {
			//Set the state or slide that we're linking to
			unset($requests['state']);
			unset($requests['slideId']);
			unset($requests['slideNum']);
			
			if (is_numeric($this->commands[$command][0])) {
				$requests['slideNum'] = $this->commands[$command][0];
			} else {
				$requests['state'] = $this->commands[$command][0];
			}
			
			return linkToItem($this->commands[$command][2], $this->commands[$command][3], false, $requests);
		}
	}
	
	public function cBackLink() {
		return $this->cLink($this->cCommandEnabled('back')? 'back' : 'close');
	}
	
	protected static function deletePath($instanceId, $fromState, $toState = false, $equivId = 0, $contentType = '') {
		
		//If a from & to are both specified, delete that specific path
		if ($toState) {
			deleteRow('nested_paths', array('instance_id' => $instanceId, 'from_state' => $fromState, 'to_state' => $toState, 'equiv_id' => $equivId, 'content_type' => $contentType));
		
		//If just one state is specified, delete all paths from and to that state
		} else {
			if (!$equivId) {
				$equivId = 0;
			}
			if (!$contentType) {
				$contentType = '';
			}
			
			deleteRow('nested_paths', array('instance_id' => $instanceId, 'from_state' => $fromState));
			deleteRow('nested_paths', array('instance_id' => $instanceId, 'to_state' => $fromState));
		}
		
	}
	
}
