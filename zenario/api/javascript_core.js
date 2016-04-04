/*
 * Copyright (c) 2016, Tribal Limited
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

/*
	This file contains JavaScript source code.
	The code here is not the code you see in your browser. Before this file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (this is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and visitor.wrapper.js.php for step (3).
*/


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO
) {
	"use strict";

	/**
	  * This section lists important JavaScript functions from the core CMS in Visitor Mode
	  * Other functions are tucked away in the /js folder
	 */

	
	//This is a shortcut function for initialising a new class.
	//It just uses normal JavaScript class inheritance, but it makes the syntax
	//a little more readable and friendly when creating a new class
	window.extensionOf =
	zenario.extensionOf = function(parent, initFun) {
		
		if (!parent) {
			return initFun || (function() {});
		}
	
		if (!initFun) {
			initFun = function() {
				parent.apply(this, arguments);
			};
		}
	
		initFun.prototype = new parent;
		initFun.prototype.constructor = parent;
	
		return initFun;
	};
	
	//This is a shortcut function for accessing the prototype of a class so you
	//can then define its methods.
	//This is more of a longcut than a shortcut function, but it makes the syntax
	//a little more readable and friendly when setting the methods of a class.
	window.methodsOf =
	zenario.methodsOf = function(thisClass) {
		return thisClass.prototype;
	};
	
	
	
	//Shortcut to document.getElementById()
	window.get = 
	zenario.get = function(el) {
		//Is there an admin floating box open?
			//To avoid clashes with ids on the page and in the box, demand that the element returned be inside the box
			//(Unless it's already prefixed with the zenario name, in which case it should be safe)
		if (window.zenario
		 && zenarioAB.isOpen
		 && el.substr(0, 7) != 'zenario'
		 && !el.match(/[^\w-]/)) {
			var $el = $('#zenario_fbAdminFloatingBox #' + el);
			
			if ($el[0]) {
				return $el[0];
			}
		}
	
		//If there wasn't an admin floating box open (or there was but the element we wanted wasn't inside)
		//then return document.getElementById() as normal
		return document.getElementById(el);
	};

	//Shortcut to hasOwnProperty()
	window.has =
	zenario.has = function(o, k) {
		return o !== undefined && o.hasOwnProperty && o.hasOwnProperty(k);
	};

	//Given a string, this window.makes = function it safe to use in the URL after a hash (i.e. a safe id for Storekeeper)
	window.encodeItemIdForOrganizer =
	zenario.encodeItemIdForOrganizer =
	//Deprecated aliases
	window.encodeItemIdForStorekeeper =
	zenario.encodeItemIdForStorekeeper =
		function(id) {
			if (1*id == id) {
				return id;
			} else {
				return '~' + encodeURIComponent('' + id).replace(/~/g, '%7E').replace(/%/g, '~');
			}
		};

	//Reverses encodeItemIdForOrganizer()
	window.decodeItemIdForOrganizer =
	zenario.decodeItemIdForOrganizer =
	//Deprecated aliases
	window.decodeItemIdForStorekeeper =
	zenario.decodeItemIdForStorekeeper =
		function(id) {
			if (('' + id).substr(0, 1) == '~') {
				return decodeURIComponent(('' + id).substr(1).replace(/~/g, '%'));
			} else {
				return id;
			}
		};

	window.engToBoolean =
	zenario.engToBoolean = function(text) {
		return text && (text = (text + '').toLowerCase()) && (text != '0' && text != 'false' && text != 'no' && text != 'off')? 1:0;
	};

	window.htmlspecialchars =
	zenario.htmlspecialchars = function(text, preserveLineBreaks, preserveSpaces) {
	
		if (text === undefined || text === null || text === false) {
			return '';
		}
	
		if (typeof text == 'object') {
			text = text.label || text.default_label || text.name || text.field_name;
		}
		
		text = ('' + text).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/\</g, '&lt;').replace(/>/g, '&gt;');
		
		if (preserveSpaces) {
			if (preserveSpaces !== 'asis') {
				text = text.replace(/ /g, '&nbsp;');
			}
		} else {
			text = $.trim(text);
		}
		
		if (preserveLineBreaks) {
			text = text.replace(/\n/g, '<br/>');
		}
		
		return text;
	};

	window.ifNull =
	zenario.ifNull = function(a, b, c) {
		return a? a : (b? b : c);
	};

	window.jsEscape =
	zenario.jsEscape = function(text) {
		return escape(text).replace(/\%u/g, '\\u').replace(/\%/g, '\\x');
	};

	zenario.addBasePath = function(url) {
		if (url === undefined) {
			return undefined;
	
		} else if (url.indexOf('://') == -1 && url.substr(0, 1) != '/') {
			return URLBasePath + url;
	
		} else {
			return url;
		}
	};

	//Take a request string, and check it's formatted correctly
	zenario.addAmp = function(request) {
	
		//For backwards compatability purposes, we'll accept a string with a URL already set, and strip the requests out
		var pos = request.indexOf('?');
		if (pos != -1) {
			request = request.substr(pos+1);
		}
	
		//Add an & to the beginning if needed
		if (request != '' && request.substr(0, 1) != '&') {
			return '&' + request;
		} else {
			return request;
		}
	};

	//Convert an array into a string for a URL if needed
	zenario.urlRequest = function(arr) {
	
		//Have a catch that stops this function being called twice on itself
		if (typeof arr == 'string') {
			return zenario.addAmp(arr);
		}
	
		var request = '';
	
		if (arr) {
			foreach (arr as var i) {
				if (typeof arr[i] != 'object') {
					request += '&' + encodeURIComponent(i) + '=';
				
					if (arr[i] !== undefined && arr[i] !== false && arr[i] !== null) {
						request += encodeURIComponent(arr[i]);
					}
				}
			}
		}
	
		return request;
	};

	//Make a non-asyncornous AJAX call.
	//Note that this is deprecated!
