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

$codeVersion = ze\db::codeVersion();
$v = $w = 'v='. $codeVersion;

if (!\ze::$cacheWrappers) {
	$w .= '&amp;no_cache=1';
}

$isWelcome = $mode === true || $mode === 'welcome';
$isOrganizer = $mode === 'organizer';
$httpUserAgent = ($_SERVER['HTTP_USER_AGENT'] ?? '');
$isAdmin = \ze::isAdmin();
$css_wrappers = \ze::setting('css_wrappers');


//Some IE specific fixes
echo '
<meta http-equiv="X-UA-Compatible" content="IE=Edge">';

//In admin mode, if this is IE, require 11 or later. Direct 10 and earlier to the compatibility mode page.
if (strpos($httpUserAgent, 'MSIE') === false) {
	$oldIE = $notSupportedInAdminMode = false;

} else {
	$oldIE = strpos($httpUserAgent, 'MSIE 6') !== false
		|| strpos($httpUserAgent, 'MSIE 7') !== false
		|| strpos($httpUserAgent, 'MSIE 8') !== false;

	$notSupportedInAdminMode = $oldIE
		|| strpos($httpUserAgent, 'MSIE 9') !== false
		|| strpos($httpUserAgent, 'MSIE 10') !== false;
	
	if ($isWelcome || $isAdmin) {
		echo '
<script type="text/javascript">
	if (typeof JSON === "undefined" || ', \ze\ring::engToBoolean($notSupportedInAdminMode), ') {
		document.location = "',
			\ze\escape::js(
				\ze\link::absolute().
				'zenario/admin/ie_compatibility_mode/index.php?'.
				http_build_query($_GET)
			),
		'";
	}
</script>';
	}
}

if ($absURL = \ze\link::absoluteIfNeeded(!$isWelcome && !$oldIE)) {
	$prefix = $absURL. 'zenario/';
}


//Work out what's on this page, which wrappers we need to include, and which Plugin/Swatches need to be requested

//Send the id of each plugin on the page to a JavaScript wrapper to include their JavaScript
//Send the plugin name of each
if (!empty(\ze::$slotContents) && is_array(\ze::$slotContents)) {
	$comma = '';
	$comma2 = '';
	$JavaScriptOnPage = [];
	$themesOnPage = [];
	
	foreach(\ze::$slotContents as &$instance) {
		
		if (isset($instance['class_name']) && !empty($instance['class'])) {
			if (empty($JavaScriptOnPage[$instance['class_name']])) {
				$JavaScriptOnPage[$instance['class_name']] = true;
				\ze::$pluginJS .= $comma. $instance['module_id'];
				$comma = ',';
			}
		}
	}
}


if ($isWelcome || ($isOrganizer && \ze::setting('organizer_favicon') == 'zenario')) {
	echo "\n", '<link rel="shortcut icon" href="', \ze\link::absolute(), 'zenario/admin/images/favicon.ico"/>';

} elseif (\ze::$dbL) {
	
	if ($isOrganizer && \ze::setting('organizer_favicon') == 'custom') {
		$faviconId = \ze::setting('custom_organizer_favicon');
	} else {
		$faviconId = \ze::setting('favicon');
	}
	
	if ($faviconId
	 && ($icon = \ze\row::get('files', ['id', 'mime_type', 'filename', 'checksum'], $faviconId))
	 && ($link = ze\file::link($icon['id'], false, 'public/images'))) {
		if ($icon['mime_type'] == 'image/vnd.microsoft.icon' || $icon['mime_type'] == 'image/x-icon') {
			echo "\n", '<link rel="shortcut icon" href="', \ze\link::absolute(), htmlspecialchars($link), '"/>';
		} else {
			echo "\n", '<link type="', htmlspecialchars($icon['mime_type']), '" rel="icon" href="', \ze\link::absolute(), htmlspecialchars($link), '"/>';
		}
	}

	if (!$isOrganizer
	 && \ze::setting('mobile_icon')
	 && ($icon = \ze\row::get('files', ['id', 'mime_type', 'filename', 'checksum'], \ze::setting('mobile_icon')))
	 && ($link = ze\file::link($icon['id'], false, 'public/images'))) {
		echo "\n", '<link rel="apple-touch-icon-precomposed" href="', \ze\link::absolute(), htmlspecialchars($link), '"/>';
	}
}



