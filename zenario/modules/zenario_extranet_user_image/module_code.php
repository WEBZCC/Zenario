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

class zenario_extranet_user_image extends ze\moduleBaseClass {
	
	protected $mergeFields = [];
	protected $sections = [];
	
	public function init() {
		if (!($_SESSION['extranetUserID'] ?? false)) {
			return ze\priv::check();
		}
		
		if (ze::post('extranet_add_image')) {
			
		  
		  if (empty($_FILES['extranet_upload_image']['name'])) {
			$this->sections['Errors'] = true;
			$this->sections['Error'] = ['Error' => $this->phrase('Please select an image.')]; 
		  
		  } elseif (!ze\file::isImage(ze\file::mimeType($_FILES['extranet_upload_image']['name']))) {
			$this->sections['Errors'] = true;
			$this->sections['Error'] = ['Error' => $this->phrase('The uploaded image is not in a supported format. Please upload an image in GIF, JPEG or PNG format. The file extension should be either .gif, .jpg, .jpeg or .png.')]; 
		  
		  } else {
			ze\fileAdm::exitIfUploadError(false, false, true, $fileVar = 'extranet_upload_image');
			
			//Work out the max size of the image upload.
			//Check the Apache max upload value, the Zenario one, and the Maximum user image file size.
			//Pick the smallest limit of the 3 to validate against.
			
			$sizesToCheck = [];
			$apacheMaxFilesize = ze\dbAdm::apacheMaxFilesize();
			$sizesToCheck[] = $apacheMaxFilesize;

			$zenarioMaxFilesizeValue = ze::setting('content_max_filesize');
			$zenarioMaxFilesizeUnit = ze::setting('content_max_filesize_unit');
			$zenarioMaxFilesize = ze\file::fileSizeBasedOnUnit($zenarioMaxFilesizeValue, $zenarioMaxFilesizeUnit);
			$sizesToCheck[] = $zenarioMaxFilesize;

			if (ze::setting('max_user_image_filesize_override') == 'limit_max_attachment_file_size') {
				$usersMaxFilesizeValue = ze::setting('max_user_image_filesize');
				$usersMaxFilesizeUnit = ze::setting('max_user_image_filesize_unit');
				$usersMaxFilesize = ze\file::fileSizeBasedOnUnit($usersMaxFilesizeValue, $usersMaxFilesizeUnit);
				$sizesToCheck[] = $usersMaxFilesize;
			}

			$maxSize = min($sizesToCheck);
			
			if ($maxSize < $_FILES['extranet_upload_image']['size']) {
				$this->sections['Errors'] = true;
				$this->sections['Error'] = ['Error' => $this->phrase('Your image must be smaller than [[max_file_size]]', ['max_file_size' => ze\file::fileSizeConvert($maxSize)])];
			} elseif (empty($_FILES['extranet_upload_image']) || empty($_FILES['extranet_upload_image']['type'])) {
				$this->sections['Errors'] = true;
				$this->sections['Error'] = ['Error' => $this->phrase('Please pick an image.')];
			
			} elseif (!ze\file::isImage($_FILES['extranet_upload_image']['type'])) {
				$this->sections['Errors'] = true;
				$this->sections['Error'] = ['Error' => $this->phrase('Please pick a JPEG, GIF or PNG image.')];
			
			} else {
				$image = getimagesize($location = $_FILES['extranet_upload_image']['tmp_name']);
				
				$minWidth = (int) $this->setting('min_width') ?: 375;
				$minHeight = (int) $this->setting('min_height') ?: 500;
				if ($image[0] >= $minWidth && $image[1] >= $minHeight) {
					//Remove the User's old image, if they had one
					$this->removeUserImage();
					if ($imageId = ze\file::addToDatabase('user', $location, rawurldecode($_FILES['extranet_upload_image']['name']), true)) {
						ze\row::update('users', ['image_id' => $imageId], ($_SESSION['extranetUserID'] ?? false));
					}
				} else {
					$this->sections['Errors'] = true;
					$this->sections['Error'] = ['Error' => $this->phrase('Your uploaded image was too small. Your image should be at least [[width]] pixels wide and [[height]] pixels high.', 
																					[	'width' => $minWidth,
																							'height' => $minHeight
																						] 
																			)];
				}
							

			}
		  }
		
		} elseif (ze::post('extranet_remove_image_confirm') && $this->setting('allow_remove')) {
			$this->removeUserImage();
		}
		
		$url = $width = $height = false;
		if (($imageId = ze\row::get('users', 'image_id', ($_SESSION['extranetUserID'] ?? false)))
		 && ze\file::imageLink($width, $height, $url, $imageId, ze::ifNull((int) $this->setting('max_width'), 375), ze::ifNull((int) $this->setting('max_height'), 500))) {
			$this->sections['Existing_Image'] = true;
		 	$this->mergeFields['Image_Src'] = htmlspecialchars($url);
		 	$this->mergeFields['Image_Width'] = $width;
		 	$this->mergeFields['Image_Height'] = $height;
			$this->mergeFields['Upload_Button_Phrase'] = $this->phrase('Replace Image');
		} else {
			$this->sections['Existing_Image'] = false;
			$this->mergeFields['Upload_Button_Phrase'] = $this->phrase('Upload your image');
		}
		
		$this->mergeFields['Image_Title'] = $this->setting('title');
		
		if($this->setting('allow_upload')) {
			$this->sections['Allow_Upload'] = true;
			
			
		}
		
		$this->sections['Remove_Image'] = (bool) ($this->setting('allow_remove') && (!ze::post('extranet_remove_image')));
		$this->sections['Remove_Image_Confirm'] = (bool) ($this->setting('allow_remove') && ze::post('extranet_remove_image'));
		$this->sections['New_Image'] = true;
		
		return true;
	}
	
	function showSlot() {
		
		if (!($_SESSION['extranetUserID'] ?? false)) {
			if (ze\priv::check()) {
				echo ze\admin::phrase('You must be logged in as an extranet user to see this plugin.');
			}
			return;
		}
		echo $this->openForm('', 'enctype="multipart/form-data"');
			$this->framework('Outer', $this->mergeFields, $this->sections);
		echo $this->closeForm();
	}
	
	protected function removeUserImage() {
		//Remove the image for a user
		ze\row::update('users', ['image_id' => 0], ($_SESSION['extranetUserID'] ?? false));
		
		//Delete any unlinked images
		$sql = "
			DELETE f FROM ". DB_PREFIX. "files AS f
			LEFT JOIN ". DB_PREFIX. "users AS u
			   ON u.image_id = f.id
			WHERE f.`usage` = 'user'
			  AND u.image_id IS NULL";
		
		ze\sql::update($sql);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {

		if(!$values['allow_upload']) {
			$fields['first_tab/min_width']['hidden'] = true;
			$fields['first_tab/min_height']['hidden'] = true;
			$fields['first_tab/note_1']['hidden'] = true;
		} else {
			$fields['first_tab/min_width']['hidden'] = false;
			$fields['first_tab/min_height']['hidden'] = false;
			$fields['first_tab/note_1']['hidden'] = false;
		}

	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if(!$values['allow_upload']) {

		}
	}

}
?>
