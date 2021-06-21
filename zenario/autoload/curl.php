<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

namespace ze;

class curl {

	//Formerly "checkCURLEnabled()"
	public static function checkEnabled() {
		return function_exists('curl_version');
	}

	//Formerly "curl()"
	public static function fetch($URL, $post = false, $options = [], $saveToFile = false) {
		if (!function_exists('curl_version')
		 || !($curl = @curl_init())) {
			return false;
		}

		$sReturn = '';
		$sReferer = \ze\link::host();
	
		curl_setopt($curl, CURLOPT_FAILONERROR, true); 
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 15);
		curl_setopt($curl, CURLOPT_VERBOSE, false);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_URL, $URL);
		curl_setopt($curl, CURLOPT_REFERER, \ze\link::host());
	
		if ($saveToFile) {
			$fp = fopen($saveToFile, 'w');
			curl_setopt($curl, CURLOPT_FILE, $fp);
		} else {
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		}
	
		if (!empty($post)) {
			curl_setopt($curl, CURLOPT_POST, true);
		
			if ($post !== true) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
			}
		} else {
			curl_setopt($curl, CURLOPT_POST, false);
		}
	
		if (!empty($options)) {
			foreach ($options as $opt => $optVal) {
				curl_setopt($curl, $opt, $optVal);
			}
		}
	
		$result = curl_exec($curl);
		curl_close($curl);
	
		if ($saveToFile) {
			fclose($fp);
		}
	
		return $result;
	}

	// Takes a curl response header as a string and returns an array of headers
	public static function getHeadersFromResponse($string) {
		$headers = [];
		foreach (explode("\n", $string) as $i => $line) {
			if ($i == 0) {
				$headers["http_code"] = $line;
			} else {
				list($key, $value) = explode(": ", trim($line));
				$headers[$key] = $value;
			}
		}
		return $headers;
	}
	
	//Given an associate array of headers, convert it into the format needed by CURL
	public static function convertHeadersFromAssociativeToIndexed($in) {
		$out = [];
	
		foreach ($in as $key => $value) {
			$out[] = $key. ': '. $value;
		}
	
		return $out;
	}

}