//Add CSS needed for the CMS in Admin mode
if ($isWelcome || $isAdmin) {
	if (!\ze::$skinId) {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'libs/manually_maintained/mit/colorbox/colorbox.css?', $v, '"/>';
	}
	
	echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'libs/manually_maintained/mit/jqueryui/jquery-ui.css?', $v, '"/>
<link rel="stylesheet" type="text/css" media="print" href="', $prefix, 'styles/print.min.css"/>';
	
	//Add the CSS for admin mode... unless this is a layout preview
	if ($mode != 'layout_preview') {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/admin.wrapper.css.php?', $w, '"/>';
	}
	
	if ($includeOrganizer) {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/organizer.wrapper.css.php?', $w, '"/>';
		
		if ($isOrganizer) {
			echo '
<link rel="stylesheet" type="text/css" media="print" href="', $prefix, 'styles/admin_organizer_print.min.css?', $v, '"/>';
		}
		
		$cssModuleIds = '';
		foreach (\ze\module::runningModules() as $module) {
			if (\ze::moduleDir($module['class_name'], 'adminstyles/organizer.css', true)
			 || \ze::moduleDir($module['class_name'], 'adminstyles/storekeeper.css', true)) {
				$cssModuleIds .= ($cssModuleIds? ',' : ''). $module['id'];
			}
		}
		
		if ($cssModuleIds) {
			echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/module.wrapper.css.php?', $w, '&amp;ids=', $cssModuleIds, '&amp;organizer=1"/>';
		}
	}
}

//Add the CSS for a skin, if there is a skin, and add CSS needed for any Module Swatches on the page
if ($overrideFrameworkAndCSS === false && ($css_wrappers == 'on' || ($css_wrappers == 'visitors_only' && !$isAdmin))) {
	
	//If wrappers are enabled, link to skin.cache_wrapper.css.php
	//(Note that wrappers are forced off when viewing a preview of layouts/CSS.)
	if (\ze::$skinId || \ze::$layoutId) {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/skin.cache_wrapper.css.php?', $v, '&amp;id=', (int) \ze::$skinId, '&amp;layoutId=', (int) \ze::$layoutId, '"/>
<link rel="stylesheet" type="text/css" media="print" href="', $prefix, 'styles/skin.cache_wrapper.css.php?', $v, '&amp;id=', (int) \ze::$skinId, '&amp;print=1', '"/>';
	}
	
} else {
	
	if (\ze::$skinId || \ze::$layoutId) {
		
		//Watch out for the variables from the CSS preview, and translate them to the format
		//needed by \ze\wrapper::includeSkinFiles() if we see them there.
		$overrideCSS = false;
		$overridePrintCSS = false;
		if ($overrideFrameworkAndCSS !== false) {
			$files = [];
			$overrideCSS = [];
			$overridePrintCSS = [];
			
			$tabs = [
				'this_css_tab',
				'all_css_tab',
				'0.reset.css',
				'.colorbox.css',
				'1.fonts.css',
				'1.forms.css',
				'1.jquery_ui.css',
				'1.layout.css',
				'3.misc.css',
				'3.misc_zfea.css',
				'4.responsive.css',
				'print.css'
			];

			//Also add editable CSS files used by the skin.
			if (\ze::$skinId && ($skin = ze\row::get('skins', 'name', \ze::$skinId))) {
				$editableCssFiles = [];
				$skinPath = CMS_ROOT . ze\content::skinPath($skin) . 'editable_css/';
				if ($handle = opendir($skinPath)) {
					while (($entry = readdir($handle)) !== false) {
						if ($entry != "." && $entry != ".." && strpos($entry, '2.') === 0) {
							$tabs[] = $entry;
						}
					}
				}
			}

			foreach ($tabs as $tab) {
				if (!empty($overrideFrameworkAndCSS[$tab. '/use_css_file'])
				 && !empty($overrideFrameworkAndCSS[$tab. '/css_filename'])
				 && isset($overrideFrameworkAndCSS[$tab. '/css_source'])) {
				 	$files[$overrideFrameworkAndCSS[$tab. '/css_filename']] = $overrideFrameworkAndCSS[$tab. '/css_source'];
				}
			}
			
			ksort($files);
			
			foreach ($files as $file => &$contents) {
				switch ($file) {
					case 'tinymce.css':
						break;
					
					case 'print.css':
					case 'stylesheet_print.css':
						$overridePrintCSS[] = [$file, $contents];
						break;
					
					default:
						$overrideCSS[] = [$file, $contents];
				}
			}
		}
		
		$req = ['id' => (int) \ze::$skinId, 'print' => '', 'layoutId' => \ze::$layoutId];
		\ze\wrapper::includeSkinFiles($req, $v, $overrideCSS);
		
		$req = ['id' => (int) \ze::$skinId, 'print' => '1'];
		\ze\wrapper::includeSkinFiles($req, $v, $overridePrintCSS);
	}
}

