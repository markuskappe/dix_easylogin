<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Markus Kappe <markus.kappe@dix.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class tx_dixeasylogin_div {
	static function validateUrl($url) {
		if(function_exists('filter_var')) {
			return filter_var($url, FILTER_VALIDATE_URL);
		} else { 
			return eregi("^((https?)://)?(((www\.)?[^ ]+\.[com|org|net|edu|gov|us]))([^ ]+)?$", $url); 
		}
	}

	/**
	 * Tries to log in user into TYPO3 front-end by checking if the ID provided by the external 
	 * auth system matches a record in fe_users in the field tx_dixeasylogin_openid
	 * If configured so, it will create a user or connect a logged-in user with the given identifier
	 * 	 
	 * @param   string $identifier    The identifier as provided by Facebook or other systems. 
	 * @return  string   Message to be displayed to the user (success / error)
	 */
	static function loginFromIdentifier($identifier, $userinfo) {
		$user = self::fetchUserByIdentifier($identifier);
		$fe_user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
		
		if ($fe_user['uid']) { // user already logged in -> try to update the identifier
			if ($GLOBALS['piObj']->conf['allowUpdate']) {
				self::linkIdentifier2User($identifier, (int)$fe_user['uid']);
				$GLOBALS["TSFE"]->fe_user->setKey("ses", "easylogin_loginType","");
				return $GLOBALS['piObj']->pi_getLL('connect_success'); 
			}
			return 'how come you see this message?'; // should never be reached
		}

		// from this point on we are sure that the user is not logged in yet
		if (!$user['uid'] && $GLOBALS['piObj']->conf['allowCreate']) {
			if (self::checkMailDomain($userinfo['email'], $GLOBALS['piObj']->conf['trustedDomains'])) {
				$user = self::createUser($identifier, $userinfo);
			}
		}

		if ($user['uid']) {
			self::login($user);
			self::redirectToSelf();
		} else {
			$GLOBALS["TSFE"]->fe_user->setKey("ses", "easylogin_loginType","");
			return sprintf($GLOBALS['piObj']->pi_getLL('nouser'), $identifier); // User not found. Please contact the admin of the website to request access to this site. Tell the admin this identifier: %s
		}
	}
	
	/**
	 * Checks if the E-Mail-Domain (the part after the @ sign) is within the trusted domains. * is a wildcard 
	 * 	 
	 * @param   string $email    The E-Mail-Address to be checked, e.g. me@example.com 
	 * @param   string $domains  Valid domains that allow creation of user accounts. e.g. "gmail.com, yahoo.de", "*", "*.mycompany.com" 
	 * @return  bool   true if allowed, false otherwise
	 */
	static function checkMailDomain($email, $domains) {
		if ($domains == '*') { return true; }
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) { return false; }
		$mailDomain = substr($email, strpos($email, '@')+1);
		$domainArray = t3lib_div::trimExplode(',', $domains);
		foreach ($domainArray as $domain) {
			$starpos = strpos($domain, '*');
			if ($starpos !== false) {
				$part1 = substr($domain, $starpos+1);
				$part2 = substr($mailDomain, -strlen($part1));
				if ($part1 === $part2) { return true; }
			} else {
				if ($domain === $mailDomain) { return true; }
			}
		}
		return false;
	}

	static function login($user) {
		$GLOBALS['TSFE']->fe_user->checkPid=0; //do not use a particular pid
		$GLOBALS['TSFE']->fe_user->createUserSession($user);
		$GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
	}

	static function redirectToSelf() {
		$url = self::getSelfUrl(array('logintype' => 'login'));
		t3lib_utility_Http::redirect($url);
	}
	
	static public function getVerifyUrl() {
		return t3lib_div::locationHeaderUrl(
			self::getSelfUrl(array('tx_dixeasylogin_pi1' => array('action'=>'verify')))
		);
	}
	
	static public function getSelfUrl(array $params=array()) {
		$preservedVars = self::getPreservedVars();
		$vars = array_merge_recursive($preservedVars, $params); // params overrules preservedVars
		$url = $GLOBALS['TSFE']->cObj->getTypoLink_URL($GLOBALS['TSFE']->id, $vars);
		return $url;
	}
	
	// recursive
	static protected function array_intersect_key_recursive(array $arr1, array $arr2) {
		$result = array();
		foreach ($arr1 as $k1=>$v1) {
			if (!isset($arr2[$k1])) { continue; }
			if (is_array($v1) && is_array($arr2[$k1])) {
				if ($merged = self::array_intersect_key_recursive($v1, $arr2[$k1])) {
					$result[$k1] = $merged;
				}
			} elseif (!is_array($arr2[$k1])) {
				$result[$k1] = $v1;
			}
		}
		return $result;
	}
	

	static protected function getPreservedVars() {
		$getVars = t3lib_div::_GET();
		$conf = $GLOBALS['piObj']->conf['preserveGETvars'];
		if ('all' == $conf) { return $getVars; }
		$conf = strtr($conf, '&?= ', ''); // basic validation
		$conf = str_replace(',', '=1&', $conf).'=1'; // transform to url style
		parse_str($conf, $params);
		$keep = self::array_intersect_key_recursive((array)$getVars, (array)$params);
		return $keep;
	}

	/**
	* @param string $identifier Identifier provided by the authorization mechanism e.g facebook-ID 
	* @return array corresponding fe_user record
	*/
	static function fetchUserByIdentifier($identifier) {
		$table = 'tx_dixeasylogin_identifiers';
		$where = sprintf('identifier = %s %s', $GLOBALS['TYPO3_DB']->fullQuoteStr($identifier, $table), $GLOBALS['piObj']->cObj->enableFields($table));
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $GLOBALS['piObj']->pi_getRecord('fe_users', $row['user']);
	}

	static function createUser($identifier, $userinfo) {
		# debugster ($userinfo); debugster ($_GET); exit();
		// possible keys in $userinfo: nickname,email,fullname,dob,gender,postcode,country,language,timezone,prefix,firstname,lastname,suffix
		// @see http://openid.net/specs/openid-simple-registration-extension-1_0.html#response_format
		$table = 'fe_users';
		$values = array(
			'email' => (string)$userinfo['email'],
			'username' => (string)($userinfo['nickname'] ? $userinfo['nickname'] : $userinfo['email']), // TODO: check if username is unique
			#'tx_dixeasylogin_openid' => $identifier,
			'pid' => $GLOBALS['piObj']->conf['user_pid'],
			'crdate' => time(),
			'tstamp' => time(),
			'password' => t3lib_div::getRandomHexString(32),
			'usergroup' => $GLOBALS['piObj']->conf['usergroup'],
			'name' => (string)($userinfo['fullname'] ? $userinfo['fullname'] : trim($userinfo['firstname'].' '.$userinfo['lastname'].' '.$userinfo['suffix'])),
			'title' => (string)$userinfo['prefix'],
			'first_name' => (string)$userinfo['firstname'],
			'last_name' => (string)$userinfo['lastname'],
			'zip' => (string)$userinfo['postcode'],
			'country' => (string)$userinfo['country'], // incoming format: http://www.iso.org/iso/country_codes/iso_3166_code_lists/country_names_and_code_elements.htm (e.g. DE or AT)
			'tx_extbase_type' => $GLOBALS['piObj']->conf['extbaseType'],
			# no field like "date of birth" in fe_users. incoming format: YYYY-MM-DD
			# no field like "gender" in fe_users. incoming format: "M" or "F"
			# no field like "language" in fe_users. incoming format: http://www.loc.gov/standards/iso639-2/php/code_list.php (e.g. de or de-DE)
			# no field like "timezone" in fe_users. incoming format: http://www.twinsun.com/tz/tz-link.htm (e.g.  "Europe/Paris" or "America/Los_Angeles")
			# casting values to string because of http://forge.typo3.org/issues/55989
		);
		$values['name'] = (string)(trim($values['name']) ? $values['name'] : $userinfo['nickname']);
		$values = self::normalizeUser($values);
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $values);
		$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();
		self::linkIdentifier2User($identifier, $uid);
		$user = self::fetchUserByIdentifier($identifier);
		return $user;
	}
	
	static function linkIdentifier2User($identifier, $uid) {
		$table = 'tx_dixeasylogin_identifiers';
		$where = sprintf('identifier = %s %s', $GLOBALS['TYPO3_DB']->fullQuoteStr($identifier, $table), $GLOBALS['piObj']->cObj->enableFields($table));
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$type = $GLOBALS["TSFE"]->fe_user->getKey("ses", "easylogin_loginType");
		$values = array(
			'user' => $uid,
			'tstamp' => time(),
			'conn_type' => $type,
			'conn_name' => $GLOBALS['piObj']->conf['provider.'][$type.'.']['name'],
		);
		if ($row['uid']) {
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid = '.$row['uid'], $values);
		} else {
			$values['identifier'] = $identifier;
			$values['crdate'] = time();
			$values['pid'] = $GLOBALS['piObj']->conf['user_pid'];
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $values);
		}
	}
	
	// lower, nospace, uniqueInPid
	static function normalizeUser($user) {
		$name = str_replace(' ', '', strtolower($user['username']));
		$tcemain = t3lib_div::makeInstance("t3lib_TCEmain");
		$user['username'] = $tcemain->getUnique('fe_users', 'username', $name, 0, $user['pid']);
		return $user;
	}

	static function makeCURLRequest($url, $method="GET", $params = "") {
		if (is_array($params)) {
			$params = http_build_query($params,'','&');
		}
		$curl = curl_init($url . ($method == "GET" && $params != "" ? "?" . $params : ""));
		
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
		curl_setopt($curl, CURLOPT_POST, ($method == "POST"));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		if ($method == "POST") {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		}
		
		$response = curl_exec($curl);
		return $response;
	}
	
	static function render_ttContent($uid) {
		$conf = array(
			'tables' => 'tt_content',
			'source' => $uid
		);
		return $GLOBALS['TSFE']->cObj->RECORDS($conf);
	}

	// @credits go out to http://modi.de/2010/02/12/fluid-without-extbase/
	static function renderFluidTemplate($filename, $values) {
		$renderer = t3lib_div::makeInstance('Tx_Fluid_View_StandaloneView');
		$path = $GLOBALS['piObj']->conf['template_path'];
		if (substr($path, -1) != DIRECTORY_SEPARATOR) { $path .= DIRECTORY_SEPARATOR; }

		$controllerContext = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_ControllerContext');
		$controllerContext->setRequest(t3lib_div::makeInstance('Tx_Extbase_MVC_Request'));
		$renderer->setControllerContext($controllerContext);
		$renderer->setTemplatePathAndFilename(t3lib_div::getFileAbsFileName($path . $filename));
		$renderer->assign('values', $values);

		return $renderer->render();
	}
	
	static function getFileRelFileName($filename) {
		if (substr($filename, 0, 4) == 'EXT:') { // extension
			list($extKey, $local) = explode('/', substr($filename, 4), 2);
			$filename = '';
			if (strcmp($extKey, '') && t3lib_extMgm::isLoaded($extKey) && strcmp($local, '')) {
				$filename = t3lib_extMgm::siteRelPath($extKey) . $local;
			}
		}
		return $filename;
	}
	
	static function getAssociatedProvider($uid) {
		$result = array();
		$table = 'tx_dixeasylogin_identifiers';
		$where = sprintf('user = %d %s', $uid, $GLOBALS['piObj']->cObj->enableFields($table));
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$result[$row['conn_type']] = $row;
		}
		return $result;
	}

}

class Tx_Dix_ViewHelpers_DispViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {
	/**
	 * @param $obj  object Object
	 * @param $prop	string Property
	 */	 	
	public function render($obj,$prop) {
		if(is_object($obj)) {
			return $obj->$prop;
		} elseif(is_array($obj)) {
			if(array_key_exists($prop, $obj)) {
				return $obj[$prop];
			}
		}
		return NULL;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_div.php']);
}

?>