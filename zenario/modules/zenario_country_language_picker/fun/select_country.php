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


header('Content-Type: text/plain; charset=UTF-8');
require '../../../basicheader.inc.php';

ze\db::loadSiteConfig();


//Check if cookies are enabled. Note this might need to be called twice in some cases
if (isset($_REQUEST['check_cookies_enabled'])) {
	
	if (empty($_COOKIE[ze\cookie::sessionName()])) {
		ze\cookie::startSession();
	} else {
		echo 1;
	}
	
	exit;
}


ze\cookie::startSession();
if (!empty($_REQUEST['country_id']) && !empty($_REQUEST['user_lang'])) {
	//Make sure country codes are always 2 letters. Make sure language codes are either 2 letters,
	//or 4 letters with a dash in the middle.
	if (preg_match('/^[a-zA-Z]{2}/', $_REQUEST['country_id']) && (preg_match('/^[a-zA-Z]{2}[-][a-zA-Z]{2}/', $_REQUEST['user_lang']) || preg_match('/^[a-zA-Z]{2}/', $_REQUEST['user_lang']))) {
		ze\cookie::setCountryAndLanguage($_REQUEST['country_id'], $_REQUEST['user_lang']);
	}
	
	//Redirect to the target page if it exists
	if (!empty($_REQUEST['cID']) && !empty($_REQUEST['cType'])) {
		$cID = (int) $_REQUEST['cID'];
		$cType = $_REQUEST['cType'];
		$targetLang = $_REQUEST['user_lang'];

		$langSanitised = ze\lang::sanitiseLanguageId($targetLang);
		$enabledContentTypes = ze\content::getContentTypes();
	
		//Validation:
		//cID needs to be an integer
		//cType should only be one of the enabled types
		//Language ID can only contain CAPITAL/lower case letters and dashes, and can only be up to 15 chars long.
		if ($targetLang == $langSanitised && array_key_exists($cType, $enabledContentTypes)) {
			ze\content::langEquivalentItem($cID, $cType, $targetLang);
			header('Location: '. ze\link::toItem($cID, $cType, true));
		}
		exit;
	}
}

if (empty($_REQUEST['ajax'])) {
	if (!empty($_SERVER['HTTP_REFERER'])) {
		header('location: '. $_SERVER['HTTP_REFERER']);
	} else {
		header('location: ../');
	}
}