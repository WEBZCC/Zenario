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

class zenario_extranet_registration extends zenario_extranet {
	
	protected $customFormErrors = [];
	protected $customFormExtraErrors = [];
	
	public function init() {
		ze::requireJsLib('zenario/libs/yarn/zxcvbn/dist/zxcvbn.js');
		ze::requireJsLib('zenario/js/password_functions.min.js');
		
		$this->registerPluginPage();
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		
		$this->mode = 'modeRegistration';
		
		ze::requireJsLib('zenario/modules/zenario_users/js/password_visitor_phrases.js.php?langId='. ze::$visLang);
		
		$this->registerGetRequest('extranet_resend');
		
		// Set the title
		if ($this->setting('registration_title')) {
			$this->subSections['Registration_Title_Section'] = true;
			$this->objects['Registration_Title'] = $this->phrase($this->setting('registration_title'));
		}
		
		// Set other text
		$this->objects['Register_Button_Text'] = $this->phrase($this->setting('register_button_text'));
		$this->objects['Resend_Verification_Email_Link_Text'] = $this->phrase($this->setting('resend_verification_email_link_text'));
		$this->objects['Resend_Verification_Email_Link_Description'] = $this->phrase($this->setting('resend_verification_email_link_description'));
		$this->objects['Go_Back_To_Login_Text'] = $this->phrase($this->setting('go_back_to_login_text'));
		$this->objects['Thank_You_Verify_Email_Text'] = $this->phrase(nl2br($this->setting('register_thank_you_verify_email_text')));
		$this->objects['Thank_You_Wait_For_Activation_Text'] = $this->phrase(nl2br($this->setting('register_thank_you_wait_for_activation_text')));
		$this->objects['Thank_You_Verify_Email_Resent_Text'] = $this->phrase(nl2br($this->setting('register_thank_you_verify_email_resent_text')));
		
		if (ze::setting('cookie_require_consent') == 'explicit' && !ze\cookie::canSet('functionality')) {
			$this->message = $this->phrase(
				'This site needs to place a cookie on your computer before you can log in. Please accept cookies from this site to continue. [[manage_cookies_link_start]]Manage cookies[[manage_cookies_link_end]]',
				[
					'manage_cookies_link_start' => '<a onclick="zenario.manageCookies();">',
					'manage_cookies_link_end' => '</a>'
				]
			);
			$this->mode = 'modeCookiesNotEnabled';
			return true;
		
		} else {
			ze\cookie::hideConsent();
			$this->manageCookies();
			
			
			if (ze::setting("user_use_screen_name") ) {
				$this->subSections['Choose_Screen_Name'] = true;
			}
			
			if ($this->setting('user_email_verification')) {
				$this->subSections['Second_Email'] = true;
			}

			if ($this->setting('show_salutation')) {
		        $this->subSections['Salutation'] = true;
		    }

		    if($this->setting('user_custom_fields')){
		        $chosenCustomFields = $allCustomFields = [];
		        $chosenCustomFields = explode(',', $this->setting('user_custom_fields'));
		        $allCustomFields = ze\datasetAdm::listCustomFields('users', $flat = false, false, $customOnly = true);
		       
		        if(isset($allCustomFields)){
		            foreach($allCustomFields as $k =>$value){
		                if(in_array($k,  $chosenCustomFields)){
				            $customDBColumns[$k]['label'] = $value['label'];
				            $customDBColumns[$k]['name'] = $value['db_column'];
				            $customDBColumns[$k]['type'] = $value['type'];
				            
				            if (!empty($_POST[$value['db_column']])) {
				            	//This line allows to preserve the value of a custom field
				            	//if a form error occured.
				            	$customDBColumns[$k]['value'] = ze\escape::sql($_POST[$value['db_column']]);
				            }
				            
				            if( $customDBColumns[$k]['type'] == 'select' || $customDBColumns[$k]['type'] == 'centralised_radios' || $customDBColumns[$k]['type'] == 'radios' || $customDBColumns[$k]['type'] == 'dataset_select' || $customDBColumns[$k]['type'] == 'centralised_select' ){
				                $customDBColumns[$k]['values'] = ze\dataset::fieldLOV($k, false);
				            }
				          
				        }
				    }
				}
				
				//To maintain the order as in the plugin settings
				$sortedArray = [];
				for ($i=0; $i<count($chosenCustomFields); $i++) {
					//Catch the case where a custom field was removed from the dataset editor,
					//but is still selected in the plugin settings.
					if (!empty($customDBColumns[$chosenCustomFields[$i]])) {
						$sortedArray[$chosenCustomFields[$i]] = $customDBColumns[$chosenCustomFields[$i]];
					}
				}
				$customDBColumns = $sortedArray;
				
				$this->objects['Custom_Fields_Values'] = $customDBColumns;
		        $this->subSections['Custom_Fields'] = true;
		    }
		    if ($this->setting('show_resend_verification_link')) {
		        $this->subSections['Show_Resend_Form'] = true;
			
		    }
			
			if ($this->setting('requires_terms_and_conditions'))	{
			    $userContentItem = $this->setting('terms_and_conditions_page');
				$useExternalLink = $this->setting('url');
				if ($userContentItem || $useExternalLink) {
					if ($userContentItem){
						$cID = $cType = false;
						$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
						ze\content::langEquivalentItem($cID, $cType);
						$TCLink = $this->linkToItem($cID, $cType, true);
					} elseif ($useExternalLink) {
						$TCLink = $this->setting('url');
					}
					$this->subSections['Ts_And_Cs_Section'] = true;
					$linkStart = '<a href ="'.$TCLink.'" target="_blank">';
					$linkEnd = '</a>';

					$this->objects['Ts_And_Cs_Link'] = $this->phrase ("I have read and accept the [[link_start]]Terms and Conditions[[link_end]].", ['link_start' => $linkStart, 'link_end' => $linkEnd]);
				}
			}
			
			if (!empty($_SESSION['extranetUserID'])) {
				if (ze::get('confirm_email') && $this->isEmailAddressVerified($_SESSION['extranetUserID'])) {
					$this->mode = 'modeVerificationAlreadyDone';
				} else {
					$this->mode = 'modeLoggedIn';
				}
			} elseif (ze::post('extranet_resend') && ($this->setting('initial_email_address_status')=='not_verified')) {
				$this->validateFormFields('Resend_Form');
				$user = $this->getDetailsFromEmail(ze::post('email'));
				if ((!$this->errors) && (!empty($user['id'])) ) {
					$this->sendVerificationEmail($user['id'] ?? false);
					$this->mode = 'modeResent';
				} else {
					$this->mode = 'modeResend';
				}
			} elseif (ze::get('extranet_resend') && ($this->setting('initial_email_address_status')=='not_verified')) {
				$this->mode = 'modeResend';
			} elseif (ze::post('extranet_register')){
				$this->scrollToTopOfSlot();
				
				if (ze::post('screen_name')) {
					$_POST['screen_name'] = trim($_POST['screen_name']);
				}
				
				if ($userId = $this->addUserRecord()){
					$this->handleUserRegistration($userId);
				} else {
					$this->mode = 'modeRegistration';
				}
				
			} elseif (ze::get('confirm_email') && ($this->setting('initial_email_address_status')=='not_verified')) { 
				if ($userId = $this->getUserIdFromHashCode(ze::get('hash'))){
					if (!$this->isEmailAddressVerified($userId)){
						$this->setEmailVerified($userId);
						$this->applyAccountActivationPolicy($userId);
						if ($this->isActive($userId)){
							if (ze\cookie::canSet('functionality')) {
								ze\cookie::setConsent();
							}
							$this->logUserIn($userId);
							$this->mode = 'modeLoggedIn';
							$this->redirectToPage();
						} else {
							$this->mode = 'modeRegisteredVerifiedNotActivated';
						}
					} else {
						$this->mode = 'modeVerificationAlreadyDone';
					}
				} else {
					$this->mode = 'modeVerificationFailed';
				}
			}
			
			if ($this->mode == 'modeRegistration') {
				if ($this->enableCaptcha()) {
				    
					$this->subSections['Captcha'] = true;
					$this->objects['Captcha'] = $this->captcha2();
				}
			}
			return true;
		}
	}
	
