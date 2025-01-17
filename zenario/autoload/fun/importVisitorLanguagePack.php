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



//This script supports two formats of data:
	//The simpliest case is where there are only one row of column headings,
	//and all the data is in tabular format.
	//The more complex case is where there are multiple blocks of data,
	//with subheadings containg data on the next block.
//The logic below tries to detect when it meets the start of a new block, and tries to read the column headers
//from the first row.
//It then tries to work out whether the block is a subheading or if the block is data. If it's a subheading,
//the values are written down and applies to the next block of data.
$permsChecked = [];
$subheadings = [
	'language_id' => '',
	'module_class_name' => 'zenario_common_features'
];
$emptyRow = [
	'phrase_code' => '',
	'reference_text' => '',
	'translation' => ''
];
$knownColumnHeaders = [
	'language id' => 'language_id',
	'target language id' => 'language_id',
	'plugin' => 'module_class_name',
	'module' => 'module_class_name',
	'phrase code' => 'phrase_code',
	'reference text' => 'reference_text',
	'translation' => 'translation'
];
$columns = false;
$justHadAnEmptyLine = true;

//Keep some results on what happens
$numberOf = ['upload_error' => false, 'wrong_language' => false, 'added' => 0, 'updated' => 0, 'protected' => 0];

//Work out whether this is a spreadsheet or a CSV file and load the file appropriately
$mimeType = ze\file::mimeType(str_replace('.php', '', ($realFilename ?: $file)));
if (\ze::in($mimeType, 'text/csv', 'text/comma-separated-values')) {
	$csv = true;
	
	require_once CMS_ROOT. 'zenario/libs/manually_maintained/mit/parsecsv/parsecsv.lib.php';
	
	$pCSV = new parseCSV();
	
	$f = fopen($file, "r");

} else {
	$csv = false;

	require_once CMS_ROOT. 'zenario/libs/manually_maintained/lgpl/PHPExcel/Classes/PHPExcel.php';
	
	switch ($mimeType) {
		case 'application/vnd.ms-excel':
			$objReader = PHPExcel_IOFactory::createReader('Excel5');
			break;
		
		case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
			$objReader = PHPExcel_IOFactory::createReader('Excel2007');
			break;
		
		case 'application/vnd.oasis.opendocument.spreadsheet':
			$objReader = PHPExcel_IOFactory::createReader('OOCalc');
			break;
		
		default:
			$numberOf['upload_error'] = true;
			return $numberOf;
	}
	
	$objPHPExcel = $objReader->load($file);
	$sheet = $objPHPExcel->getActiveSheet();
	$maxI = $sheet->getHighestRow();
}

//Loop through each row
for ($i = 1; true; ++$i) {
	//Work out whether this is a spreadsheet or a CSV file and load the next row appropriately
	if ($csv) {
		if (!$row = fgets($f)) {
			break;
		}
		
		$row = $pCSV->parse_string("0,1,2,3,4,5\n". $row. "\n");
		$row = $row[0] ?? false;
		$rowCount = (is_array($row) ? count($row) : 0);
		
		//Ignore empty lines, though remember that we've had them
		if (!$row || $rowCount == 0 || $row[0] === '' || $row[0] === null) {
			$justHadAnEmptyLine = true;
			continue;
		}
	} else {
		if ($i > $maxI) {
			break;
		}
		
		$row = [];
		for ($j = 0; $j < 5; ++$j) {
			if (!($row[$j] = $sheet->getCellByColumnAndRow($j, $i)->getCalculatedValue())) {
				$row[$j] = '';
			}
		}
		
		//Ignore empty lines, though remember that we've had them
		if (!$row[0]) {
			$justHadAnEmptyLine = true;
			continue;
		}
		
		foreach ($row as &$cell) {
			if (is_object($cell)) {
				$cell = $cell->getPlainText();
			}
		}
	}
	
	
	//Look out for column headers if we've not had them already,
	//or if we've just had an empty line and we see what looks like a column header
	if (!$columns || $justHadAnEmptyLine) {
		$lookForColumnHeaders = [];
		$headersFound = 0;
		
		for ($j = 0; $j < 5; ++$j) {
			if (!empty($row[$j])) {
				$header = strtolower($row[$j]);
			
				if (substr($header, -12) == ' translation') {
					$lookForColumnHeaders['translation'] = $j;
					++$headersFound;
			
				} elseif (!empty($row[$j]) && !empty($knownColumnHeaders[$header])) {
					$lookForColumnHeaders[$knownColumnHeaders[$header]] = $j;
					++$headersFound;
				}
			}
		}
		
		//If these look like column headers, note down what they were and then move to the next line
		if ($headersFound >= 4
		 || ($headersFound >= 1 && (!$columns || $headersFound == count($row)))) {
		
			$columns = $lookForColumnHeaders;
			$justHadAnEmptyLine = false;
			continue;
		}
	}
	
	//We can't start reading in data until we've had the column headers!
	if (!$columns) {
		continue;
	}
	
	//Look for changes to the subheadings (e.g. if the language id or module change)
	foreach ($subheadings as $col => &$value) {
		if (isset($columns[$col]) && !empty($row[$columns[$col]])) {
			$value = $row[$columns[$col]];
		}
	}
	
	if ($forceLanguageIdOverride !== false) {
		$subheadings['language_id'] = $forceLanguageIdOverride;
	}
	
	//Load in data on the current row (e.g. the reference text, translation and code)
	$thisRow = $emptyRow;
	foreach ($thisRow as $col => &$value) {
		if (isset($columns[$col]) && !empty($row[$columns[$col]])) {
			$value = $row[$columns[$col]];
		}
	}
	
	//Check to see if this row has a phrase defined
	if ($thisRow['phrase_code']) {
		$code = $thisRow['phrase_code'];
	} else {
		$code = $thisRow['reference_text'];
	}
	
	$justHadAnEmptyLine = false;
	
	//If there is no phrase defined on this row then keep going!
	if (!$code) {
		continue;
	}
	
	//Report an error and stop if we find a code before we know the language
	if (!$subheadings['language_id']) {
		$numberOf['upload_error'] = true;
		break;
	
	//If all we needed to do was find out what language was in the file, stop now
	} elseif ($scanning === 'language id')  {
		break;
	
	//If we've just scanning to see what's in the file, keep going but don't actually do the import
	} elseif ($scanning === 'full scan' || $scanning === 'number and file')  {
		++$numberOf['added'];
	
	} elseif ($checkPerms
		   && !isset($permsChecked[$subheadings['language_id']])
		   && !($permsChecked[$subheadings['language_id']] = \ze\priv::onLanguage('_PRIV_MANAGE_LANGUAGE_PHRASE', $subheadings['language_id']))
	) {
		echo
			\ze\admin::phrase('This spreadsheet contains phrases in [[lang]], which you do not have permissions to change',
				['lang' => \ze\lang::name($subheadings['language_id'])]);
		exit;
	
	//Otherwise try to import this phrase
	} else {
		\ze\phraseAdm::importVisitorPhrase($subheadings['language_id'], $subheadings['module_class_name'], $code, $thisRow['translation'], $adding, $numberOf);
	}
}


if ($csv) {
	fclose($f);
}

$languageIdFound = $subheadings['language_id'];
return $numberOf;