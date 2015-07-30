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

// https://developer.linkedin.com/documents/code-samples

class tx_dixeasylogin_oauth2 {
	function init($provider, $piVars) {
		$this->provider = $provider;
		$this->piVars = $piVars;
		$GLOBALS['TSFE']->fe_user->fetchSessionData();
	}

	function main() {
		if ($this->piVars['process']) {
			$error = $this->redirToProvider();
		} elseif ($this->piVars['action']=="verify") {
			$error = $this->verifyLogin();
		}
		return $error;
	}

	function redirToProvider() { // authorize
		$state = uniqid('', true); // unique long string
		$GLOBALS["TSFE"]->fe_user->setKey("ses", "easylogin_oauth2_state", $state);
		$GLOBALS["TSFE"]->fe_user->storeSessionData();
		$verifyUrl = tx_dixeasylogin_div::getVerifyUrl();
		
		if (strpos($verifyUrl, '?')) {
			throw new Exception(sprintf($GLOBALS['piObj']->pi_getLL('qmark_in_url'), $verifyUrl));
		}

		$params = array(
			'response_type' => 'code',
			'client_id' => $this->provider['consumerKey'],
			'scope' => $this->provider['scope'],
			'state' => $state,
			'redirect_uri' => $verifyUrl,
		);
  	$auth_url = $this->provider['authorizeUrl'] . '?' . http_build_query($params,'','&');
	  header("Location: $auth_url");
	}

	function verifyLogin() {
		$state = $GLOBALS["TSFE"]->fe_user->getKey("ses", "easylogin_oauth2_state");
		if ($state != t3lib_div::_GET('state')) {
			throw new Exception('State parameter mismatch: either session timed out or XSRF attack');
    }

		$error = '';
		$token = $this->getToken(t3lib_div::_GET('code'), $error);
		if ($error) { return $error; }
		$userinfo = $this->getUserInfo($token, $error);
		if ($error) { return $error; }
		return tx_dixeasylogin_div::loginFromIdentifier($userinfo['id'], $userinfo);
	}

	function getToken($code, &$error) {
		$response = tx_dixeasylogin_div::makeCURLRequest($this->provider['accessTokenUrl'], 'POST', array(
			'grant_type' => 'authorization_code',
			'client_id' => $this->provider['consumerKey'],
			'redirect_uri' => tx_dixeasylogin_div::getVerifyUrl(), // must not contain a question mark "?"
			'client_secret' => $this->provider['consumerSecret'],
			'code' => $code,
		));
		if (!$response) {
			$error = sprintf($GLOBALS['piObj']->pi_getLL('error_validate_nocode'), $code); // Error while validating code-parameter '%s' (no answer)
			return false;
		}
		$decoded = json_decode($response, true);
		if ($decoded['error']) {
			$error = sprintf($GLOBALS['piObj']->pi_getLL('error_oauth2_validate'), print_r($decoded, TRUE)); // Error while validating code-parameter (%s)
			return false;
		}
		if ($decoded['access_token']) { return $decoded['access_token']; }
		// else try url parsing
		$result = array();
		parse_str($response, $result);
		if (!$result['access_token']) {
			$error = $GLOBALS['piObj']->pi_getLL('error_token'); // Error: could not retrieve access_token
		}
		return $result['access_token'];
	}
	
	/**
	 * recursive function that reduces multi-level-arrays to one single level
	 * e.g. 
	 * $input = array('name' => array('first' => 'Markus', 'last' => 'Kappe'));
	 * transforms into
	 * $result = array('name.first' => 'Markus', 'name.last' => 'Kappe');
	 */
	function flattenArray($input, $prefix='') {
		if (!is_array($input)) { return $input; }
		$result = array();
		foreach ($input as $key=>$value) {
			if (is_array($value)) {
				$result = $result + $this->flattenArray($value, $prefix.$key.'.');
			} else {
				$result[$prefix.$key] = $value;
			}
		}
		return $result;
	}

	function getUserInfo($token, &$error) {
		$userinfo = array();
		$response = tx_dixeasylogin_div::makeCURLRequest($this->provider['requestProfileUrl'], 'GET', array('access_token' => $token, 'oauth2_access_token' => $token)); // linkedin uses oauth2_access_token; facebook parameter is named access_token
		if (strtolower(trim($this->provider['profileEncoding'])) == 'json') {
			$decoded = (array)json_decode($response, true);
			if (!$decoded['error']) {
				$decoded = $this->flattenArray($decoded); // nedded for google
			}
		} elseif (strtolower(trim($this->provider['profileEncoding'])) == 'xml') {
			$decoded = array();
			$decoded_step1 = (array)simplexml_load_string($response);
			foreach ($decoded_step1 as $k=>$v) {
				$decoded[$k] = is_object($v) ? (array)$v : $v; // second level simplxml data
			}
		} else { // url encoded
			$decoded = array(); // relevant? not until now...
		}
		if ($decoded['error']) {
			$error = sprintf($GLOBALS['piObj']->pi_getLL('error_validate_token'), $decoded['error']['type'], $decoded['error']['message']); // Error while validating token-parameter (%s: %s)
			return;
		}
		foreach ($this->provider['profileMap.'] as $dbField => $detailsField) {
			$userinfo[$dbField] = $decoded[$detailsField];
		}

		if (!$userinfo['id']) {
			$error = $GLOBALS['piObj']->pi_getLL('error_getting_userinfo'); // Error: While retrieving user details, the user id was empty
		}
		$userinfo['id'] = 'oauth2-'.$this->provider['key'].'-'.$userinfo['id'];

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dix_easylogin']['hook_userInfo'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dix_easylogin']['hook_userInfo'] as $_classRef) {
				$_procObj = &t3lib_div::getUserObj($_classRef);
				$_procObj->process($userinfo, $decoded, $this);
			}
		}

		return $userinfo;
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_oauth2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_oauth2.php']);
}

?>