	//Override the equivalent method in the base module and always show Captcha.
	public function addToPageHead() {
		if ($this->enableCaptcha()) {
			$this->loadCaptcha2Lib();
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch($path) {
			case 'plugin_settings':
				$fields['set_timer_on_new_users']['hidden'] = !ze\module::inc('zenario_user_timers');
				
				$customFields = ze\datasetAdm::listCustomFields('users', $flat = false, ['checkbox', 'checkboxes'], $customOnly = true, $useOptGroups = true);

				if($options = self::removeEmptyTabs($customFields)){
					$box['tabs']['first_tab']['fields']['select_characteristics_for_new_users']['values'] = $options;
				}

				$customFields = ze\datasetAdm::listCustomFields('users', $flat = false, 'groups_only', $customOnly = true, $useOptGroups = true);
				if($options = self::removeEmptyTabs($customFields)){
					$box['tabs']['first_tab']['fields']['select_group_for_new_users']['values'] = $options;
				}
				
				//Set the default value of the login page selector to the special page.
				if (!$values['first_tab/login_page']) {
					$cID = $cType = false;
					if (ze\content::langSpecialPage('zenario_login', $cID, $cType)) {
						$tagId = $cType . '_' . $cID;
						$values['first_tab/login_page'] = $tagId;
					}
				}

				if(ze::setting('user_use_screen_name')){
				    $fields['show_screen_name']["value"] = 1;
				} else {
				    $fields['show_screen_name']["value"] = 0;
				}
        		//Make sure that the dataset field picker points to the users dataset
        		$dataset = ze\dataset::details('users');
        		
        		if (!ze::setting('google_recaptcha_site_key') || !ze::setting('google_recaptcha_secret_key')) {
				    //Show warning
					$recaptchaLink = "<a href='organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~tcaptcha_picture~k{\"id\"%3A\"api_keys\"}' target='_blank'>site settings</a>";
					$fields['use_captcha']['side_note'] = $this->phrase(
						"Recaptcha keys are not set. To show a captcha you must set the recaptcha [[recaptcha_link]].",
						['recaptcha_link' => $recaptchaLink]
					);
					$fields['use_captcha']['readonly'] = true;
                    $fields['use_captcha']['value'] = 0;
				}
				        
				$fields['custom_fields/user_custom_fields']['pick_items']['info_button_path'] =
					'zenario__administration/panels/custom_datasets/item_buttons/edit_gui//'. $dataset['id']. '//';
                
                $fields['custom_fields/desc']['snippet']['html'] = ze\admin::phrase($fields['custom_fields/desc']['snippet']['html'], $dataset);;

				break;
			case 'site_settings':
				if ($settingGroup == 'users') {
					$times = [];
					for ($i = 0; $i <= 23; ++$i) {
						$time = sprintf('%02d', $i) . ':00';
						$times[$time] = ['label' => $time];
					}
					$fields['registration/delayed_registration_email_time_of_day']['values'] = $times;
				}
				break;
		}
			
		return parent::fillAdminBox($path, $settingGroup, $box, $fields, $values);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {

		switch ($path) {
			case 'plugin_settings':
				$fields['first_tab/select_group_for_new_users']['hidden'] = !$values['first_tab/add_user_to_group'];
				$fields['first_tab/verification_email_template']['hidden'] = $values['first_tab/initial_email_address_status']=='verified';
				$fields['first_tab/user_signup_notification_email_template']['hidden'] = !$values['first_tab/enable_notifications_on_user_signup'];
				$fields['first_tab/user_signup_notification_email_address']['hidden'] = !$values['first_tab/enable_notifications_on_user_signup'];
				$fields['first_tab/select_characteristics_for_new_users']['hidden'] = !$values['first_tab/set_characteristics_on_new_users'];
				$fields['first_tab/select_characteristic_values_for_new_users']['hidden'] = (!$values['first_tab/set_characteristics_on_new_users'] || !$values['first_tab/select_characteristics_for_new_users']);
				$fields['first_tab/timer_for_new_users']['hidden'] = !$values['first_tab/set_timer_on_new_users'];
				$fields['first_tab/terms_and_conditions_page']['hidden'] = !$values['first_tab/requires_terms_and_conditions'];
				$fields['first_tab/url']['hidden'] = !$values['first_tab/requires_terms_and_conditions'];
				if ($values['first_tab/select_characteristics_for_new_users']) {
					$fieldType = ze\row::get('custom_dataset_fields', 'type', $values['first_tab/select_characteristics_for_new_users']);
					if ($fieldType == 'checkboxes') {
						$fields['first_tab/select_characteristic_values_for_new_users']['hidden'] = !$values['first_tab/set_characteristics_on_new_users'];
						$fields['first_tab/select_characteristic_values_for_new_users']['values'] = ze\dataset::fieldLOV($values['first_tab/select_characteristics_for_new_users']);
					} else {
						$fields['first_tab/select_characteristic_values_for_new_users']['hidden'] = true;
					}
				}
				
				$fields['user_activation/welcome_email_template']['hidden'] = !($values['user_activation/verified_account_status'] == 'active' || $values['user_activation/verified_account_status'] == 'contact');
				$fields['user_activation/trusted_email_domains']['hidden'] = $values['user_activation/verified_account_status'] != 'check_trusted';
	
				$fields['user_activation/user_activation_notification_email_template']['hidden'] = !$values['user_activation/user_activation_notification_email_enable'];
				$fields['user_activation/user_activation_notification_email_address']['hidden'] = !$values['user_activation/user_activation_notification_email_enable'];
				
				$fields['user_activation/welcome_page']['hidden'] = $values['user_activation/show_welcome_page'] != '_ALWAYS' && $values['user_activation/show_welcome_page'] != '_IF_NO_PREVIOUS_PAGE';
				
				
				// Screen name error hidden if screen names not enabled and no user forms or user forms and screen name on form
				$fields['error_messages/screen_name_in_use']['hidden'] = !ze::setting('user_use_screen_name');
			    
			    //Show checkbox rensend verification link
				$fields['first_tab/show_resend_verification_link']['hidden'] = $values['first_tab/initial_email_address_status']=='verified';
				
				if ($values['first_tab/include_an_attachment'] == true && $values['first_tab/selected_attachment']) {
					$privacy = ze\row::get('documents', 'privacy', ['id' => $values['first_tab/selected_attachment']]);
					
					if ($privacy == 'offline') {
						$fields['first_tab/selected_attachment']['note_below'] = ze\admin::phrase('The selected document is [[privateOrOffline]] and will not be sent. Please change its privacy settings, or choose a different document.', ['privateOrOffline' => $privacy]);
					} else {
						unset($fields['first_tab/selected_attachment']['note_below']);
					}
				} else {
					unset($fields['first_tab/selected_attachment']['note_below']);
				}

				
				
				break;
			
			case 'site_settings':
				$showWarningMessage = $values['registration/send_delayed_registration_email'] && !ze\miscAdm::checkScheduledTaskRunning('jobSendDelayedRegistrationEmails');
				$fields['registration/warning_message']['hidden'] = !$showWarningMessage;
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	
		switch ($path) {
			case 'plugin_settings':
				if ($values['first_tab/include_an_attachment'] == true && $values['first_tab/selected_attachment']) {
					$privacy = ze\row::get('documents', 'privacy', ['id' => $values['first_tab/selected_attachment']]);
					
					if ($privacy == 'offline') {
						$fields['first_tab/selected_attachment']['error'] = true;
					}
				}
			break;
		}
	}
	
	
	
	
	public function removeEmptyTabs($customFields){
		$tabs=[];
		if(is_array($customFields) && $customFields){
			foreach($customFields as $field){
				if(isset($field['parent'])){
					$tabs[$field['parent']]=$field['parent'];
				}
			}
		}
		
		if(is_array($tabs) && $tabs){
			foreach($customFields as $key=>$field){
				if(!isset($field['parent'])){
					if(!isset($tabs[$key])){
						unset($customFields[$key]);
					}
				}
			}
		}
		
		if(!$tabs){
			return false;
		}else{
			return $customFields;
		}
	}

	protected function validateFormFields($section, $contactsCountAsUnregistered = false) {
		$fields = parent::validateFormFields($section, $contactsCountAsUnregistered);
		if ($section == 'Registration_Form') {
			if (!empty($_POST['first_name']) && mb_strlen($_POST['first_name']) > 25) {
				$this->errors[] = ['Error' => $this->phrase('Your first name looks too long, are you sure this is your first name?')];
			}

			if (!empty($_POST['last_name']) && mb_strlen($_POST['last_name']) > 35) {
				$this->errors[] = ['Error' => $this->phrase('Your last name looks too long, are you sure this is your last name?')];
			}

			if ($this->setting('user_password') == 'user_to_choose_password') {
				$errors = $this->validatePassword($_POST['extranet_new_password'] ?? false, ze::post('extranet_new_password_confirm'), false, get_class($this));
				if (count($errors)) {
					$this->errors = array_merge($this->errors, $errors);
					unset($_SESSION['captcha_passed__'. $this->instanceId]);
					return false;
				}
			}

			if (!empty($this->errors)) {
				unset($_SESSION['captcha_passed__'. $this->instanceId]);
			}
			
			if ($this->enableCaptcha()) {
				if ($this->checkCaptcha2()) {
					$_SESSION['captcha_passed__'. $this->instanceId] = true;
				} else {
					$this->errors[] = ['Error' => $this->phrase('Please correctly verify that you are human.')];
				}
			}
		}

		return $fields;
	}

	protected function addUserRecord() {
		
		//Depending on the settings, allow contacts to register as if they haven't already made an account.
		switch ($this->setting('verified_account_status')) {
			case 'active':
				$contactsCountAsUnregistered = true;
				break;
			case 'contact':
				$contactsCountAsUnregistered = false;
				break;
			default:
				$contactsCountAsUnregistered = $this->setting('initial_account_status') == 'pending';
		}
		
		$fields = $this->validateFormFields('Registration_Form', $contactsCountAsUnregistered);
		
		if ($this->setting('user_email_verification')) {
			if (!ze::post('email_confirm')) {
				$this->errors[] = ['Error' => $this->phrase('Please re-enter your email address.')];
			} elseif (ze::post('email') != $_POST['email_confirm'] ?? false) {
				$this->errors[] = ['Error' => $this->phrase('The email addresses you entered do not match.')];
			}
		}
		
		if ($this->setting('show_salutation')) {
		    //$this->subSections['Salutation'] = true;
		}

		if ($this->errors) {
			return false;
		}
		
		if ($this->useScreenName) {
			if (ze::post('screen_name')){
				$fields['screen_name'] = $_POST['screen_name'] ?? false;
				$fields['screen_name_confirmed'] = 1;
			} else {
				$this->errors[] = ['Error' => $this->phrase('Please choose a screen name, this can be similar to your name, or can be different entirely.')];
				return false;
			}
		}
		
		$fields['password_needs_changing'] = 0;
		if ($this->setting('user_password')=='user_to_have_random_password' && $this->setting('password_needs_changing')) {
			$fields['password_needs_changing'] = 1;
		}
		
		
		
		$fields['email_verified'] = 0;
		if ($this->setting('initial_account_status')=='pending'){
			$fields['status'] = 'pending';
			if ($this->setting('user_password') == 'user_to_choose_password') {
				$fields['password'] = $_POST['extranet_new_password'] ?? false;
			} else {
				$fields['password'] = ze\userAdm::createPassword();
			}
		} else {
			$fields['status'] = 'contact';
		}

		$fields['created_date'] = date("Y-m-d H:i:s");

		if ($this->setting('user_password') == 'user_to_choose_password') {
			$fields['password'] = $_POST['extranet_new_password'] ?? false;
		} else {
			$fields['password'] = ze\userAdm::createPassword();
		}
		
		if (ze::request('extranet_terms_and_conditions') && $this->setting('requires_terms_and_conditions') && ($this->setting('terms_and_conditions_page')) || $this->setting('url')) {
			$fields['terms_and_conditions_accepted'] = 1;
		}
		
		if (isset($fields['%attributes%'])) {
			unset($fields['%attributes%']);
		}
		
		// Temp code to remove extra values from custom frameworks. Module should eventually be changed to user_forms.
		$fields2 = [];
		$sql = 'SHOW COLUMNS FROM '. DB_PREFIX. 'users';
		$result = ze\sql::select($sql);
		while ($column = ze\sql::fetchAssoc($result)) {
			if (isset($fields[$column['Field']]) && ($column['Key'] != 'PRI')) {
				$fields2[$column['Field']] = $fields[$column['Field']];
			}
		}
		
		if ($fields2['status'] == 'contact') {
			$fields2['creation_method_note'] = 'Contact signup';
		} else {
			$fields2['creation_method_note'] = 'User signup';
		}
		
		//Allow contacts to register, turning their contact account into a user account
		$userId = ze\row::get('users', 'id', ['email' => ze::post('email'), 'status' => 'contact']);
		
		$userId = ze\userAdm::save($fields2, $userId);
		
		if (ze::isError($userId)) {
			return false;
		} else {
			//Record user consent if terms and conditions were accepted
			if (!empty($fields2['terms_and_conditions_accepted'])) {
				ze\user::recordConsent(
					'extranet_registration',
					$this->instanceId, 
					$userId, 
					$fields2['email'] ?? false, $fields2['first_name'] ?? false, 
					$fields2['last_name'] ?? false, 
					$this->phrase("I have read and accept the Terms and Conditions.")
				);
			}
			
			if (!empty($userId)) {
				$creatorUserIdentifier = ze\user::identifier($userId);
			}
		
			$details = [];
			$details['created_admin_id'] = null;
			$details['created_user_id'] = $userId ?? null;
			$details['created_username'] = $creatorUserIdentifier ?? null;
			
			ze\row::update('users', $details, $userId);
			
			$details = [];
			ze::$dbL->checkTableDef($tableName = DB_PREFIX . 'users_custom_data');
			
			if ($this->setting('user_custom_fields')) {
				$result = ze\sql::select("select db_column, type, label from ".DB_PREFIX."custom_dataset_fields where id in (". ze\escape::in($this->setting('user_custom_fields'), 'numeric'). ")");
			
				// Save custom fields from plugin settings
				while ($column = ze\sql::fetchAssoc($result)) {
					if ($column['db_column']  != 'user_id') {
						$details[$column['db_column']] = $_POST[$column['db_column']] ?? 0;
					 
						if ($column['type'] && $column['type'] == "consent" && isset($_POST[$column['db_column']])) {
					   
							if($_POST[$column['db_column']] == 'on'){
								$details[$column['db_column']] = 1;
								//check if dataset feild is consent field
								ze\user::recordConsent('extranet_registration', $this->instanceId, $userId, $fields2['email'] ?? false, $fields2['first_name'] ?? false, $fields2['last_name'] ?? false, $column['label']);
							} 
						}
					}
				}
			}

			// Save custom fields from frameworks (using framework field).
			foreach (ze::$dbL->cols[$tableName] as $col => $colDef) {
				if ($col != 'user_id' && (isset($fields[$col]) || isset($customFields[$col]))) {
					$details[$col] = $fields[$col];
				   
					$colType = ze\row::get('custom_dataset_fields', 'type', ["db_column" => $col]);
					if ($colType && $colType == "consent") {
						//check if dataset feild is consent field
						//ze\user::recordConsent('extranet_registration', $this->instanceId, $userId, $fields2['email'] ?? false, $fields2['first_name'] ?? false, $fields2['last_name'] ?? false, strip_tags($this->phrase("_".$col)));
					}
				}
			}
			
			if (!empty($details)) {
				ze\row::set('users_custom_data', $details, $userId);
			}
			
			return $userId;
		}
	}
	
	
	protected function applyEmailVerificationPolicy($userId){
		if ($this->setting('enable_notifications_on_user_signup') && ($this->setting('user_signup_notification_email_template')) && ($this->setting('user_signup_notification_email_address'))){
			$this->sendSignupNotification($userId);
		}

		if ($this->setting('initial_email_address_status')=='verified'){
			$this->setEmailVerified($userId);
		} elseif ($this->setting('initial_email_address_status')=='not_verified'){
			$this->sendVerificationEmail($userId);
		}
	}

	protected function setEmailVerified($userId){
		if ($userId) {
			$sql = "
					UPDATE "
						. DB_PREFIX . "users 
					SET email_verified = 1";
			
			$sql .= " WHERE id = " . (int) $userId;
			ze\sql::update($sql);
		}
	}
	
	protected function getUserIdFromHashCode($hash){
		if ($hash && ($userId = (int) ze\row::get("users","id",['hash'=>$hash]))){
			return $userId;
		} else {
			return 0;
		}
	}
	
	protected function sendVerificationEmail($userId) {
		ze\userAdm::updateHash($userId);
		$emailMergeFields = ze\user::details($userId);
		if (!empty($emailMergeFields['email']) && $this->setting('verification_email_template')) {
			$emailMergeFields['cms_url'] = ze\link::absolute();
			$emailMergeFields['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&confirm_email=1&hash='. $emailMergeFields['hash']);
						
			$emailMergeFields['user_groups'] = ze\user::getUserGroupsNames($userId);
			
			if (ze\module::inc('zenario_users')) {
				foreach (ze\user::details($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv ?: '');
				}
			}
			
			
			zenario_email_template_manager::sendEmailsUsingTemplate($emailMergeFields['email'] ?? false,$this->setting('verification_email_template'),$emailMergeFields,[]);
		}
	}

	protected function sendSignupNotification($userId){
		if ($this->setting('user_signup_notification_email_address') && $this->setting('user_signup_notification_email_template')) {
			ze\userAdm::updateHash($userId);
			$emailMergeFields = ze\user::details($userId);
			$emailMergeFields['cms_url'] = ze\link::absolute();
			$emailMergeFields['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&confirm_email=1&hash='. $emailMergeFields['hash']);
			$emailMergeFields['organizer_link'] = ze\link::protocol(). ze\link::adminDomain(). SUBDIRECTORY. 'organizer.php#zenario__users/panels/users//'. $emailMergeFields['id'];
			
			$emailMergeFields['user_groups'] = ze\user::getUserGroupsNames($userId);
			
			if (ze\module::inc('zenario_users')) {
				foreach (ze\user::details($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
	
			zenario_email_template_manager::sendEmailsUsingTemplate($this->setting('user_signup_notification_email_address'),$this->setting('user_signup_notification_email_template'),$emailMergeFields,[]);
		}
	}

	protected function applyAccountActivationPolicy($userId){
		if ($this->setting('user_activation_notification_email_enable') && ($this->setting('user_activation_notification_email_template')) && ($this->setting('user_activation_notification_email_address'))){
			$this->sendActivationNotification($userId);
		}
		
		//No form is submitted, so $_POST is blank.
		//Add the necessary data for email logging purposes.
		if ($this->setting('verified_account_status') == 'contact' || $this->setting('verified_account_status') == 'active' || $this->setting('verified_account_status') == 'check_trusted') {
			$_POST['cID'] = $this->cID;
			$_POST['slideId'] = $this->slideId;
			$_POST['cType'] = $this->cType;
			$_POST['slotName'] = $this->slotName;
			$_POST['instanceId'] = $this->instanceId;
			$_POST['containerId'] = $this->containerId;
			$_POST['cVersion'] = $this->cVersion;
		}
		
		switch ($this->setting('verified_account_status')) {
			case 'contact':
				$this->setAccountContact($userId);
				$this->sendWelcomeEmail($userId);
				break;
			case 'active':
				$this->setAccountActive($userId);
				$this->sendWelcomeEmail($userId);
				break;
			case 'check_trusted':
				$userDetails = ze\user::details($userId);
				$userEmail = $userDetails['email'];
				$domains = explode(',', $this->setting('trusted_email_domains'));
				$domains = array_map('trim', $domains);
				if (in_array(substr($userEmail, strpos($userEmail, '@')), $domains)) {
					$this->setAccountActive($userId);
					$this->sendWelcomeEmail($userId);
				}
				break;
		}
	}
	
	protected function setAccountContact($userId){
		if ($userId) {
			$sql = '
				UPDATE '
					 .DB_PREFIX . 'users
				SET 
					status = "contact"
				WHERE 
					id = '.(int)$userId;
			ze\sql::update($sql);
		}
	}

	protected function setAccountActive($userId){
		if ($userId){
			$sql ="
					UPDATE " 
						. DB_PREFIX . "users
					SET 
						status='active'
					WHERE 
						id = " . (int) $userId;
			ze\sql::update($sql);
		}				
	}
	
	protected function sendWelcomeEmail($userId){
		$emailMergeFields = ze\user::details($userId);
		if (!empty($emailMergeFields['email']) && $this->setting('welcome_email_template')) {
			
			$emailMergeFields['cms_url'] = ze\link::absolute();
			$emailMergeFields['user_groups'] = ze\user::getUserGroupsNames($userId);
			
			if (ze\module::inc('zenario_users')) {
				foreach (ze\user::details($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
			//Deal with the fact that passwords are encrypted
			// If user chose password, show ****
			if ($this->setting('user_password') == 'user_to_choose_password') {
				$emailMergeFields['password'] = '********';
			
			// Otherwise generate a new password and show it
			} else {
				$password = ze\userAdm::createPassword();
				$emailMergeFields['password'] = $password;
				ze\userAdm::setPassword($userId, $password);
			}
			
			//Add attachments to the welcoming email.
			//Only accept public and private documents.
			$attachments = [];
			if ($this->setting('include_an_attachment') == true && $this->setting('selected_attachment')) {
				$document = ze\row::get('documents', ['file_id', 'privacy'], ['id' => $this->setting('selected_attachment')]);
				
				if ($document['privacy'] != 'offline') {
					$file = ze\file::link($document['file_id']);
					
					//For Docstore symlinks, get the real file path.
					$attachments[] = realpath(rawurldecode($file));
				}
			}
			
			zenario_email_template_manager::sendEmailsUsingTemplate($emailMergeFields['email'] ?? false,$this->setting('welcome_email_template'),$emailMergeFields,$attachments);
		}
	}

	protected function sendActivationNotification($userId) {
		if ($this->setting('user_activation_notification_email_address') && $this->setting('user_activation_notification_email_template')) {
			$emailMergeFields = ze\user::details($userId);
			$emailMergeFields['cms_url'] = ze\link::absolute();
			$emailMergeFields['organizer_link'] = ze\link::protocol(). ze\link::adminDomain(). SUBDIRECTORY. 'organizer.php#zenario__users/panels/users//'. $emailMergeFields['id'];
			
			$emailMergeFields['user_groups'] = ze\user::getUserGroupsNames($userId);
			
			if (ze\module::inc('zenario_users')) {
				foreach (ze\user::details($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
			
			zenario_email_template_manager::sendEmailsUsingTemplate($this->setting('user_activation_notification_email_address'),$this->setting('user_activation_notification_email_template'),$emailMergeFields,[]);
		}
	}
	
	protected function handleUserRegistration($userId) {
		//Set a flag for a user
		if ($this->setting('set_characteristics_on_new_users') && $this->setting('select_characteristics_for_new_users')) {
			$datasetField = ze\dataset::fieldDetails($this->setting('select_characteristics_for_new_users'));
			if ($datasetField['type'] == 'checkbox') {
				ze\row::set('users_custom_data', [$datasetField['db_column'] => 1], $userId);
			} elseif ($datasetField['type'] == 'checkboxes') {
				if ($this->setting('select_characteristic_values_for_new_users')) {
					foreach (explode(',', $this->setting('select_characteristic_values_for_new_users')) as $value) {
						ze\row::set(
							'custom_dataset_values_link', 
							[],  
							['linking_id'=> $userId, 'value_id'=> $value, 'dataset_id' => $datasetField['dataset_id']]
						);
					}
				}
			}
		}
		
		//Add user to group
		if ($this->setting('add_user_to_group') && (int)$this->setting('select_group_for_new_users')) {
			ze\user::addToGroup($userId, (int)$this->setting('select_group_for_new_users'));
		}
		
		//Create user timer
		if ($this->setting('timer_for_new_users') && ze\module::inc('zenario_user_timers')) {
			zenario_user_timers::createTimer($this->setting('timer_for_new_users'), $userId);
		}
		
		if (ze::setting('send_delayed_registration_email')) {
			ze\row::update('users', ['send_delayed_registration_email' => 1], $userId);
		}
		
		//Send signal
		$this->sendSignalFromForm('eventUserRegistered', $userId);
		
		unset($_SESSION['captcha_passed__'. $this->instanceId]);
		if (ze\cookie::canSet('functionality')) {
			ze\cookie::setConsent();
		}
		$this->applyEmailVerificationPolicy($userId);
		if ($this->isEmailAddressVerified($userId)){
			$this->applyAccountActivationPolicy($userId);
			if ($this->isActive($userId)){
				$this->logUserIn($userId);
				$this->mode = 'modeLoggedIn';
				$this->redirectToPage();
			} else {
				$this->mode = 'modeRegisteredVerifiedNotActivated';
			}
		} else {
			$this->mode = 'modeRegisteredNotVerified';
		}
	}
	
	protected function isEmailAddressVerified($userId){
		return (boolean) ((int) ze\row::get('users','email_verified',['id'=>$userId]));
	}

	protected function isActive($userId){
		return (ze\row::get('users','status',['id'=>$userId])=='active');
	}


	protected function modeRegistration() {
		
		$this->addLoginLinks();
		
		//Overwrite the Resend_Link from the addLoginLinks() function
		if ($this->setting('initial_email_address_status')=='not_verified'){
			$this->subSections['Resend_Link_Section'] = true;
			$this->objects['Resend_Link'] = $this->refreshPluginSlotAnchor('&extranet_resend=1');
		} else {
			$this->subSections['Resend_Link_Section'] = false;
			unset($this->objects['Resend_Link']);
		}
		
		
		$this->subSections['User_passwords'] = false;
		$userToChoosePassword = ($this->setting('user_password') == 'user_to_choose_password');
		if ($userToChoosePassword) {
			$this->subSections['User_passwords'] = true;
		}
		
		echo $this->openForm('',' class="form-horizontal"', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true);
			$this->subSections['Registration_Form'] = true;
			$this->objects['Password_Requirements_Settings'] = [
				'min_extranet_user_password_length' => ze::setting('min_extranet_user_password_length'),
				'min_extranet_user_password_score' => ze::setting('min_extranet_user_password_score')
			];
			$this->framework('Outer', $this->objects, $this->subSections);
		echo $this->closeForm();
		
		if ($userToChoosePassword) {
			$this->callScript('zenarioP', 'updatePasswordNotifier', '#extranet_new_password', $this->objects['Password_Requirements_Settings'], '#password_message', $adminFacing = false, $isInstaller = false);
		}
	}

	protected function modeResend() {
		$this->addLoginLinks();
		
		$this->objects['Registration_Link'] = $this->refreshPluginSlotAnchor();
		
		echo $this->openForm('',' class="form-horizontal"', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true),
			$this->remember('extranet_resend');
			$this->subSections['Resend_Form'] = true;
			$this->framework('Resend_Form', $this->objects, $this->subSections);
		echo $this->closeForm();
	}

	protected function modeVerificationFailed(){
		$this->subSections['Verification_Failed'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function modeVerificationAlreadyDone(){
		$this->subSections['Verification_Already_Done'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function modeRegisteredNotVerified(){
		$this->subSections['Registered_Not_Verified'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}

	protected function modeRegisteredVerifiedNotActivated(){
		$this->subSections['Registered_Verified_Not_Activated'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}

	protected function modeRegistered() {
		$this->subSections['Registered'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}

	protected function modeResent() {
		$this->subSections['Registered_Email_Resent'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case "zenario__users/panels/zenario_extranet_registration__code_groups":
				if (ze::post('action') == 'add_group_to_code') {
					ze\row::set(
						ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'code_groups',
						[],
						['code_id' => $refinerId, 'group_id' => ze::post('group_id')]);
				}
	
				if (ze::post('action') == 'remove_group_from_code') {
					foreach (explode(',', $ids) as $id) {
						ze\row::delete(
							ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'code_groups',
							['id' => (int)$id]);
					}
				}
				break;
			case "zenario__users/panels/zenario_extranet_registration__codes":
				if (ze::post('action') == 'delete_code') {
					foreach (explode(',', $ids) as $id) {
						ze\row::delete(
							ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'codes',
							['id' => (int) $id]);
						ze\row::delete(
							ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'code_groups',
							['code_id' => (int) $id]);
					}
				}
				
				break;
		}
	}
	
	public function checkCodeValid ($code) {
		return ze\row::exists(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "codes",["code" => $code]);
	}
	
	public function getCodeIdFromCode ($code) {
		return ze\row::get(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "codes","id",["code" => $code]);
	}
	
	public function getCodeGroups ($codeId) {
		$result = ze\row::query(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "code_groups","group_id",["code_id" => $codeId]);
		
		$groupIds = [];
		
		if (ze\sql::numRows($result)>0) {
			while ($row = ze\sql::fetchAssoc($result)) { 
				$groupIds[] = $row['group_id'];
			}
		}
		return $groupIds;
	}
	
	public static function jobSendDelayedRegistrationEmails() {
		$return = false;
		$date = new DateTime();
		$hour = (int)$date->format('H');
		$hourToSend = ze::setting('delayed_registration_email_time_of_day');
		if ($hour == $hourToSend) {
			
			$delay = ze::setting('delayed_registration_email_days_delayed');
			$template = ze::setting('delayed_registration_email_template');
			$sql = '
				SELECT u.id, u.identifier, u.first_name, u.last_name, u.email
				FROM ' . DB_PREFIX. 'users AS u
				WHERE u.send_delayed_registration_email = 1
				  AND u.status = "active"
				  AND DATE_ADD(u.created_date, INTERVAL '. (int) $delay. ' DAY) <= NOW()';
			
			$result = ze\sql::select($sql);
			while ($user = ze\sql::fetchAssoc($result)) {
				$mergeFields = $user;
				$mergeFields['cms_url'] = ze\link::absolute();
				zenario_email_template_manager::sendEmailsUsingTemplate($user['email'], $template, $mergeFields);
				ze\row::update('users', ['send_delayed_registration_email' => 0], $user['id']);
				
				echo "Sent delayed registration email to user " . $user['identifier'] . "\n";
				$return = true;
			}
		}
		return $return;
	}
}