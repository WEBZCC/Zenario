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

//This file works like local.inc.php, but should contain any updates for local admin tables
//(i.e. updates that won't be re-run after a site-reset)




//Rework to the restricted admin permissions
ze\dbAdm::revision(48600
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	CHANGE COLUMN `permissions` `old_permissions`
		enum('all_permissions','specific_actions','specific_languages','specific_menu_areas')
		NOT NULL DEFAULT 'specific_actions'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	ADD COLUMN `permissions`
		enum('all_permissions', 'specific_actions', 'specific_areas')
		NOT NULL DEFAULT 'specific_actions'
	AFTER `status`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]admins`
	SET `permissions` = 'all_permissions'
	WHERE old_permissions = 'all_permissions'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]admins`
	SET `permissions` = 'specific_areas'
	WHERE old_permissions IN ('specific_languages', 'specific_menu_areas')
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	DROP COLUMN `old_permissions`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	DROP COLUMN `specific_menu_areas`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	MODIFY COLUMN `specific_languages` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	ADD COLUMN `specific_content_types` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci
	AFTER `specific_content_items`
_sql


//
//	Zenario 9.2
//

//Drop the "All content items in these languages" admin permission option
);	ze\dbAdm::revision(54250
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	DROP COLUMN `specific_languages`
_sql


//
//	Zenario 9.3
//

//In 9.3, we're going through and fixing the character-set on several columns that should
//have been using "ascii"
);	ze\dbAdm::revision(55140
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admin_organizer_prefs`
	MODIFY COLUMN `checksum` varchar(22) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '{}'
_sql


//
//	Zenario 9.4
//

//In 9.4, we started counting admin failed logins.
//have been using "ascii"
);	ze\dbAdm::revision(57301
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	ADD COLUMN `failed_login_count_since_last_successful_login` int unsigned NOT NULL DEFAULT 0 AFTER `last_login_ip`
_sql
);