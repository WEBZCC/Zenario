<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_email_template_manager extends module_base_class {

	public static function getTemplateNames() {
		$rv = array();
		$result = getRows('email_templates', array('code', 'template_name'), array(), 'template_name');
		while ($row = sqlFetchAssoc($result)) {
			$rv[$row['code']] = $row['template_name'];
		}
		return $rv;
	}


	public static function getTemplateById($id) {
		return getRow('email_templates', true, array('id' => $id));
	}

	public static function getTemplateByCode($code) {
		return getRow('email_templates', true, array('code' => $code));
	}

	public static function getTemplatesByNameIndexedByCode($name, $strict = true){
		
		if ($strict) {
			$where = array('template_name' => $name);
		} else {
			$where = array('template_name' => array('LIKE' => '%'. $name. '%'));
		}
		
		$result = getRows('email_templates', true, $where);
		
		$rv = array();
		while ($row = sqlFetchAssoc($result)){
			$rv[$row['code']] = $row;
		}
		return $rv;
	}
	
	public static function getLogRecordById($id) {
		return getRow(ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX. 'email_template_sending_log', true, array('id' => $id));
	}
	
		
	
	public static function sendEmails(
		$rcpts, $subject, $addressFrom, $nameFrom, $body, $mergeFields=array(), 
		$attachments=array(), $attachmentFilenameMappings=array(), 
		$templateNo = 0, $disableHTMLEscaping = false, 
		$addressReplyTo = false, $nameReplyTo = false,
		$ccs = '', $bccs = '', $debugOverride = ''
	){

		mb_regex_encoding('UTF-8');
		foreach ($mergeFields as $K=>&$V) {
			$search = '\[\[' . $K . '\]\]';
			
			if (is_array($disableHTMLEscaping)? !empty($disableHTMLEscaping[$K]) : $disableHTMLEscaping) {
				$replace = &$V;
			} else {
				$replace = nl2br(htmlspecialchars($V));
			}
			
			$body = mb_ereg_replace($search, $replace, $body);
			$subject = mb_ereg_replace($search, $replace, $subject);
			$nameFrom = mb_ereg_replace($search, $replace, $nameFrom);
			$addressFrom = mb_ereg_replace($search, $replace, $addressFrom);
			unset($replace);
		}
		
		$regex_filter = '\[\[[^\]]*\]\]';
		$body = mb_ereg_replace ($regex_filter,'',$body);
		$subject = mb_ereg_replace ($regex_filter,'',$subject);
		$nameFrom = mb_ereg_replace ($regex_filter,'',$nameFrom);
		$addressFrom = mb_ereg_replace ($regex_filter,'',$addressFrom);
		
		if($addressReplyTo) {
			$addressReplyTo = mb_ereg_replace ($regex_filter,'',$addressReplyTo);
		}
		if($nameReplyTo) {
			$nameReplyTo = mb_ereg_replace ($regex_filter,'',$nameReplyTo);
		}
		
		$result = true;
		
		foreach (array_unique(explodeAndTrim(str_replace(';', ',', $rcpts))) as $addressTo) {
			/*
			(
	$subject, $body, $addressTo, &$addressToOverriddenBy,
	$nameTo = false, $addressFrom = false, $nameFrom = false, 
	$attachments = array(), $attachmentFilenameMappings = array(),
	$precedence = 'bulk', $isHTML = true, $exceptions = false,
	$addressReplyTo = false, $nameReplyTo = false, $warningEmailCode = false,
	$ccs = '', $bccs = ''
)
			*/
			
			if($debugOverride){
				//template in debug mode
				$debugEmails = array_unique(explodeAndTrim(str_replace(';', ',', $debugOverride)));
				foreach($debugEmails as $debugEmail){
					$addressToOverriddenBy = false;
					$thisResult = sendEmail(
						$subject, $body, $debugEmail, $addressToOverriddenBy,
						false, $addressFrom, $nameFrom,
						$attachments, $attachmentFilenameMappings,
						'bulk', true, false,
						$addressReplyTo, $nameReplyTo, false,
						$ccs, $bccs
					);
					
					self::logEmail(
						$subject, $body, $addressTo, $addressToOverriddenBy? $addressToOverriddenBy : $debugOverride,
						$addressFrom, $nameFrom,
						$attachments, $attachmentFilenameMappings,
						$templateNo, $thisResult,
						$_POST, 
						$addressReplyTo, $nameReplyTo
					);
					
				}
			}else{
				//$debugOverride? $debugOverride : $addressTo, $addressToOverriddenBy
				$thisResult = sendEmail(
					$subject, $body,$addressTo, $addressToOverriddenBy,
					false, $addressFrom, $nameFrom,
					$attachments, $attachmentFilenameMappings,
					'bulk', true, false,
					$addressReplyTo, $nameReplyTo, false,
					$ccs, $bccs
				);
			
				self::logEmail(
					$subject, $body, $addressTo, $addressToOverriddenBy? $addressToOverriddenBy : $debugOverride,
					$addressFrom, $nameFrom,
					$attachments, $attachmentFilenameMappings,
					$templateNo, $thisResult,
					$_POST, 
					$addressReplyTo, $nameReplyTo
				);
			}
			
			$result &= $thisResult;
		}
		return $result;
	}
	
	public static function logEmail(
		$subject, &$body, $addressTo, $addressToOverriddenBy,
		$addressFrom, $nameFrom,
		$attachments, $attachmentFilenameMappings,
		$templateNo, $status,
		$senderCmsObjectArray = array(),
		$addressReplyTo = false, $nameReplyTo = false
	) {
	
		self::clearOldData();
		
		$sql = "
			INSERT INTO ". DB_NAME_PREFIX. ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX. "email_template_sending_log SET
				module_id = ". (int) getRow('plugin_instances', 'module_id', array('id' => ($senderCmsObjectArray['instanceId'] ?? false))). ",
				instance_id = ". (int) ($senderCmsObjectArray['instanceId'] ?? false). ",
				content_id = ". (int) ($senderCmsObjectArray['cID'] ?? false). ",
				content_type = '". sqlEscape($senderCmsObjectArray['cType'] ?? false). "',
				content_version = ". (int) ($senderCmsObjectArray['cVersion'] ?? false). ",
				email_template_id = ". (int) $templateNo. ",
				email_template_name = '". sqlEscape(getRow('email_templates', 'template_name', array('id' => $templateNo))). "',
				email_subject = '". sqlEscape($subject). "',
				email_address_to = '". sqlEscape($addressTo). "',
				email_address_to_overridden_by = '". sqlEscape($addressToOverriddenBy). "',
				email_address_from = '". sqlEscape($addressFrom). "',
				email_name_from = '". sqlEscape($nameFrom). "',
				attachment_present = ". (int) !empty($attachments). ",
				sent_datetime = '". sqlEscape(now()). "',
				`status` = '". sqlEscape($status? 'success' : 'failure'). "'";
		
		if ($addressReplyTo) {
			$sql .= ",
				email_address_replyto = '". sqlEscape($addressReplyTo). "'";
		}
		if ($nameReplyTo) {
			$sql .= ",
				email_name_replyto = '". sqlEscape($nameReplyTo). "'";
		}
		
		if (strlen($body) < 100000) {
			$sql .= ",
				email_body = '". sqlEscape($body). "'";
		} else {
			$sql .= ",
				email_body = '". sqlEscape(adminPhrase('Body too large to save')). "'";
		}
		
		sqlUpdate($sql, false, false);
	}
	
	
	public static function clearOldData(){
		if($days = setting('period_to_delete_the_email_template_sending_log')){
			if(is_numeric($days)){
				$today = date('Y-m-d');
				$date = date('Y-m-d', strtotime('-'.$days.' day', strtotime($today)));
				if($date){
					$sql = " 
						DELETE FROM ". DB_NAME_PREFIX. ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX. "email_template_sending_log
						WHERE sent_datetime < '".sqlEscape($date)."'";
					sqlUpdate($sql);
				}
			}
		}
	}
	
	
	public static function sendEmailsUsingTemplate(
		$rcpts, $templateCode, $mergeFields = array(),
		$attachments = array(), $attachmentFilenameMappings = array(),
		$disableHTMLEscaping = false, $addressReplyTo = false, $nameReplyTo = false
	) {
		if ($template = self::getTemplateByCode($templateCode)) {
			
			if ($template['head']) {
				static::putHeadOnBody($template['head'], $template['body']);
			}
			
			if (self::sendEmails(
				$rcpts,
				$template['subject'],  
				$template['email_address_from'],
				$template['email_name_from'],
				$template['body'], 
				$mergeFields,
				$attachments,
				$attachmentFilenameMappings,
				$template['id'],
				$disableHTMLEscaping,
				$addressReplyTo,
				$nameReplyTo,
				!$template['debug_override'] && $template['send_cc']? $template['cc_email_address'] : '',
				!$template['debug_override'] && $template['send_bcc']? $template['bcc_email_address'] : '',
				$template['debug_override']? $template['debug_email_address'] : ''
			)) {
				
				$sql = "
					UPDATE ". DB_NAME_PREFIX. "email_templates SET 
						last_sent = NOW()
					WHERE id = ". (int) $template['id'];
				sqlQuery($sql);

				return true;
			}
		}
		
		return false;
	}
	
		//If this email template has HTML in the <head>, we'll need to send the email as a full webpage
	public static function putHeadOnBody(&$head, &$body) {
		
		if ($head && trim($head)) {
			$body =
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
'. $head. '
</head>
<body>
'. $body. '
</body>
</html>';
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require funIncPath(__FILE__, __FUNCTION__);
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	
	
	//Deprecated, don't call!
	public static function getTemplateNamesOrderedByName() {
		return static::getTemplateNames();
	}
	
	//Deprecated, don't call!
	public static function getTemplates(){
		$rv=array();
		$sql = 'SELECT 
					id,
					code,
					template_name,
					subject,
					email_address_from,
					email_name_from,
					body
				FROM '
					. DB_NAME_PREFIX.  'email_templates';
		$result = sqlQuery($sql);
		while($row=sqlFetchAssoc($result))
			$rv[$row['template_name']]=$row;
		return $rv;
	}
}