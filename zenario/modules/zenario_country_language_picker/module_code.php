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

class zenario_country_language_picker extends ze\moduleBaseClass {
	
	protected $mergeFields = [];
	protected $data = [];
	
	public function init() {
		ze::requireJsLib('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js');
		
		if (empty($_COOKIE['country_id']) && empty($_COOKIE['user_lang'])) {
			if (empty($_SESSION['country_id']) || empty($_SESSION['user_lang'])) {
				$showPicker = true;
			} elseif (!empty($_SESSION['country_id']) && !empty($_SESSION['user_lang'])) {
				$showPicker = false;
			}
		} else {
			$showPicker = false;
		}
		
		if ($showPicker) {
			$this->data['Show_picker'] = true;
			
			//Get an array of installed languages.
			$sql = '
				SELECT id
				FROM ' . DB_PREFIX . 'languages';
			$result = ze\sql::select($sql);
			
			$this->data['Active_languages'] = [];
			while ($row = ze\sql::fetchValue($result)) {
				$this->data['Active_languages'][] = $row;
			}
			
			$this->data['Default_language'] = ze::setting('default_language');
			
			$this->data['cID'] = ze::$cID;
			$this->data['cType'] = ze::$cType;
			
			//Get an array of active countries.
			$sql = '
				SELECT id, english_name
				FROM ' . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
				WHERE active = 1';
			$result = ze\sql::select($sql);
			
			$this->data['Active_countries'] = [];
			while ($row = ze\sql::fetchAssoc($result)) {
				$this->data['Active_countries'][$row['id']] = $row['english_name'];
			}
			
			//This module allows detecting the user's country,
			//suggesting it, and asking the user if it's correct.
			//Please note that this variable is usable in custom frameworks,
			//but the standard framework does not make use of it.
			if (ze\module::inc('zenario_geoip_lookup')) {
				$this->data['Detected_country'] = zenario_geoip_lookup::getCountryCodeForVisitorByIP();
			}
			
			$this->callScript('zenario_country_language_picker','disablePageScrolling');
			
		} elseif (ze::request('showInFloatingBox') =='1') {
			$floatingBoxParams = [
				'escKey' => false, 
				'overlayClose' => false, 
				'closeConfirmMessage' => 'Are you sure you want to close this window? You will lose any changes.'
			];
			$this->showInFloatingBox(true, $floatingBoxParams);
		} else {
			
			$requests = 'showInFloatingBox=1';
			$buttonJS = $this->refreshPluginSlotAnchor($requests, false, false);
		
			if (!empty($_COOKIE['country_id']) && !empty($_COOKIE['user_lang'])) {
				$country_name = ze\row::get('visitor_phrases', 'local_text', ['code' => '_COUNTRY_NAME_' . $_COOKIE['country_id'], 'language_id' => ['LIKE' => $_COOKIE['user_lang'] . '%']]);
				$country_id = $_COOKIE['country_id'];
			} elseif (!empty($_SESSION['country_id']) && !empty($_SESSION['user_lang'])) {
				$country_name = ze\row::get('visitor_phrases', 'local_text', ['code' => '_COUNTRY_NAME_' . $_SESSION['country_id'], 'language_id' => ['LIKE' => $_SESSION['user_lang'] . '%']]);
				$country_id = $_SESSION['country_id'];
			}
			
			if (empty($country_name)) {
				$country_name = ze\lang::phrase('Set your country');
				$country_id = '';
			}
		
			$this->data['Country_name'] = $country_name;
			$this->data['Button_js'] = $this->refreshPluginSlotAnchor($requests, false, false);
			$this->data['Change_country_button'] = true;
			$this->data['Country_id'] = $country_id;
		}
		
		$this->data['ajaxURL'] = $this->pluginAJAXLink();
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public static function clearCountryIdAndLanguage() {
		ze\cookie::clear('country_id');
		ze\cookie::clear('user_lang');
		unset($_SESSION['country_id']);
		unset($_SESSION['user_lang']);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
	
	public function handlePluginAJAX() {
		if (ze::post('changeCountry')) {
			self::clearCountryIdAndLanguage();
		}
	}
}