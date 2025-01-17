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

/*
	This file contains JavaScript source code.
	The code here is not the code you see in your browser. Before thus file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (thus is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and organizer.wrapper.js.php for step (3).
*/


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes
) {
	"use strict";

//Note: extensionOf() and methodsOf() are our shortcut functions for class extension in JavaScript.
	//extensionOf() creates a new class (optionally as an extension of another class).
	//methodsOf() allows you to get to the methods of a class.
var methods = methodsOf(
	panelTypes.hierarchy_documents = extensionOf(panelTypes.hierarchy_with_lazy_load)
);


methods.getHierarchyMicroTemplateHTML = function(m) {
	return thus.microTemplate('zenario_organizer_hierarchy_documents', m)
}

methods.getItems = function() {
	var m = thus.getMergeFieldsForItemsAndColumns(),
		i, j, l, item, name, column, cell,
		line_number,
		max = -1,
		lineHeader, lineHeaders = {};
	
	// Get columns on lines
	foreach (thus.tuix.columns as name => column) {
		if (line_number = 1*column.document_line_number) {
			lineHeaders[name] = {
				line_number: line_number,
				css_class: column.css_class
			}
		}
	}
	
	// Split values onto multiple lines
	if (!_.isEmpty(lineHeaders)) {
		foreach (m.items as i => item) {
			
			var lineIndex = 0,
				lineValues = {};
			
			foreach (item.cells as j => cell) {
				if (cell.id
				 && cell.value !== ''
				 && (lineHeader = lineHeaders[cell.id])) {
					line_number = lineHeader.line_number;
					
					if (!lineValues[line_number]) {
						lineValues[line_number] = {
							values: [],
							index: ++lineIndex
						};
					}
					lineValues[line_number].values.push({
						value: cell.value,
						css_class: lineHeader.css_class
					});
					
					if (max < line_number) {
						max = line_number;
					}
				}
			}
			
			item.lines = [];
			for (l = 0; l <= max; l++) {
				if (lineValues[l]) {
					item.lines.push(lineValues[l]);
				}
			}
		}
	}
	
	return m;
}


}, zenarioO.panelTypes);