//Are there modules on this page..?
if (!empty(\ze::$slotContents) && is_array(\ze::$slotContents)) {
	//Include the Head for any plugin instances on the page, if they have one
	foreach(\ze::$slotContents as $slotName => &$instance) {
		if (!empty($instance['class'])) {
			\ze\plugin::preSlot($slotName, 'addToPageHead');
				$instance['class']->addToPageHead();
			\ze\plugin::postSlot($slotName, 'addToPageHead');
		}
	}
}

if ($grsk = \ze::setting('google_recaptcha_site_key')) {
	echo '
<script type="text/javascript">var google_recaptcha = {
	sitekey: "' . \ze\escape::js($grsk) . '",
	theme: "' . \ze\escape::js(\ze::setting('google_recaptcha_widget_theme')) . '"
};</script>';
}


if ($isAdmin) {
	//Add CSS needed for modules in Admin Mode in the frontend
	if (\ze::$cID) {
		$cssModuleIds = '';
		foreach (\ze\module::runningModules() as $module) {
			if (\ze::moduleDir($module['class_name'], 'adminstyles/admin_frontend.css', true)) {
				$cssModuleIds .= ($cssModuleIds? ',' : ''). $module['id'];
			}
		}
	
		if ($cssModuleIds) {
			echo '
<link rel="stylesheet" type="text/css" href="', $prefix, 'styles/module.wrapper.css.php?', $w, '&amp;ids=', $cssModuleIds, '&amp;admin_frontend=1" media="screen" />';
		}
	}
	
	//Add the CSS file for skin-specific admin styles, if it exists
	if (\ze::$skinId && ($skinPath = \ze\content::skinPath())) {
		if (is_file(CMS_ROOT. ($filePath = $skinPath. 'adminstyles/admin_frontend.css'))) {
			echo '
	<link rel="stylesheet" type="text/css" media="screen" href="', $absURL, $filePath, '"/>';
		}
	}
	

//Add the CSS for the login link for admins if this looks like a logged out admin
} else if (isset($_COOKIE['COOKIE_LAST_ADMIN_USER']) && !\ze\link::adminDomainIsPrivate()) { 
	echo '
<link rel="stylesheet" type="text/css" href="', $prefix, 'styles/admin_login_link.min.css?', $v, '" media="screen" />';
}