//	zenario.nonAsyncAJAX = function(url, post, json, useCache) {};
	
	//An easy-as-possible drop-in replacement for zenario.nonAsyncAJAX(), which is now deprecated.
	//It returns a zenario.callback object.
		//url: The URL of the request
		//post: Pass some POST requests in here to use POST. Or set to true to use POST without any POST requests.
		//json: Set to true to decode a JSON response
		//useCache: Store the response in the session cache, and use the cached results next time.
			//Won't apply to POST requests.
			//The cache results are cleared automatically if the data_rev in the database changes.
		//retry: If there's an error, show a "retry" button on the error message.
			//Only works in admin mode.
			//Can be a function to call, or true to recall this function
		//timeout: If set, the request will be automatically retried or cancelled after this amount of time.
//	zenario.ajax = function(url, post, json, useCache, retry, timeout, settings) {};
	

	zenario.loadedLibraries = {};
	zenario.loadLibrary = function(path, callback) {
	
		var library;
	
		if (library = zenario.loadedLibraries[path]) {
			if (library.loaded) {
				callback();
			} else {
				library.cb.after(callback);
			}
	
		} else {
			library = zenario.loadedLibraries[path] = {cb: new zenario.callback, loaded: false};
	
			library.cb.after(callback);
	
			$.ajax({
				url: path,
				cache: true,
				dataType: 'script',
				success: function() {
					library.loaded = true;
					library.cb.call();
				}
			});
		}
	};



	//Redirect the user to a URL using JavaScript
	zenario.goToURL = function(URL, useChromeFix) {
		document.location.href = URL;
	
		if (useChromeFix) {
			//Hack to fix a bug with Chrome :(
			setTimeout(
				function() {
					document.location.href = URL;
				}, 500);
		}
	
		return false;
	};

	zenario.phrases = {};
	zenario.loadPhrases = function(vlpClass, code) {
	
		var url = URLBasePath + 'zenario/ajax.php'
			+ '?method_call=loadPhrase'
			+ '&__class__=' + encodeURIComponent(vlpClass)
			+ '&langId=' + encodeURIComponent(zenario.langId);
	
		if (code !== undefined) {
			url += '&__code__=';
			
			if (_.isArray(code)) {
				url += _.map(code, zenario.encodeItemIdForOrganizer).join(',');
			} else {
				url += zenario.encodeItemIdForOrganizer(code);
			}
		}
	
		var phrases = zenario.nonAsyncAJAX(url, false, true, true);
	
		if (!zenario.phrases[vlpClass]) {
			zenario.phrases[vlpClass] = phrases;
		} else {
			$.extend(zenario.phrases[vlpClass], phrases);
		}
	
		return phrases;
	};

	//Look up a Plugin's VLP Phrase
	zenario.phrase = function(vlpClass, text, mrg) {
	
		if (zenario.phrases[vlpClass] === undefined
		 || zenario.phrases[vlpClass][text] === undefined) {
			zenario.loadPhrases(vlpClass, text);
		}
		if (zenario.phrases[vlpClass][text] === null
		 || zenario.phrases[vlpClass][text] === undefined) {
			zenario.phrases[vlpClass][text] = text;
		}
	
		if (!mrg) {
			mrg = {};
		}
	
		var trans = '',
			b,
			bits = ('' + zenario.phrases[vlpClass][text]).split(/\[\[(.*?)\]\]/g);
	
		foreach (bits as b) {
			if (b % 2) {
				if (mrg[bits[b]] !== undefined) {
					trans += mrg[bits[b]];
				}
			} else {
				trans += bits[b];
			}
		}
	
		return trans;
	};

	zenario.nphrase = function(vlpClass, text, pluralText, n, mrg) {
		if (pluralText !== undefined && (1*n) !== 1) {
			return zenario.phrase(vlpClass, pluralText, mrg);
		} else {
			return zenario.phrase(vlpClass, text, mrg);
		}
	}


	//Link to a content item
	zenario.linkToItem = function(cID, cType, request, adminlogin) {
	
		//Accept an input in the form of a Plugin Setting, e.g. "html_123"
		if (!cType && ('' + cID).indexOf('_') !== -1) {
			//Only accept the input if it's in the correct form
			var split = cID.split('_');
				//There should be only one underscore
			if (split[2] === undefined
				//The second part should be a number
			 && split[1] == 1 * split[1]
				//The first part must be a-z
			 && split[0].replace(/\w/g, '') === '') {
				cID = split[1];
				cType = split[0];
			}
		}
	
		if (!cType) {
			cType = 'html';
		}
	
		if (!request) {
			request = '';
		}
	
		var pos,
			canonicalURL,
			basePath = URLBasePath;
		if (adminlogin) {
			basePath += 'zenario/admin/welcome.php';
		} else {
			basePath += zenario.indexDotPHP;
		}
		
		//If we're linking to the content item that we're currently on...
		if (!adminlogin
		 && !zenario.adminId
		 && cID === zenario.cID) {
			//...check to see if it is using a friendly URL...
			if ((canonicalURL = $('link[rel="canonical"]').attr('href'))
			 && (!canonicalURL.match(/\bcID=/))) {
			 	//..and try to keep it if possible
				
				//Get rid of any existing requests
				pos = canonicalURL.indexOf('?');
				if (pos != -1) {
					canonicalURL = canonicalURL.substr(0, pos);
				}
				
				if (request) {
					return canonicalURL + '?' + zenario.urlRequest(request).substr(1);
				} else {
					return canonicalURL;
				}
			}
		}
		
		
		if (cID != 1*cID) {
			return basePath + '?cID=' + cID + zenario.urlRequest(request);
	
		} else {
			return basePath + '?cID=' + cID + '&cType=' + cType + zenario.urlRequest(request);
		}
	};




	//Functions for managing plugin slots

	//Attempt to get the name of a slot from an element within the slot
	zenario.getSlotnameFromEl = function(el, getContainerId) {
		if (typeof el == 'string') {
			return el;
	
		} else if (typeof el == 'object') {
			do {
				if (el.id && el.id == 'colorbox') {
					return zenario.colorboxOpen;
			
				} else if (el.id && el.id.substr(0, 7) == 'plgslt_') {
				
					var hyphen = el.id.indexOf('-'),
						slotName;
				
					//Extract the slot name out from the container id
					if (hyphen == -1) {
						slotName = el.id.substr(7);
					} else {
						slotName = el.id.substr(7, hyphen - 7);
					
						//Check that this matches the correct pattern
						var nestId = el.id.substr(hyphen + 1);
						if (nestId != 1*nestId) {
							continue;
						}
					}
				
					//Check if this is a name of a slot that exists!
					if (!zenario.slots[slotName]) {
						continue;
					}
				
					if (getContainerId) {
						return el.id;
					} else {
						return slotName;
					}
				}
			} while (el = el.parentNode)
		}
		return false;
	};

	zenario.getContainerIdFromEl = function(el) {
		return zenario.getSlotnameFromEl(el, true);
	};

	zenario.getContainerIdFromSlotName = function(slotName) {
		return 'plgslt_' + slotName;
	};

	//Scroll to the top of a slot if needed
	zenario.scrollToSlotTop = function(containerIdSlotNameOrEl, neverScrollDown, time, el, offset) {
		if (typeof containerIdSlotNameOrEl == 'string') {
			containerIdSlotNameOrEl = zenario.ifNull(zenario.get('plgslt_' + containerIdSlotNameOrEl), zenario.get(containerIdSlotNameOrEl));
		}
	
		if (!containerIdSlotNameOrEl) {
			return;
		}
	
		var scrollTop = zenario.scrollTop(undefined, undefined, el);
		var slotTop = $(containerIdSlotNameOrEl).offset().top;
	
		if (offset === undefined) {
			offset = -80;
		}
	
		//Check that the top of the slot is actually visible
		slotTop = Math.max(0, slotTop  + offset);
	
		//Have an option to only scroll up, and never down
		if (neverScrollDown && scrollTop < slotTop) {
			return;
		}
	
		if (time === undefined) {
			time = 700;
		}
	
		//Scroll to the correct place
		zenario.scrollTop(slotTop, time, el);
	};

	//Refresh a plugin in a slot
	zenario.refreshPluginSlot = function(slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache, post) {
	
		if (scrollToTopOfSlot === undefined) {
			scrollToTopOfSlot = true;
		}
	
		if (fadeOutAndIn === undefined) {
			fadeOutAndIn = true;
		}
	
		if (!slotName) {
			return;
		}
	
		if (zenarioA.init) {
			zenarioA.closeSlotControls();
			zenarioA.cancelMovePlugin();
		}
	
		//Remove the Nested Plugin id from the slotname if needed
		slotName = slotName.split('-')[0];
	
		if (!zenario.slots[slotName]) {
			return;
		}
	
		if (!additionalRequests) {
			additionalRequests = '';
		} else {
			additionalRequests = zenario.urlRequest(additionalRequests);
		}
	
		if (zenario.slots[slotName] && zenario.slots[slotName].tabId
		 && additionalRequests.indexOf('&tab=') == -1
		 && additionalRequests.indexOf('&tab_no=') == -1) {
			additionalRequests += '&tab=' + zenario.slots[slotName].tabId;
		}
	
		//Allow a slot to be refreshed by name only, in which case we'll check its current instance id
		if (instanceId == 'lookup') {
			instanceId = zenario.slots[slotName].instanceId;
		}
	
		if (scrollToTopOfSlot && !zenarioAB.isOpen) {
			//Scroll to the top of a slot if needed
			zenario.scrollToSlotTop(slotName, true);
		
			//Don't scroll to the top later if we've already done it now
			scrollToTopOfSlot = false;
		}
	
		//Fade the slot out to give a graphical hint that something is happening
		if (fadeOutAndIn) {
			var fadeOutAndInSelector = (fadeOutAndIn === 1 || fadeOutAndIn === true) ? ('#plgslt_' + slotName) : fadeOutAndIn;
			$(fadeOutAndInSelector).stop(true, true).animate({opacity: .5}, 150);
		}
	
		//Run an AJAX request to reload the contents
		var html,
			url = zenario.pluginAJAXURL(slotName, additionalRequests, instanceId); 
	
		//if (!post && useCache && (html = zenario.checkSessionStorage(url))) {
		//	zenario.replacePluginSlotContents(slotName, instanceId, html, additionalRequests, recordInURL, scrollToTopOfSlot);
		//} else {
		//	//(I'm using jQuery so that this is done asyncronously)
		//	var method = 'GET';
		//	if (post) {
		//		method = 'POST';
		//	}
		//	
		//	$.ajax({
		//		dataType: 'text',
		//		data: post,
		//		method: method,
		//		url: url,
		//		success: function(html) {
		//			if (useCache) {
		//				zenario.setSessionStorage(html, url);
		//			}
		//		
		//			zenario.replacePluginSlotContents(slotName, instanceId, html, additionalRequests, recordInURL, scrollToTopOfSlot);
		//		}
		//	});
		//}
		
		zenario.ajax(url, post, false, true).after(function(html) {
			zenario.replacePluginSlotContents(slotName, instanceId, html, additionalRequests, recordInURL, scrollToTopOfSlot);
		});
	};

	//Call a signal/event on all included Modules, if they have it defined
	zenario.sendSignal = function(signalName, data) {
	
		if (zenario.signalsInProgress[signalName]) {
			return;
		}
		zenario.signalsInProgress[signalName] = true;
	
		var id,
			module,
			returnValue,
			returnValues = [];
		foreach (zenario.modules as id) {
			if (module = window[zenario.modules[id].moduleClassName]) {
				if (typeof module[signalName] == 'function') {
					returnValue = module[signalName](data);
				
					if (returnValue !== undefined) {
						returnValues.push(returnValue);
					}
				}
			}
		}
	
		delete zenario.signalsInProgress[signalName];
		return returnValues;
	};


	zenario.getMouseX = function(e) {
		if (e.pageX != undefined) {
			return e.pageX;
		} else {
			return e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
		}
	};

	zenario.getMouseY = function(e) {
		if (e.pageY != undefined) {
			return e.pageY;
		} else {
			return e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
		}
	};

	zenario.scrollTop = function(value, time, el) {
	
		if (el === undefined) {
			el = zenario.browserIsSafari()? 'body' : 'html';
		}
	
		if (value === undefined) {
			return $(el).scrollTop();
		} else if (!time) {
			return $(el).scrollTop(value);
		} else {
			$(el).animate({ scrollTop: value }, time);
		}
	};

	zenario.scrollLeft = function(value) {
		var $body = $(zenario.browserIsSafari()? 'body' : 'html');
	
		if (value === undefined) {
			return $body.scrollLeft();
		} else {
			return $body.scrollLeft(value);
		}
	};

	zenario.versionOfIE = function(n) {
		if (/opera|OPERA/.test(navigator.userAgent)) {
			return false;
		}
		var ver = /MSIE ([0-9]{1,}[\.0-9]{0,})/.exec(navigator.userAgent);
		return ver && ver[1] && 1*ver[1];
	};

	zenario.browserIsIE = function(n) {
		var ver = zenario.versionOfIE();
		
		return ver && (n? ver <= n: true);
	};

	zenario.browserIsChrome = function() {
		return /Chrome/.test(navigator.userAgent);
	};

	zenario.browserIsFirefox = function() {
		return /Firefox/.test(navigator.userAgent);
	};

	zenario.browserIsRetina = function() {
		return window.devicePixelRatio > 1;
	};

	zenario.browserIsSafari = function() {
		return /Safari/.test(navigator.userAgent);
	};

	zenario.browserIsWebKit = function() {
		return /WebKit/.test(navigator.userAgent);
	};

	zenario.browserIsOpera = function() {
		return /Opera/.test(navigator.userAgent);
	};

	zenario.browserIsiPad = function() {
		return /iPad/.test(navigator.userAgent);
	};

	zenario.browserIsiPhone = function() {
		return /iPhone/.test(navigator.userAgent);
	};

	zenario.browserIsMobile = function() {
		return zenario.browserIsiPad() || zenario.browserIsiPhone();
	};

	zenario.ishttps = function() {
		return window.location
			&& window.location.protocol === 'https:';
	};

	zenario.httpOrhttps = function() {
		return zenario.ishttps()? 'https://' : 'http://';
	};
	
	
	zenario.actAfterDelayIfNotSuperseded = function(type, fun, delay) {
		if (!delay) {
			delay = 900;
		}
	
		if (!zenario.adinsActions[type]) {
			zenario.adinsActions[type] = 0;
		}
		var thisAttemptNum = ++zenario.adinsActions[type];
		
		if (fun !== undefined) {
			setTimeout(
				function() {
					//Catch to stop outdated/spammed requests
					if (thisAttemptNum == zenario.adinsActions[type]) {
						fun();
					}
				}, delay);
		}
	};
	
	zenario.clearAllDelays = function(type) {
		if (type) {
			delete zenario.adinsActions[type];
		} else {
			zenario.adinsActions = {};
		}
	};
	

	//Disable any parent elements of element from scrolling
	zenario.disableBackgroundScrolling = function(element) {
		$(element).on('DOMMouseScroll mousewheel', function(ev) {
			var $this = $(this),
				scrollTop = this.scrollTop,
				scrollHeight = this.scrollHeight,
				height = $this.height(),
				delta = (ev.type == 'DOMMouseScroll' ?
					ev.originalEvent.detail * -40 :
					ev.originalEvent.wheelDelta),
				up = delta > 0;
		
			var prevent = function() {
				ev.stopPropagation();
				ev.preventDefault();
				ev.returnValue = false;
				return false;
			}
		
			if (!up && -delta > scrollHeight - height - scrollTop) {
				// Scrolling down, but this will take us past the bottom.
				$this.scrollTop(scrollHeight);
				return prevent();
			} else if (up && delta > scrollTop) {
				// Scrolling up, but this will take us past the top.
				$this.scrollTop(0);
				return prevent();
			}
		});
	};
	
	
	
	
	zenario.AJAXLink = function(moduleClassName, requests) {
		return URLBasePath + 'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=handleAJAX' + zenario.urlRequest(requests);
	};
	
	zenario.pluginAJAXLink = function(moduleClassName, slotNameOrContainedElement, requests) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			instanceId = zenario.slots[slotName] && zenario.slots[slotName].instanceId;
		
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=handlePluginAJAX' +
			'&cID=' + zenario.cID +
			'&cType=' + zenario.cType +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion
		   : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
			zenario.urlRequest(requests);
	};
	
	zenario.showFileLink = function(moduleClassName, requests) {
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showFile' +
			zenario.urlRequest(requests);
	};
	
	zenario.showFloatingBoxLink = function(moduleClassName, slotNameOrContainedElement, requests) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			instanceId = zenario.slots[slotName] && zenario.slots[slotName].instanceId;
		
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showFloatingBox' +
			'&cID=' + zenario.cID +
			'&cType=' + zenario.cType +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion
		   : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
			zenario.urlRequest(requests);
	};
	
	zenario.showSingleSlotLink = function(moduleClassName, slotNameOrContainedElement, requests, hideLayout) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			instanceId = zenario.slots[slotName] && zenario.slots[slotName].instanceId;
		
		if (hideLayout === undefined) {
			hideLayout = true;
		}
		
		return zenario.linkToItem(zenario.cID, zenario.cType,
			'moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showSingleSlot' +
			(hideLayout? '&hideLayout=1' : '') +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion
		   : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
			zenario.urlRequest(requests));
	};
	
	zenario.showImageLink = function(moduleClassName, requests) {
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showImage' +
			zenario.urlRequest(requests);
	};
	
	zenario.showStandalonePageLink = function(moduleClassName, requests) {
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showStandalonePage' +
			zenario.urlRequest(requests);
	};


});