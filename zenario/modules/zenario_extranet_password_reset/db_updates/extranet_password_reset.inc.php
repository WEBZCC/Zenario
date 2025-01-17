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
ze\dbAdm::revision(1, "
	INSERT IGNORE [[DB_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`,
		`use_standard_email_template`
	) VALUES (
		 'zenario_extranet_password_reset__to_user_password_reset_en',
		 'To User: Password reset',
		 'Password reset',
		 '<p>Dear [[first_name]] [[last_name]],</p>
		 <p>You (or someone pretending to be you) asked for a reset of your password on <a href=\"[[cms_url]]\">[[cms_url]]</a></p>
		 <p>To reset your password, please click the link below</p>
		 <p><a href=\"[[reset_url]]\">[[reset_url]]</a></p>
		 <p>&nbsp;</p>
		 <p>This is an auto-generated email from [[cms_url]] .</p>
		',
		 NOW(),
		 " .(int) ($_SESSION['admin_userid'] ?? false) . ",
		 0,
		 1
		)
"
); ze\dbAdm::revision(3,

<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_extranet_password_reset'
	WHERE `code` = 'zenario_extranet_password_reset__to_user_password_reset_en'
_sql

); ze\dbAdm::revision(9,

<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `body` = 
		'<p>Dear [[first_name]] [[last_name]],</p>
		 <p>You (or someone pretending to be you) asked for a reset of your password on <a href=\"[[cms_url]]\">[[cms_url]]</a></p>
		 <p>To reset your password, please click the button below:</p>
		 <p>&nbsp;</p>
		 <p style=\"text-align: center;\"><a style=\"background: #015ca1; color: white; text-decoration: none; padding: 20px 40px; font-size: 16px;\" href=\"[[reset_url]]\">RESET PASSWORD</a></p>
		 <p>&nbsp;</p>
		 <p>If the above link doesn\'t work, copy the following link and paste it into your browser:</p>
		 <p><a href=\"[[reset_url]]\">[[reset_url]]</a></p>
		 <p>&nbsp;</p>
		 <p>This is an auto-generated email from [[cms_url]] .</p>
		'
	WHERE `code` = 'zenario_extranet_password_reset__to_user_password_reset_en'
_sql

);
?>