if (\ze::$cID && \ze::$cID !== -1) {
	$itemHTML = $templateHTML = $familyHTML =
	$bgWidth = $bgHeight = $bgURL = false;
	
	//Look up the background image and any HTML to add to the HEAD from the content item
	$sql = "
		SELECT head_html, head_cc, head_cc_specific_cookie_types, head_visitor_only, head_overwrite, bg_image_id, bg_color, bg_position, bg_repeat
		FROM ". DB_PREFIX. "content_item_versions
		WHERE id = ". (int) \ze::$cID. "
		  AND type = '". \ze\escape::asciiInSQL(\ze::$cType). "'
		  AND version = ". (int) \ze::$cVersion;
	$result = \ze\sql::select($sql);
	$itemHTML = \ze\sql::fetchAssoc($result);
	
	switch ($itemHTML['head_cc']) {
		case 'needed':
			if (!\ze\cookie::canSet()) {
				$itemHTML['head_html'] = $itemHTML['head_overwrite'] = false;
			}
			break;
		case 'specific_types':
			$cookieType = $itemHTML['head_cc_specific_cookie_types'];
			if (!(\ze::in($cookieType, 'functionality', 'analytics', 'social_media') && \ze\cookie::canSet($cookieType))) {
				$itemHTML['head_html'] = $itemHTML['head_overwrite'] = false;
			}
			break;
	}
	
	//Look up the background image and any HTML to add to the HEAD from the layout
	$sql = "
		SELECT head_html, head_cc, head_cc_specific_cookie_types, head_visitor_only, bg_image_id, bg_color, bg_position, bg_repeat
		FROM ". DB_PREFIX. "layouts
		WHERE layout_id = ". (int) \ze::$layoutId;
	$result = \ze\sql::select($sql);
	$templateHTML = \ze\sql::fetchAssoc($result);
	
	//Only add html from the layout if it's not been overridden on the Content Item
	if (empty($itemHTML['head_overwrite'])) {
		switch ($templateHTML['head_cc']) {
			case 'needed':
				if (!\ze\cookie::canSet()) {
					$templateHTML['head_html'] = $templateHTML['head_overwrite'] = false;
				}
				break;
			case 'specific_types':
				$cookieType = $templateHTML['head_cc_specific_cookie_types'];
				if (!(\ze::in($cookieType, 'functionality', 'analytics', 'social_media') && \ze\cookie::canSet($cookieType))) {
					$templateHTML['head_html'] = $templateHTML['head_overwrite'] = false;
				}
				break;
		}
		
		if (!empty($templateHTML['head_html']) && (empty($templateHTML['head_visitor_only']) || !$isAdmin)) {
			echo "\n\n". $templateHTML['head_html'], "\n\n";
		}
	}
	
	if (!empty($itemHTML['head_html']) && (empty($itemHTML['head_visitor_only']) || !$isAdmin)) {
		echo "\n\n". $itemHTML['head_html'], "\n\n";
	}
	
	
	//Check to see if there is a background image on this content item (or on this layout if not on the content item)
	if ($itemHTML['bg_image_id']) {
		ze\file::imageLink($bgWidth, $bgHeight, $bgURL, $itemHTML['bg_image_id']);
	} elseif ($templateHTML['bg_image_id']) {
		ze\file::imageLink($bgWidth, $bgHeight, $bgURL, $templateHTML['bg_image_id']);
	}
	
	$bgColor = $itemHTML['bg_color']? $itemHTML['bg_color'] : $templateHTML['bg_color'];
	$bgPosition = $itemHTML['bg_position']? $itemHTML['bg_position'] : $templateHTML['bg_position'];
	$bgRepeat = $itemHTML['bg_repeat']? $itemHTML['bg_repeat'] : $templateHTML['bg_repeat'];
	
	if ($bgURL || $bgColor || $bgPosition || $bgRepeat) {
		
		$background_selector = 'body';
		if (\ze::$skinId) {
			$background_selector = \ze\row::get('skins', 'background_selector', \ze::$skinId);
		}
		
		echo '
<style type="text/css">
	', $background_selector, ' {';
		if ($bgURL) {
			echo '
		background-image: url(\'', htmlspecialchars($bgURL), '\');';
		}
		if ($bgColor) {
			echo '
		background-color: ', htmlspecialchars($bgColor), ';';
		}
		if ($bgPosition) {
			echo '
		background-position: ', htmlspecialchars($bgPosition), ';';
		}
		if ($bgRepeat) {
			echo '
		background-repeat: ', htmlspecialchars($bgRepeat), ';';
		}
		
		echo '
		}
</style>';
	}
	
}

//Used by service workers to cache wrapper files
echo '<script type="text/javascript">window.zenarioCodeVersion = "', $codeVersion, '"</script>';

//Bugfixes for IE 6, 7 and 8
echo '
<!--[if lte IE 8]><script type="text/javascript" src="', $prefix, 'libs/yarn/respond.js/dest/respond.min.js?', $v, '"></script><![endif]-->';
