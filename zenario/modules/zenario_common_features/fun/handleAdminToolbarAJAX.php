<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


//Requests for Menu Nodes
if (ze::request('mID') && ze::post('menu_item')) {
	//Most of the logic for Menu Nodes is already included for Storekeeper, so include those functions
	$this->handleOrganizerPanelAJAX('zenario__menu/panels/menu_nodes', ze::request('mID'), $ids, false, false);
	
	if (ze::post('make_primary') && ze\priv::check('_PRIV_EDIT_MENU_ITEM')) {
		$_SESSION['page_toolbar'] = 'menu1';
	}
} elseif (ze::post('rollback')) {
	$cVersionTo = false;
	ze\contentAdm::createDraft($cID, $cID, $cType, $cVersionTo, $cVersion);
		
} elseif (ze::post('trash')) {
	if (ze\contentAdm::allowTrash($cID, $cType) && ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
		$menu = ze\menu::getFromContentItem($cID, $cType);
		
		ze\contentAdm::trashContent($cID, $cType);
		
		if (!empty($menu['parent_id'])) {
			echo '<!--Go_To_URL:?mID='. $menu['parent_id']. '-->';
		
		} else {
			echo '<!--Go_To_URL:-->';
		}
	}

//Delete the draft of a Content Item
} elseif (ze::post('delete')) {
	if (ze\contentAdm::allowDelete($cID, $cType) && ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType)) {
		$menu = ze\menu::getFromContentItem($cID, $cType);
		
		ze\contentAdm::deleteDraft($cID, $cType);
		
		//Deleting a Draft in the front-end should automatically switch you to Preview mode
		unset($_SESSION['last_item'], $_SESSION['page_mode'], $_SESSION['page_toolbar']);
		
		//If this was just trashed or deleted, go back up one level in the menu, or go to the homepage.
		switch (ze\content::status($cID, $cType)) {
			case 'trashed':
				if (!empty($menu['parent_id'])) {
					echo '<!--Go_To_URL:?mID='. $menu['parent_id']. '-->';
				
				} else {
					echo '<!--Go_To_URL:-->';
				}
				break;
				
			case 'deleted':
				if (!empty($menu['parent_id'])) {
					echo '<!--Go_To_URL:?mID='. $menu['parent_id']. '-->';
					$_SESSION['zenario__deleted_so_up'] = true;
				
				} else {
					echo '<!--Go_To_URL:-->';
					$_SESSION['zenario__deleted_so_home'] = true;
				}
				break;
		}
	}

} elseif (ze::post('add_existing_translation_to_chain')) {
	if (ze\priv::check('_PRIV_EDIT_DRAFT')) {
		
		$cID = $cType = false;
		if ((ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids))
		 && ($cID2 = (int) ($_POST['cID'] ?? 0))) {
			ze\contentAdm::recordEquivalence($cID, $cID2, $cType);
		}
	}

} elseif (ze::post('rescan_extract')) {
	if (ze\module::inc('zenario_ctype_document')) {
		zenario_ctype_document::rescanExtract($ids, true);
	}

} else {
	//Most of the logic for Content is already included for Storekeeper, so include those functions
	$this->handleOrganizerPanelAJAX('zenario__content/panels/content', $cType. '_'. $cID, $ids, false, false);
}

return false;