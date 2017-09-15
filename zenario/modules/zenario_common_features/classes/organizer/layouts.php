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


class zenario_common_features__organizer__layouts extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/layouts') return;
		
		if (in($mode, 'full', 'quick', 'select')) {
			if (!checkForChangesInCssJsAndHtmlFiles($runInProductionMode = true)) {
				checkForMissingTemplateFiles();
			}
		}
		
		if (isset($_GET['refiner__trash'])) {
			$panel['title'] = adminPhrase('Archived Layouts');
			$panel['no_items_message'] = adminPhrase('No Layouts have been archived.');
			
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__trash'];
			
			unset($panel['columns']['archived']['title']);
			unset($panel['columns']['default']);
			unset($panel['collection_buttons']);
			unset($panel['trash']);
		
		} elseif ($refinerName == 'content_type') {
			unset($panel['trash']);
			unset($panel['columns']['archived']['title']);
			$panel['no_items_message'] = adminPhrase('There are no active Layouts for this Content Type.');
		
		} elseif ($mode == 'typeahead_search') {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__typeahead_search'];
		
		} elseif ($refinerName || in($mode, 'get_item_name', 'get_item_links')) {
			unset($panel['trash']);
			
			if (isset($panel['db_items']['custom_where_statement__without_unregistered'])) {
				$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__without_unregistered'];
			} else {
				unset($panel['db_items']['where_statement']);
			}
		
		} else {
			$panel['trash']['empty'] = !checkRowExists('layouts', array('status' => 'suspended'));
		}
		
		if (isset($_GET['refiner__content_type'])) {
			unset($panel['columns']['content_type']['title']);
		}
		
		if (isset($_GET['refiner__template_family'])) {
			unset($panel['columns']['family_name']['title']);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/layouts') return;
		
		require_once CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';
		
		$panel['key']['disableItemLayer'] = true;
		
		if ($refinerName == 'content_type') {
			$panel['title'] = adminPhrase('Layouts available for the "[[name]]" content type', array('name' => getContentTypeName($refinerId)));
			$panel['no_items_message'] = adminPhrase('There are no layouts available for the "[[name]]" content type', array('name' => getContentTypeName($refinerId)));
		
		} elseif ($_GET['refiner__module_usage'] ?? false) {
			$mrg = array(
				'name' => getModuleDisplayName($_GET['refiner__module_usage'] ?? false));
			$panel['title'] = adminPhrase('Layouts on which the module "[[name]]" is used (layout layer)', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no layouts using the module "[[name]]".', $mrg);
		
		} elseif ($_GET['refiner__plugin_instance_usage'] ?? false) {
			$mrg = array(
				'name' => getPluginInstanceName($_GET['refiner__plugin_instance_usage'] ?? false));
			$panel['title'] = adminPhrase('Layouts on which the plugin "[[name]]" is used (layout layer)', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no layouts using the plugin "[[name]]".', $mrg);
		
		}
		
		$panel['columns']['content_type']['values'] = array();
		foreach (getContentTypes() as $cType) {
			$panel['columns']['content_type']['values'][$cType['content_type_id']] = $cType['content_type_name_en'];
		}
		
		$foundPaths = array();
		$defaultLayouts = getRowsArray('content_types', 'default_layout_id', array());
		
		$templatePreview = '';
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array();
			
			
			//For each Template file that's not missing, check its size and check the contents
			//to see if it has grid data saved inside it.
			//Multiple layouts could use the same file, so store the results of this to avoid
			//wasting time scanning the same file more than once.
			if (empty($item['missing']) && !isset($foundPaths[$item['path']])) {
				if ($fileContents = @file_get_contents($item['path'])) {
					$foundPaths[$item['path']] = array(
						'filesize' => strlen($fileContents),
						'checksum' => md5($fileContents),
						'grid' => zenario_grid_maker::readCode($fileContents, true, true)
					);
				} else {
					$foundPaths[$item['path']] = false;
				}
			}
			unset($fileContents);
			
			if (empty($item['missing']) && !empty($foundPaths[$item['path']])) {
				$item['filesize'] = $foundPaths[$item['path']]['filesize'];
				
				if ($foundPaths[$item['path']]['grid']) {
					$item['traits']['grid'] = true;
				}
			} else {
				$item['missing'] = 1;
				$item['usage_status'] = 'missing';
			}
			
			
			//Numeric ids are Layouts
			if (is_numeric($id)) {
				
				if ($item['family_name'] == 'grid_templates') {
					$layoutDetails = zenario_grid_maker::readLayoutCode($id);
					$summary = 'Gridmaker layout / ';
					if (!empty($layoutDetails['fluid'])) {
						$summary .= 'Fluid ';
					} else {
						$summary .= 'Fixed width ';
					}
					if (!empty($layoutDetails['responsive'])) {
						$summary .= '/ Responsive ';
					}
					if (!empty($layoutDetails['gCols'])) {
						$summary .= '/ '. $layoutDetails['gCols']. ' columns';
					}
				} else {
					$summary = 'Static';
				}
				$item['summary'] = $summary;
				
				if (!checkRowExists('content_types', array('default_layout_id' => $id)) && !checkRowExists('content_item_versions', array('layout_id' => $id))) {
					$item['traits']['deletable'] = true;
				
				}
				
				$item['usage_status'] = $item['usage_count'];
				
				// Try to automatically add a thumbnail
				if (!empty($foundPaths[$item['path']])) {
					$item['image'] = 'zenario/admin/grid_maker/ajax.php?thumbnail=1&width=180&height=130&loadDataFromLayout='. $id. '&checksum='. $foundPaths[$item['path']]['checksum'];
					$item['list_image'] = 'zenario/admin/grid_maker/ajax.php?thumbnail=1&width=24&height=23&loadDataFromLayout='. $id. '&checksum='. $foundPaths[$item['path']]['checksum'];
				}
				
			//Non-numeric ids are the Family and Filenames of Template Files that have no layouts created
			} else {
				$item['name'] = str_replace('.tpl.php', '', $item['template_filename']);
				$item['usage_status'] = $item['status'];
				$item['traits']['unregistered'] = true;
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__layouts/panels/layouts') return;
		
		//Delete a template if it is not in use
		if (($_POST['delete'] ?? false) && checkPriv('_PRIV_EDIT_TEMPLATE')) {
			foreach (explodeAndTrim($ids) as $id) {
				if (!checkRowExists('content_types', array('default_layout_id' => $id))
				 && !checkRowExists('content_item_versions', array('layout_id' => $id))) {
					deleteLayout($id, true);
				}
			}
			checkForChangesInCssJsAndHtmlFiles($runInProductionMode = true, $forceScan = true);
		
		//Archive a template
		} elseif (($_POST['archive'] ?? false) && checkPriv('_PRIV_EDIT_TEMPLATE')) {
			foreach (explodeAndTrim($ids) as $id) {
				if (!checkRowExists('content_types', array('default_layout_id' => $id))) {
					updateRow('layouts', array('status' => 'suspended'), $id);
				}
			}
		
		//Restore a template
		} elseif (($_POST['restore'] ?? false) && checkPriv('_PRIV_EDIT_TEMPLATE')) {
			foreach (explodeAndTrim($ids) as $id) {
				updateRow('layouts', array('status' => 'active'), $id);
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}