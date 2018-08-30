<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

class content {



	const currentLangIdFromTwig = true;
	//Formerly "currentLangId()"
	public static function currentLangId() {
		return \ze::$langId ?? $_SESSION['user_lang'] ?? \ze::$defaultLang;
	}
	
	const visitorLangIdFromTwig = true;
	//Formerly "visitorLangId()"
	public static function visitorLangId() {
		return \ze::$visLang ?? $_SESSION['user_lang'] ?? \ze::$defaultLang;
	}





	//Special case for if the installer needs to be run
	//Formerly "showStartSitePageIfNeeded()"
	public static function showStartSitePageIfNeeded($reportDBOutOfDate = false) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}





	//Write the URLBasePath, and other related JavaScript variables, to the page
	//Formerly "CMSWritePageHead()"
	public static function pageHead($prefix, $mode = false, $includeOrganizer = false, $overrideFrameworkAndCSS = false) {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	//Formerly "CMSWritePageBody()"
	public static function pageBody($extraClassNames = '', $attributes = '', $showSitewideBodySlot = false, $includeAdminToolbar = false) {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	//Write the URLBasePath, and other related JavaScript variables, to the page
	//Formerly "CMSWritePageFoot()"
	public static function pageFoot($prefix, $mode = false, $includeOrganizer = true, $includeAdminToolbar = true, $defer = false) {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}





	//Formerly "getContentTypes()"
	public static function getContentTypes($contentType = false, $onlyCreatable = false) {
	
		$key = [];
		if ($contentType) {
			$key['content_type_id'] = $contentType;
		}
		if ($onlyCreatable) {
			$key['is_creatable'] = true;
		}
		return \ze\row::getAssocs('content_types', ['content_type_id', 'content_type_name_en', 'default_layout_id'], $key, 'content_type_id');
	}

	//Formerly "getContentTypeName()"
	public static function getContentTypeName($cType) {
		return \ze\row::get('content_types', 'content_type_name_en', $cType);
	}



	//Formerly "getCIDAndCTypeFromTagId()"
	public static function getCIDAndCTypeFromTagId(&$cID, &$cType, $tagId) {
		if ($tagId
		 && ($tagId = explode('_', trim($tagId), 2))
		 && (!empty($tagId[1]))
		 && (!preg_match('/[^a-zA-Z]/', $tagId[0]))
		 && (!preg_match('/[^0-9]/', $tagId[1]))
		 && ($cType = $tagId[0])
		 && ($cID = (int) $tagId[1])) {
			return true;
		} else {
			return $cID = $cType = false;
		}
	}

	//Formerly "getEquivIdAndCTypeFromTagId()"
	public static function getEquivIdAndCTypeFromTagId(&$equivId, &$cType, $tagId) {
		if ((\ze\content::getCIDAndCTypeFromTagId($equivId, $cType, $tagId))
		 && ($equivId = \ze\content::equivId($equivId, $cType))) {
			return true;
		} else {
			return $equivId = $cType = false;
		}
	}

	//Formerly "contentVersion()"
	public static function version($cID, $cType) {
		return \ze\row::get('content_items', \ze\priv::check()? 'admin_version' : 'visitor_version', ['id' => $cID, 'type' => $cType]);
	}

	//Formerly "isDraft()"
	public static function isDraft($statusOrCID, $cType = false, $cVersion = false) {
	
		if (is_numeric($statusOrCID) && $cType) {
			if (!($content = \ze\row::get('content_items', ['admin_version', 'status'], ['id' => $statusOrCID, 'type' => $cType]))
			 || ($cVersion && $cVersion != $content['admin_version'])) {
				return false;
			}
		
			$statusOrCID = $content['status'];
		}
	
		return $statusOrCID == 'first_draft'
			|| $statusOrCID == 'published_with_draft'
			|| $statusOrCID == 'hidden_with_draft'
			|| $statusOrCID == 'trashed_with_draft';
	}

	//Formerly "isPublished()"
	public static function isPublished($statusOrCID, $cType = false, $cVersion = false) {
	
		if (is_numeric($statusOrCID) && $cType) {
			if (!($content = \ze\row::get('content_items', ['visitor_version', 'status'], ['id' => $statusOrCID, 'type' => $cType]))
			 || ($cVersion && $cVersion != $content['visitor_version'])) {
				return false;
			}
		
			$statusOrCID = $content['status'];
		}
	
		return $statusOrCID == 'published'
			|| $statusOrCID == 'published_with_draft';
	}

	//Get an item's description
	//	function isSpecialPage($cID, $cType) {}

	//Automatically generate SQL to search through Content, for example for a content list
	//A bit of a techy function so we've included the full code here, so you can see exactly what it does
	//Formerly "sqlToSearchContentTable()"
	public static function sqlToSearchContentTable($hidePrivateItems = true, $onlyShow = false, $extraJoinSQL = '', $includeSpecialPages = false) {


		$sql = "
			FROM ". DB_PREFIX. "content_item_versions AS v
			INNER JOIN ". DB_PREFIX. "content_items AS c
			   ON v.id = c.id
			  AND v.type = c.type
			INNER JOIN ". DB_PREFIX. "translation_chains AS tc
			   ON c.equiv_id = tc.equiv_id
			  AND c.type = tc.type";
	
		if (\ze\priv::check()) {
			$sql .= "
			  AND v.version = c.admin_version
			  AND c.status IN ('first_draft','published_with_draft','hidden_with_draft','trashed_with_draft','published')";
		} else {
			$sql .= "
			  AND v.version = c.visitor_version";
		}
	
		$sql .= "
			". $extraJoinSQL;
	
	
		$userId = \ze\user::id();
	
		//Filter by whether the current viewer can see each item
		if (\ze\priv::check()) {
			//Show Admins everything, even including private drafts
			$sql .= "
			WHERE TRUE";
	
		} elseif (!$hidePrivateItems) {
			//If show_private_items is enabled, show all items
			$sql .= "
			WHERE TRUE";
		
		} elseif (!$userId && $onlyShow == 'private') {
			//Private items can only be seen by logged in users...
			$sql .= "
			WHERE FALSE";
		  
		} elseif (!$userId || $onlyShow == 'public') {
			//If the visitor is not logged in, only show public items
			$sql .= "
			WHERE tc.privacy IN ('public', 'logged_out')";
	
		} else {
			//If the visitor is logged in, check which items they can see
		
			$groupsList = "FALSE";
			foreach (\ze\user::groups($userId) as $groupId => $groupName) {
				$sql .= "
					LEFT JOIN ". DB_PREFIX. "group_link AS gcl". $groupId. "
					   ON gcl". $groupId. ".link_from = 'chain'
					  AND gcl". $groupId. ".link_from_id = tc.equiv_id
					  AND gcl". $groupId. ".link_from_char = tc.type
					  AND gcl". $groupId. ".link_to = 'group'
					  AND gcl". $groupId. ".link_to_id = ". $groupId;
			
				if ($groupsList == "FALSE") {
					$groupsList = "";
				} else {
					$groupsList .= " OR ";
				}
			
				$groupsList .= "gcl". $groupId. ".link_to_id IS NOT NULL";
			}
		
			$rolesList = "FALSE";
			if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = \ze\module::prefix('zenario_organization_manager')) {
				foreach (\ze\sql::fetchValues("
					SELECT DISTINCT role_id
					FROM ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link
					WHERE user_id = ". (int) $userId
				) as $roleId) {
					$sql .= "
						LEFT JOIN ". DB_PREFIX. "group_link AS rcl". $roleId. "
						   ON rcl". $roleId. ".link_from = 'chain'
						  AND rcl". $roleId. ".link_from_id = tc.equiv_id
						  AND rcl". $roleId. ".link_from_char = tc.type
						  AND rcl". $roleId. ".link_to = 'role'
						  AND rcl". $roleId. ".link_to_id = ". $roleId;
			
					if ($rolesList == "FALSE") {
						$rolesList = "";
					} else {
						$rolesList .= " OR ";
					}
			
					$rolesList .= "rcl". $roleId. ".link_to_id IS NOT NULL";
				}
			}
		
			$sql .= "
			WHERE IF (tc.privacy = 'group_members',
				". $groupsList. ",
				IF (tc.privacy = 'with_role',
					". $rolesList. ",
					tc.privacy IN ('public', 'logged_in')
				)
			)";
		}
	
		if ($onlyShow == 'public') {
			$sql .= "
			  AND tc.privacy IN ('public', 'logged_out')";
	
		} elseif ($onlyShow == 'private') {
			$sql .= "
			  AND tc.privacy IN ('logged_in', 'group_members', 'with_role', 'in_smart_group', 'logged_in_not_in_smart_group')";
		}
	
		//Ensure that special pages are not included in the search results
		if (!$includeSpecialPages) {
			$sql .= "
				  AND c.tag_id NOT IN ('". implode("', '", array_map('ze\\escape::sql', \ze::$specialPages)). "')";
		}

	
		return $sql;
	}





	//Formerly "equivId()"
	public static function equivId($cID, $cType) {
		return \ze\row::get('content_items', 'equiv_id', ['id' => $cID, 'type' => $cType]);
	}

	const langEquivalentItemFromTwig = true;
	//Formerly "langEquivalentItem()"
	public static function langEquivalentItem(&$cID, &$cType, $langId = false, $checkVisible = false) {
	
		//Catch the case where a tag id is entered, not a cID and cType
		if (!is_numeric($cID)) {
			$tagId = $cID;
			\ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
		}
	
		if (!$cID) {
			return false;
	
		} elseif (!$cType) {
			if (!\ze\content::getCIDAndCTypeFromTagId($cID, $cType, $cID)) {
				return false;
			}
		}
	
		if ($langId === false) {
			$langId = \ze\content::visitorLangId();
	
		} elseif ($langId === true) {
			$langId = \ze::$defaultLang;
		}
	
		$sql = "
			SELECT id, equiv_id, language_id
			FROM ". DB_PREFIX. "content_items
			WHERE id = ". (int) $cID. "
			  AND type = '". \ze\escape::sql($cType). "'";
		$result = \ze\sql::select($sql);
	
		if ($row = \ze\sql::fetchAssoc($result)) {
			if ($langId != $row['language_id']) {
				$sql = "
					SELECT id
					FROM ". DB_PREFIX. "content_items
					WHERE equiv_id = ". (int) $row['equiv_id']. "
					  AND type = '". \ze\escape::sql($cType). "'
					  AND language_id = '". \ze\escape::sql($langId). "'";
			
				if ($checkVisible) {
					$adminMode = \ze::isAdmin();
				
					//If an admin is logged in, any drafts/hidden content items should effect which language they get directed to
					//If not, only published pages should effect the logic.
					if ($adminMode) {
						$sql .= "
						  AND status NOT IN ('trashed', 'deleted')";
					} else {
						$sql .= "
						  AND status IN ('published_with_draft', 'published')";
					}
				}
			
				$result = \ze\sql::select($sql);
			
				if ($row = \ze\sql::fetchAssoc($result)) {
					$cID = $row['id'];
					return true;
				}
			}
		
			if (!$checkVisible) {
				return true;
			}
		}
	
		return false;
	}

	//Formerly "equivalences()"
	public static function equivalences($cID, $cType, $includeCurrent = true, $equivId = false) {
		if ($equivId === false) {
			$equivId = \ze\content::equivId($cID, $cType);
		}
	
		$result = \ze\row::query(
			'content_items',
			['id', 'type', 'language_id', 'equiv_id', 'status'],
			['equiv_id' => $equivId, 'type' => $cType],
			'language_id');
	
		$equivs = [];
		while($equiv = \ze\sql::fetchAssoc($result)) {
			if ($includeCurrent || $equiv['id'] != $cID) {
				$equivs[$equiv['language_id']] = $equiv;
			}
		}
	
		return $equivs;
	}


	//Attempt to get a special page
	//We should never show unpublished pages to Visitors, and never return a Special Page in the wrong language if $languageMustMatch was set
	//Otherwise return a $cID and $cType as best we can
	//Formerly "langSpecialPage()"
	public static function langSpecialPage($pageType, &$cID, &$cType, $preferredLanguageId = false, $languageMustMatch = false, $skipPermsCheck = false) {
		//Assume that we'll want the special page in the language that the Visitor is currently viewing, if a language is not specified
		if ($preferredLanguageId === false) {
			$preferredLanguageId = \ze::$visLang ?? $_SESSION['user_lang'] ?? \ze::$defaultLang;
		}
	
		//Convert the requested language to the format used in the special pages array
		if ($preferredLanguageId == \ze::$defaultLang) {
			$preferredLanguageId = '';
		} else {
			$preferredLanguageId = '`'. $preferredLanguageId;
		}
	
		//Try to get the Special Page in the language that we've requested
		if (isset(\ze::$specialPages[$pageType. $preferredLanguageId])) {
			if (\ze\content::getCIDAndCTypeFromTagId($cID, $cType, \ze::$specialPages[$pageType. $preferredLanguageId])) {
				if ($skipPermsCheck || \ze\content::checkPerm($cID, $cType)) {
					return true;
				}
			}
		}
	
		//Otherwise try to fall back to the page for the default language
		if ($preferredLanguageId && !$languageMustMatch && isset(\ze::$specialPages[$pageType])) {
			if (\ze\content::getCIDAndCTypeFromTagId($cID, $cType, \ze::$specialPages[$pageType])) {
				if ($skipPermsCheck || \ze\content::checkPerm($cID, $cType)) {
					return true;
				}
			}
		}
	
		$cID = $cType = false;
		return false;
	}










	//Formerly "isSpecialPage()"
	public static function isSpecialPage($cID, $cType) {
		$specialPage = array_search($cType. '_'. $cID, \ze::$specialPages);
	
		if ($specialPage !== false) {
			$specialPage = explode('`', $specialPage, 2);
			return $specialPage[0];
		} else {
			return false;
		}
	}
	
	const langIdFromTwig = true;
	//Formerly "getContentLang()"
	public static function langId($cID, $cType = false) {
		return \ze\row::get('content_items', 'language_id', ['id' => $cID, 'type' => ($cType ?: 'html')]);
	}

	//Try to work out what content item is being accessed
	//n.b. \ze\link::toItem() and \ze\content::resolveFromRequest() are essentially opposites of each other...
	//Formerly "resolveContentItemFromRequest()"
	public static function resolveFromRequest(&$cID, &$cType, &$redirectNeeded, &$aliasInURL, $get, $request, $post) {
		$aliasInURL = '';
		$equivId = $cID = $cType = $reqLangId = $redirectNeeded = $languageSpecificDomain = $hierarchicalAliasInURL = false;
		$adminMode = \ze::isAdmin();
	
		//Check that we're on the domain we're expecting.
		//If not, flag that any links we generate should contain the full path and domain name.
		if (!empty($_SERVER['HTTP_HOST'])) {
			if ($adminMode) {
				if (\ze\link::adminDomain() != $_SERVER['HTTP_HOST']) {
					\ze::$wrongDomain =
					\ze::$mustUseFullPath = true;
				}
			} else {
				if (\ze\link::primaryDomain() != $_SERVER['HTTP_HOST']) {
					\ze::$wrongDomain =
					\ze::$mustUseFullPath = true;
				}
			}
		}
	
		//If there is a menu id in the request, try to get the Content Item from that
		if (!empty($request['mID']) && ($menu = \ze\menu::getContentItem($request['mID'], 2))) {
			$cID = $menu['equiv_id'];
			$cType = $menu['content_type'];
			\ze\content::langEquivalentItem($cID, $cType);
		
			//Visitors shouldn't see this type of link, so redirect them to the correct URL
			if (!$adminMode) {
				$redirectNeeded = 301;
			}
			return;
		}
	
		$multilingual = \ze\lang::count() > 1;
	
		//Check for a language-specific domain. If it is being used, get the language from that.
		if ($multilingual) {
			foreach (\ze::$langs as $langId => $lang) {
				if ($lang['domain']
				 && $lang['domain'] == $_SERVER['HTTP_HOST']) {
					$languageSpecificDomain = true;
					$reqLangId = $langId;
					break;
				}
			}
		}
	
		//Check for a requested page in the GET request
		if (!empty($get['cID'])) {
			$aliasInURL = $get['cID'];
		
			//If we see any slashes in the alias used in the URL, any links we generate will need to have the full path.
			if (strpos($aliasInURL, '/') !== false) {
				\ze::$mustUseFullPath = true;
			}
		}
		//Also check the POST request; use this instead if we see it
		if (!empty($post['cID'])) {
			$aliasInURL = $post['cID'];
		}
	
		//Attempt to work out what content item we're on, and break out of this logic as soon as it's resolved
		do {
	
			//Show one of the home pages if there's nothing in the request and no language specific domain
			if (!$reqLangId && !$aliasInURL) {
				$equivId = \ze::$homeEquivId;
				$cType = \ze::$homeCType;
			
			//At some point, I might start adding some special cases here, which you can trigger
			//by adding aliases that start with a ~.
			//However the only one that we currently use is the ability for Apache to call the CMS to show
			//a custom 404 page in place of the built-in Apache 404 page.
			} elseif ($aliasInURL == '~') {
				$cID = false;
				$cType = false;
				\ze::$mustUseFullPath = true;
				break;
	
			} else {
		
				//Check for slashes in the alias
				if (strpos($aliasInURL, '/') !== false) {
			
					$hierarchicalAliasInURL = trim($aliasInURL, '/');
					$slashes = explode('/', $hierarchicalAliasInURL);
			
					//For multilingual sites, check the first part of the URL for the requested language code.
					//(Except if a language-specific domain was used above, in which case skip this.)
					if ($multilingual
					 && !$reqLangId
					 && !empty($slashes[0])
					 && isset(\ze::$langs[$slashes[0]])) {
						$reqLangId = array_shift($slashes);
					}
			
					//Use the last bit of the URL to find the page.
					$aliasInURL = array_pop($slashes);
			
					//Anything in the middle are the other aliases in the menu tree; currently these are just visual
					//and are ignored.
		
				} else {
					//Check the request for a numeric cID, a string alias, and a language code separated by a comma.
					$aliasInURL = explode(',', $aliasInURL);
		
					if (!empty($aliasInURL[1])) {
						//Don't allow a language specific domain name *and* the language code in a comma
						if ($languageSpecificDomain) {
							$redirectNeeded = 301;
						}
				
						$reqLangId = $aliasInURL[1];
					}
			
					$aliasInURL = $aliasInURL[0];
				}
			
				//Catch the case where someone typed /admin onto the URL to try and login to admin mode
				if ($aliasInURL === 'admin') {
					$cID = false;
					$cType = false;
					$redirectNeeded = 'admin';
					break;
				
				//Catch the case where the language id is in the URL, and nothing else.
				//This should be a link to the home page in that language.
				} elseif ($aliasInURL && isset(\ze::$langs[$aliasInURL])) {
					$reqLangId = $aliasInURL;
					$aliasInURL = false;
			
					//N.b. this is not a valid URL is there is only one language on a site!
					if (count(\ze::$langs) < 2) {
						$redirectNeeded = 301;
					}
				}
		
				//Language codes with no alias means the home page for that language
				if ($reqLangId && !$aliasInURL) {
			
					\ze\content::langSpecialPage('zenario_home', $cID, $cType, $reqLangId, $languageMustMatch = true, $skipPermsCheck = true);
			
					//Slightly different logic depending on whether we are allowed slashes in the alias or not
						//If so, this is a valid URL and we don't need to change it
						//If not, it's not a valid URL, and we should rewrite it to show the alias.
					//Also, language specific domains should trigger the same logic.
					if (!$languageSpecificDomain && !\ze::setting('mod_rewrite_slashes')) {
						$redirectNeeded = 301;
					}
			
					break;
		
				//Link by numeric cID
				} elseif (is_numeric($aliasInURL)) {
					$cID = (int) $aliasInURL;
			
					if (!empty($request['cType'])) {
						$cType = $request['cType'];
					} else {
						$cType = 'html';
					}
			
					//Allow numeric cIDs with language codes, but redirect them to the correct URL
					if ($reqLangId) {
						\ze\content::langEquivalentItem($cID, $cType, $reqLangId);
						$redirectNeeded = 301;
					}
			
					//We know both the Content Item and language from the numeric id,
					//so we can stop straight away without looking up anything else.
					break;
		
				//Link by tag id
				} elseif (\ze\content::getCIDAndCTypeFromTagId($cID, $cType, $aliasInURL)) {
					//Allow tag ids with language codes, but redirect them to the correct URL
					if ($reqLangId && !$languageSpecificDomain) {
						\ze\content::langEquivalentItem($cID, $cType, $reqLangId);
						$redirectNeeded = 301;
					}
			
					//Again we can stop straight away as we know the specific Content Item
					break;
		
				//Link by an alias
				} else {
					//Attempt to look up a page with this alias
					$sql = "
						SELECT id, type, equiv_id, language_id
						FROM ". DB_PREFIX. "content_items
						WHERE alias = '". \ze\escape::sql($aliasInURL). "'";
			
					//If an admin is logged in, any drafts/hidden content items should effect which language they get directed to
					//If not, only published pages should effect the logic.
					if ($adminMode) {
						$sql .= "
						  AND status NOT IN ('trashed', 'deleted')";
					} else {
						$sql .= "
						  AND status IN ('published_with_draft', 'published')";
					}
			
					$sql .= "
						ORDER BY language_id";
			
					//If there was a language code in the URL, focus on the language that we're looking for
					if ($reqLangId) {
						$sql .= " = '". \ze\escape::sql($reqLangId). "' DESC, language_id";
					}
			
					//Get two rows, so we can tell if this alias was unique
					$sql .= "
						LIMIT 2";
			
					$result = \ze\sql::select($sql);
					if ($row = \ze\sql::fetchAssoc($result)) {
						$row2 = \ze\sql::fetchAssoc($result);
					}
			
					//If the alias was not found, then we can't resolve it to a Content Item
					if (!$row) {
						$cID = false;
						$cType = false;
						break;
			
					//If there was only one result for that alias, we can use this straight away
					//If there was a language specified and there was only one match for that language, we're also good to go
					} elseif ($row && (
						!$row2
					 || ($reqLangId && $reqLangId == $row['language_id'] && $reqLangId != $row2['language_id'])
					)) {
						$cID = $row['id'];
						$cType = $row['type'];
				
						//Redirect the case where we resolved a match, but the alias didn't actually match the language code
						if ($reqLangId && $reqLangId != $row['language_id']) {
							\ze\content::langEquivalentItem($cID, $cType, $reqLangId, true);
							$redirectNeeded = 301;
				
						//If this was a hierarchical URL, but hierarchical URLs are disabled,
						//we should redirect back to a page with a flat URL
						} elseif ($hierarchicalAliasInURL !== false && !\ze::setting('mod_rewrite_slashes')) {
							$redirectNeeded = 301;
				
						//If this was a hierarchical URL, check the URL was correct and redirect if not
						} elseif ($hierarchicalAliasInURL !== false) {
							$hierarchicalAlias = \ze\link::hierarchicalAlias($row['equiv_id'], $row['type'], $row['language_id'], $aliasInURL);
				
							if ($hierarchicalAliasInURL != $hierarchicalAlias
							 && $hierarchicalAliasInURL != $row['language_id']. '/'. $hierarchicalAlias) {
								$redirectNeeded = 301;
							}
						}
			
						break;
			
					} else {
						//Otherwise, just note down which translation chain was found, and resolve to the correct language below
						$cID = false;
						$equivId = $row['equiv_id'];
						$cType = $row['type'];
					}
				}
			}
	
	
			//If we reach this point, we've found a translation to show, but don't know which language to show it in.
			$acptLangId = $acptLangId2 = false;
			if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				//Get the Visitor's preferred languae from their browser.
					//Note: as of 6.1 we only look at the first choice.
				$acptLangId = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'], 2);
				$acptLangId = explode(';', $acptLangId[0], 2);
				$acptLangId = strtolower(trim($acptLangId[0]));
	
				//Also look for the first part of the language code (before the hyphen) as a fallback
				$acptLangId2 = explode('-', $acptLangId, 2);
				$acptLangId2 = $acptLangId2[0];
			}
	
			//Look at the languages that we have for the requested translation.
			$sql = "
				SELECT c.id, c.type, c.equiv_id, c.language_id, c.alias, l.detect, l.detect_lang_codes
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "languages AS l
				   ON c.language_id = l.id
				WHERE c.equiv_id = ". (int) $equivId. "
				  AND c.type = '". \ze\escape::sql($cType). "'";
			
				//If an admin is logged in, any drafts/hidden content items should effect which language they get directed to
				//If not, only published pages should effect the logic.
				if ($adminMode) {
					$sql .= "
					  AND c.status NOT IN ('trashed', 'deleted')";
				} else {
					$sql .= "
					  AND c.status IN ('published_with_draft', 'published')";
				}
			
				$sql .= "
				ORDER BY
					c.language_id = '". \ze\escape::sql(\ze::$defaultLang). "' DESC,
					c.language_id";
	
			$match = false;
			$result = \ze\sql::select($sql);
			while ($row = \ze\sql::fetchAssoc($result)) {
				//If this language should be auto-detected, get a list of language codes that it matches to
				if ($row['detect']) {
					$langCodes = array_flip(\ze\ray::explodeAndTrim($row['language_id']. ','. $row['detect_lang_codes']));
				}
		
				//If there is a match, use that and stop here
				if ($row['detect'] && $acptLangId && !empty($langCodes[$acptLangId])) {
					$match = $row;
					break;
		
				//If there is a match on the first part of the language code, remember this one as a fallback
				} elseif ($row['detect'] && $acptLangId2 && !empty($langCodes[$acptLangId2])) {
					$match = $row;
		
				//If nothing else matches, make sure we go to the default language
				//(or the first found language if there was no default) as a fallback.
				} elseif (!$match) {
					$match = $row;
				}
			}
	
			if ($match) {
				$cID = $match['id'];
		
				//If there was a requested alias, which was different than the resolved alias, we should do a redirect.
				if ($aliasInURL && $aliasInURL != $match['alias']) {
					$redirectNeeded = 301;
		
				//If there was a requested language, which was different than the resolved language, we should do a redirect.
				} elseif ($reqLangId && $reqLangId != $match['language_id']) {
					$redirectNeeded = 301;
		
				//For multilingual sites, if the language code was not in the URL and we had an ambiguous link, we should do a redirect.
				//But make it a 302 redirect, as we don't want to discourage Search Engines from listing the URLs of landing pages.
				} elseif (!$reqLangId && $multilingual) {
					$redirectNeeded = 302;
				}
	
			} else {
				$cID = false;
				$cType = false;
			}
		} while (false);
	
	
		//Check to see if the user requested the page shown using a different language
		if ($cID !== false
		 && isset($_GET['visLang'])
		 && \ze::$visLang === null) {
			$visLang = $_GET['visLang'];
		
			//Don't allow this if the language requested is not used on the site,
			//or if there's a real translation for the page in the language requested that's now visible.
			if (!isset(\ze::$langs[$visLang])
			 || \ze\content::langEquivalentItem($cID, $cType, $visLang, true)) {
				unset($_GET['visLang']);
				$redirectNeeded = 301;
		
			} else {
				\ze::$visLang = $visLang;
			}
		}
	}

	const checkPermFromTwig = true;
	//Check to see if a Content Item exists, and the current visitor/user/admin can see a Content Item
	//(Admins can see all Content Items that exist)
	//Formerly "checkPerm()"
	public static function checkPerm($cID, $cType = 'html', $requestVersion = false) {
		$content = false;
		return (bool) \ze\content::checkPermAndGetShowableContent($content, $cID, $cType, $requestVersion);
	}

	//Gets the correct version of a Content Item to show someone, or false if the do not have any access.
	//(Works exactly like \ze\content::checkPerm() above, except it will return a version number.)
	//Formerly "getShowableVersion()"
	public static function showableVersion($cID, $cType = 'html') {
		$content = false;
		return \ze\content::checkPermAndGetShowableContent($content, $cID, $cType, $requestVersion = false);
	}

	//Check to see if a Content Item exists, and the current visitor/user/admin can see a Content Item
	//Works like \ze\content::checkPerm() above, except that it will return a permissions error code
	//It also looks up some details on the Content Item
	//Formerly "getShowableContent()"
	public static function getShowableContent(&$content, &$version, $cID, $cType = 'html', $requestVersion = false, $checkRequestVars = false, $adminsSee400Errors = false) {

		if ($checkRequestVars) {
			//Look variables such as userId, locationId, etc., in the request
			if (!require \ze::editionInclude('checkRequestVars')) {
				if ($adminsSee400Errors || !\ze\priv::check()) {
					//Handle the case where the current visitor does not have the rights to see something requested in the core variables
					if (empty($_SESSION['extranetUserID'])) {
						//If the current visitor is not logged in, sent a 401 error
						return ZENARIO_401_NOT_LOGGED_IN;
					} else {
						//If there is a visitor not logged in, sent a 403 error
						return ZENARIO_403_NO_PERMISSION;
					}
				}
			}
		}
	
		$versionNumber = \ze\content::checkPermAndGetShowableContent($content, $cID, $cType, $requestVersion, $adminsSee400Errors);
	
		if ($versionNumber && is_numeric($versionNumber)) {
			$versionColumns = [
				'version',
				'title', 'description', 'keywords',
				'layout_id', 'css_class', 'feature_image_id',
				'release_date', 'published_datetime', 'created_datetime',
				'rss_slot_name', 'rss_nest'];
		
			$version = \ze\row::get('content_item_versions', $versionColumns, ['id' => $content['id'], 'type' => $content['type'], 'version' => $versionNumber]);
			$versionNumber = true;
		}
	
		return $versionNumber;
	}


	//Formerly "checkPermAndGetShowableContent()"
	public static function checkPermAndGetShowableContent(&$content, $cID, $cType, $requestVersion, $adminsSee400Errors = false) {
		// Returns the version of this content item which should normally be returned
		if ($cID
		 && $cType
		 && (ctype_alpha(\ze\escape::sql($cType))) //cType can only be letters a-z and A-Z.
		 && ($content = \ze\sql::fetchAssoc("
				SELECT
					equiv_id, id, type, language_id, alias,
					visitor_version, admin_version, status, lock_owner_id
				FROM ". DB_PREFIX. "content_items
				WHERE id = ". (int) $cID. "
				  AND type = '". \ze\escape::sql($cType). "'")
			)
		 && ($chain = \ze\sql::fetchAssoc("
				SELECT equiv_id, type, privacy, smart_group_id
				FROM ". DB_PREFIX. "translation_chains
				WHERE equiv_id = ". (int) $content['equiv_id']. "
				  AND type = '". \ze\escape::sql($cType). "'")
		)) {
			//If we are in admin mode, allow anything that exists to be shown
			if (\ze\priv::check()) {
			
				//If no specific version was requested, use the admin version
				if (!(int) $requestVersion) {
					$requestVersion = (int) $content['admin_version'];
				}
			
				//Check the requested version exists
				if (!$requestVersion
				 || !\ze\row::exists('content_item_versions', ['id' => $content['id'], 'type' => $content['type'], 'version' => $requestVersion])) {
					return false;
				}
			
				//If the $adminsSee400Errors option is set, still check the privacy settings even though an admin is logged in
				$status = true;
				if ($adminsSee400Errors) {
					$privacySettings = false;
				
					switch ($chain['privacy']) {
						case 'call_static_method':
							$privacySettings =
								\ze\row::get('translation_chain_privacy', true, [
									'equiv_id' => $content['equiv_id'],
									'content_type' => $cType]);
					}
			
					$status = \ze\content::checkItemPrivacy($chain, $privacySettings, $cID, $cType, $requestVersion);
				}
			
				return $status? $requestVersion: $status;
		
			//If we are in visitor mode, only show a published version
			} elseif (\ze\content::isPublished($content['status']) && ($cVersion = (int) $content['visitor_version'])) {
			
				$privacySettings = false;
			
				switch ($chain['privacy']) {
					case 'call_static_method':
						$privacySettings =
							\ze\row::get('translation_chain_privacy', true, [
								'equiv_id' => $content['equiv_id'],
								'content_type' => $cType]);
				
					case 'send_signal':
						\ze::$canCache = false;
				}
			
				$status = \ze\content::checkItemPrivacy($chain, $privacySettings, $cID, $cType, $cVersion);
			
				return $status? $cVersion: $status;
			}
		}
	
		return false;
	}

	//Formerly "checkItemPrivacy()"
	public static function checkItemPrivacy($privacy, $privacySettings, $cID, $cType, $cVersion) {
	
		//Check if a user is logged in.
		$userId = false;
		if (!empty($_SESSION['extranetUserID'])
		 && ($userId = (int) $_SESSION['extranetUserID'])) {
	
		//If not, any permission that needs an account should fail with a 401 error.
		} else {
			switch ($privacy['privacy']) {
				case 'logged_in':
				case 'group_members':
				case 'with_role':
				case 'in_smart_group':
				case 'logged_in_not_in_smart_group':
					return ZENARIO_401_NOT_LOGGED_IN;
			}
		}

		switch ($privacy['privacy']) {
			case 'public':
			case 'logged_in': //Already checked above
				return true;
		
			case 'logged_out':
				return $userId? ZENARIO_403_NO_PERMISSION : true;
	
			case 'group_members':
			case 'with_role':
			
				if ($privacy['privacy'] == 'group_members') {
					//Try to get this user's groups
					$linkTo = 'group';
					$linkToIds = \ze\user::groups($userId);
			
				} elseif ($privacy['privacy'] == 'with_role' && ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = \ze\module::prefix('zenario_organization_manager'))) {
					//Try to get this user's roles
					$linkTo = 'role';
					$linkToIds =
						\ze\ray::valuesToKeys(
							\ze\sql::fetchValues("
								SELECT DISTINCT role_id
								FROM ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link
								WHERE user_id = ". (int) $userId
							)
						);
			
				} else {
					return false;
				}
		
				//Look up all of the groups for this content item or slide.
				//If the user has one of the groups, allow access.
				if (!empty($linkToIds)) {
				
					$sql = "
						SELECT link_to_id
						FROM `". DB_PREFIX. "group_link`
						WHERE link_to = '". \ze\escape::sql($linkTo). "'";
				
					if (!empty($privacy['equiv_id']) && !empty($privacy['type'])) {
						$sql .= "
							  AND link_from = 'chain'
							  AND link_from_id = ". (int) $privacy['equiv_id']. "
							  AND link_from_char = '". \ze\escape::sql($cType). "'";
				
					} elseif (!empty($privacy['slide_id'])) {
						$sql .= "
							  AND link_from = 'slide'
							  AND link_from_id = ". (int) $privacy['slide_id'];
				
					} else {
						return false;
					}
				
					foreach (\ze\sql::fetchValues($sql) as $groupId) {
						if (!empty($linkToIds[$groupId])) {
							return true;
						}
					}		
				}
		
				//If they don't have access return a 403 error.
				return ZENARIO_403_NO_PERMISSION;
		
			case 'in_smart_group':
				return \ze\smartGroup::isUserIn($privacy['smart_group_id'], $userId)? true : ZENARIO_403_NO_PERMISSION;
		
			case 'logged_in_not_in_smart_group':
				return !\ze\smartGroup::isUserIn($privacy['smart_group_id'], $userId)? true : ZENARIO_403_NO_PERMISSION;
		
			//Call a module's static method, or send the eventCheckContentItemPermission() signal,
			//to decide whether the current user should see this content item
			case 'call_static_method':
			case 'send_signal':
				
				$status = ZENARIO_404_NOT_FOUND;
			
				if ($privacy['privacy'] == 'call_static_method') {
					if ($privacySettings) {
						if ((\ze\module::inc($privacySettings['module_class_name']))
						 && (method_exists($privacySettings['module_class_name'], $privacySettings['method_name']))) {
					
							$status = call_user_func(
								[$privacySettings['module_class_name'], $privacySettings['method_name']],
								$privacySettings['param_1'], $privacySettings['param_2']);
						}
					}
			
				} else {
					if ($results = \ze\module::sendSignal('eventCheckContentItemPermission', ['userId' => $userId, 'cID' => $cID, 'cType' => $cType, 'cVersion' => $cVersion])) {
						foreach ($results as $result) {
							if ($result !== false) {
								$status = $result;
							}
						}
					}
				}
			
				//Catch the case where the PHP script above sends a 401 error but a user is logged in,
				//and convert it to a 403 error
				if ($status === ZENARIO_401_NOT_LOGGED_IN && $userId) {
					return ZENARIO_403_NO_PERMISSION;
				}
			
				return $status? true: $status;
		}
	
		return false;
	}

	//Formerly "setShowableContent()"
	public static function setShowableContent(&$content, &$version) {
		\ze::$equivId = $content['equiv_id'];
		\ze::$cID = $content['id'];
		\ze::$cType = $content['type'];
		\ze::$alias = $content['alias'];
		\ze::$status = $content['status'];
		\ze::$langId = $content['language_id'];
	
		//Set the visitor's language differently, depending on whether we're showing this
		//page for another language
		if (\ze::$visLang !== null
		 && \ze::$visLang != \ze::$langId) {
			$_SESSION['user_lang'] = \ze::$visLang;
		} else {
			$_SESSION['user_lang'] = \ze::$visLang = \ze::$langId;
		}
	
		\ze::$cVersion = $version['version'];
		\ze::$adminVersion = $content['admin_version'];
		\ze::$visitorVersion = $content['visitor_version'];
	
		\ze::$pageTitle = $version['title'];
		\ze::$pageDesc = $version['description'];
		\ze::$pageImage = $version['feature_image_id'];
		\ze::$pageKeywords = $version['keywords'];
	
		\ze::$itemCSS = $version['css_class'];
		\ze::$date = ($version['release_date'] ?: ($version['published_datetime'] ?: $version['created_datetime']));
		\ze::$rss = $version['rss_nest']. '_'. $version['rss_slot_name'];
	
		\ze::$isDraft =
			$version['version'] == $content['admin_version'] && (
				$content['status'] == 'first_draft'
			 || $content['status'] == 'published_with_draft'
			 || $content['status'] == 'hidden_with_draft'
			 || $content['status'] == 'trashed_with_draft');
	
		\ze::$locked = $content['lock_owner_id'] && !empty($_SESSION['admin_userid']) && $content['lock_owner_id'] != $_SESSION['admin_userid'];

		//Given what we know, find a Layout and a Template Family as best we can.
		//Give priority to matching Layout ids, matching family names, active Layouts,
		//and then html type Layouts in that order
		$sql = "
			SELECT
				family_name, layout_id, file_base_name, skin_id, css_class,
				cols, min_width, max_width, fluid, responsive
			FROM ". DB_PREFIX. "layouts
			ORDER BY
				content_type = '". \ze\escape::sql(\ze::$cType). "' DESC";
	
		if (($layoutId = $version['layout_id']) || ($layoutId = \ze\row::get('content_types', 'default_layout_id', ['content_type_id' => \ze::$cType]))) {
			$sql .= ",
				layout_id = ". (int) $layoutId. " DESC";
		}
	
		$sql .= ",
				layout_id";
	
		$result = \ze\sql::select($sql);
		$template = \ze\sql::fetchAssoc($result);
	
		\ze::$layoutId = $template['layout_id'];
		\ze::$cols = (int) $template['cols'];
		\ze::$minWidth = (int) $template['min_width'];
		\ze::$maxWidth = (int) $template['max_width'];
		\ze::$fluid = (bool) $template['fluid'];
		\ze::$responsive = (bool) $template['responsive'];
		\ze::$templateCSS = $template['css_class'];
		\ze::$templateFamily = $template['family_name'];
		\ze::$templateFileBaseName = $template['file_base_name'];
		\ze::$templateFilename = $template['file_base_name']. '.tpl.php';
		\ze::$templatePath = \ze\content::templatePath(\ze::$templateFamily);
	
		//This constant was used in some old Template Files.
		define('TEMPLATE_PATH', \ze::$templateFamily);
	
		if ((\ze::$skinId = \ze\content::layoutSkinId($template, true))
		 && ($skin = \ze\content::skinDetails(\ze::$skinId))) {
			\ze::$skinName = $skin['name'];
			\ze::$skinCSS = $skin['css_class'];
		}
	}
	
	
	//Formerly "templateSkinId()"
	public static function layoutSkinId($template, $fallback = false) {
	
		if (!is_array($template)) {
			$template = \ze\row::get('layouts', ['family_name', 'skin_id'], $template);
		}
	
		if ($template) {
			if ($template['skin_id']) {
				return $template['skin_id'];
		
			} elseif ($skinId = \ze\row::get('template_families', 'skin_id', ['family_name' => $template['family_name']])) {
				return $skinId;
		
			} elseif ($fallback) {
				return \ze\row::get('skins', 'id', ['family_name' => $template['family_name'], 'missing' => 0]);
			}
		}
		return false;
	}
	

	//Formerly "contentItemAlias()"
	public static function alias($cID, $cType) {
		return \ze\row::get('content_items', 'alias', ['id' => $cID, 'type' => $cType]);
	}

	//Formerly "contentItemTemplateId()"
	public static function layoutId($cID, $cType, $cVersion = false) {
	
		if ($cVersion === false) {
			$cVersion = \ze\content::latestVersion($cID, $cType);
		}
	
		return \ze\row::get('content_item_versions', 'layout_id', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
	}


	//Formerly "getLatestContentID()"
	public static function latestId($cType) {
		return (int) \ze\row::max('content_items', 'id', ['type' => $cType]);
	}

	//Formerly "getPublishedVersion()"
	public static function publishedVersion($cID, $cType) {
		return \ze\row::get('content_items', 'visitor_version', ['id' => $cID, 'type' => $cType]);
	}

	//Formerly "getLatestVersion()"
	public static function latestVersion($cID, $cType) {
		return \ze\row::get('content_items', 'admin_version', ['id' => $cID, 'type' => $cType]);
	}

	//Formerly "getAppropriateVersion()"
	public static function appropriateVersion($cID, $cType) {
		return \ze\row::get('content_items', \ze\priv::check()? 'admin_version' : 'visitor_version', ['id' => $cID, 'type' => $cType]);
	}


	//Formerly "getItemTitle()"
	public static function title($cID, $cType, $cVersion = false) {
	
		if (!$cVersion) {
			$cVersion = \ze\content::latestVersion($cID, $cType);
		}
	
		if ($cID == \ze::$cID && $cType == \ze::$cType && $cVersion == \ze::$cVersion) {
			return \ze::$pageTitle;
		} else {
			return \ze\row::get('content_item_versions', 'title', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
		}
	}

	//Formerly "getItemDescription()"
	public static function description($cID, $cType, $cVersion) {
		return \ze\row::get('content_item_versions', 'description', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
	}

	//Formerly "formatTagFromTagId()"
	public static function formatTagFromTagId($tagId) {
		$cID = $cType = false;
		if (\ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
			return \ze\content::formatTag($cID, $cType);
		} else {
			return false;
		}
	}

	//Formerly "formatTag()"
	public static function formatTag($cID, $cType, $alias = -1, $langId = false, $neverAddLanguage = false) {
		$content = false;
		$friendlyURL = '';
	
		if ($alias === -1) {
			$content = \ze\row::get('content_items', ['alias', 'language_id'], ['id' => $cID, 'type' => $cType]);
			$alias = $content['alias'];
		}
	
		if ($alias) {
			$friendlyURL = '/'. $alias;
		}
	
		if (!$neverAddLanguage
		 && \ze\lang::count() > 1) {
			if (!$langId) {
				if (!$content) {
					$content = \ze\row::get('content_items', ['alias', 'language_id'], ['id' => $cID, 'type' => $cType]);
				}
				$langId = $content['language_id'];
			}
		
			$friendlyURL .= ','. $langId;
		}
	
		return $cType. '_'. $cID. $friendlyURL;
	}


	//Formerly "cutTitle()"
	public static function cutTitle($title, $max_title_length = 20, $cutText = '...') {
		if (strlen($title) > $max_title_length) {
			return mb_substr($title, 0, floor($max_title_length/2)). $cutText. mb_substr($title, -floor($max_title_length/2));
		} else {
			return $title;
		}
	}







	//Formerly "getContentStatus()"
	public static function status($cID, $cType) {
		return \ze\row::get('content_items', 'status', ['id' => $cID, 'type' => $cType]);
	}



	//Formerly "getSearchtermParts()"
	public static function searchtermParts($searchString) {
		//Remove everything from the search terms except for word characters, single quotes (which can be part of words) and double quotes
		//Attempt to validate allowing UTF-8 characters through
		if (!function_exists('mb_ereg_replace')
		 || !$searchString = mb_ereg_replace('[^\w\s_\'"]', ' ', $searchString)) {
			//Fall back to traditional pattern matching if that fails
			$searchString = preg_replace('/[^\w\s_\'"]/', ' ', $searchString);
		}
	
		//Limit the search results to 100 chars
		$searchString = substr($searchString, 0, 100);
	
		//Break the search string up into tokens.
		//Normally we break by spaces, but you can use a pattern in double quotes to override this
		preg_match_all('/"([^"]+)"|(\S+)/', trim($searchString), $searchStrings, PREG_SET_ORDER);
	
		$quotesUsed = false;
		$searchWordsAndPhrases = [];
	
		foreach($searchStrings as $i => $string) {
			//Have a limit of 10 words
			if ($i >= 10) {
				break;
			}
		
			//Remove any double-quotes that might still be in the text
			$string = str_replace('"', '', $string);
		
			if (isset($string[2])) {
				$searchWordsAndPhrases[$string[2]] = 'word';
			} else {
				$searchWordsAndPhrases[$string[1]] = 'phrase';
				$quotesUsed = true;
			}
		}
	
		//Just in case the user doesn't know about the "using quotes to group words together" feature,
		//add the whole phrase in as a search term.
		//Also do this as a fallback in case nothing was matched
		if (empty($searchWordsAndPhrases)
		 || (!$quotesUsed && count($searchStrings) > 1)) {
			$searchWordsAndPhrases[$searchString] = 'whole phrase';
		}
	
		return $searchWordsAndPhrases;
	}





	//Whether to show untranslated content items
	//Formerly "showUntranslatedContentItems()"
	public static function showUntranslatedContentItems($langId = false) {
	
		if ($langId === false) {
			$langId = \ze::$visLang;
		}
	
		return \ze::$langs[$langId]['show_untranslated_content_items'] ?? false;
	}
	
	
	
	
	
	
	
	
	
	



	//	Layouts  //

	//Formerly "getTemplateDetails()"
	public static function layoutDetails($layoutId) {
		$sql = "
			SELECT
				layout_id,
				family_name,
				file_base_name,
				CONCAT(file_base_name, '.tpl.php') AS filename,
				CONCAT('L', IF (layout_id < 10, LPAD(CAST(layout_id AS CHAR), 2, '0'), CAST(layout_id AS CHAR)), ' ', name) AS id_and_name,
				name,
				content_type,
				status,
				skin_id,
				css_class,
				bg_image_id,
				bg_color,
				bg_position,
				bg_repeat
			FROM ". DB_PREFIX. "layouts
			WHERE layout_id = ". (int) $layoutId;
		$result = \ze\sql::select($sql);
		return \ze\sql::fetchAssoc($result);
	}

	//Formerly "zenarioTemplatePath()"
	public static function templatePath($templateFamily = false, $fileBaseName = false, $css = false) {
		return 'zenario_custom/templates/'. ($templateFamily? $templateFamily. '/'. ($fileBaseName? $fileBaseName. ($css? '.css' : '.tpl.php') : '') : '');
	}
	
	
	
	//	Skins  //

	//Formerly "getSkinFromId()"
	public static function skinDetails($skinId) {
		return \ze\row::get('skins', ['id', 'family_name', 'name', 'display_name', 'extension_of_skin', 'import', 'css_class', 'missing'], ['id' => $skinId]);
	}

	//Formerly "getSkinFromName()"
	public static function skinName($familyName, $skinName) {
		return \ze\row::get('skins', ['id', 'family_name', 'name', 'display_name', 'extension_of_skin', 'import', 'css_class', 'missing'], ['family_name' => $familyName, 'name' => $skinName]);
	}

	//Formerly "getSkinPath()"
	public static function skinPath($templateFamily = false, $skinName = false) {
		return \ze\content::templatePath(($templateFamily ?: \ze::$templateFamily)). 'skins/'. ($skinName ?: \ze::$skinName). '/';
	}

	//Formerly "getSkinPathURL()"
	public static function skinURL($templateFamily = false, $skinName = false) {
		return \ze\content::templatePath(($templateFamily ?: \ze::$templateFamily)). 'skins/'. rawurlencode(($skinName ?: \ze::$skinName)). '/';
	}
	

	//Find the lowest common denominator of two numbers
	//Formerly "rationalNumber()"
	public static function rationalNumber(&$a, &$b) {
	  for ($i = min($a, $b); $i > 1; --$i) {
		  if (($a % $i == 0)
		   && ($b % $i == 0)) {
			  $a = (int) ($a / $i);
			  $b = (int) ($b / $i);
		  }
	  }
	}

	//Give a grid's cell a class-name based on how many columns it takes up, and the ratio out of the total width that it takes up
	//Formerly "rationalNumberGridClass()"
	public static function rationalNumberGridClass($a, $b) {
		$w = $a;
		\ze\content::rationalNumber($a, $b);
		return 'span span'. $w. ' span'. $a. '_'. $b;
	}
	



}