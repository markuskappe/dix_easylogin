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

class tx_dixeasylogin_oauth1 {
	function init($provider, $piVars) {
		$this->provider = $provider;
		$this->piVars = $piVars;

		$sigClass = 'OAuthSignatureMethod_'.trim(strtoupper($this->provider['sigMethod']));
		if (!class_exists($sigClass)) { $sigClass = 'OAuthSignatureMethod_HMAC_SHA1'; }
		$this->sigMethod = t3lib_div::makeInstance($sigClass);
		$this->consumer = new OAuthConsumer($this->provider['consumerKey'], $this->provider['consumerSecret'], NULL);
	}

	function main() {
		if ($this->piVars['process']) {
			$error = $this->getRequestToken();
			if (!$error) {
				$error = $this->redirToProvider();
			}
		} elseif ($this->piVars['action']=="verify") {
			$error = $this->verifyLogin();
		}
		return $error;
	}

	function getRequestToken() {
	  $req_req = OAuthRequest::from_consumer_and_token($this->consumer, NULL, "GET", $this->provider['requestTokenUrl'], array());
	  $req_req->set_parameter('oauth_callback', tx_dixeasylogin_div::getVerifyUrl()); // xing expects this parameter when requesting the request token; twitter when redirecting to provider
	  $req_req->sign_request($this->sigMethod, $this->consumer, NULL);

		$response = tx_dixeasylogin_div::makeCURLRequest((string)$req_req, 'GET', array());

	  $params = array();
	  parse_str($response, $params);
	  $this->oauth_token = $params['oauth_token'];
	  $this->oauth_token_secret = $params['oauth_token_secret'];
	  if (!$this->oauth_token || !$this->oauth_token_secret) { return sprintf($GLOBALS['piObj']->pi_getLL('error_reqToken'), $response); }
		$GLOBALS["TSFE"]->fe_user->setKey("ses", "easylogin_oauth_token", $this->oauth_token);
		$GLOBALS["TSFE"]->fe_user->setKey("ses", "easylogin_oauth_token_secret", $this->oauth_token_secret);
	}

	function redirToProvider() { // authorize
		$callback_url = tx_dixeasylogin_div::getVerifyUrl();
  	$auth_url = $this->provider['authorizeUrl'] . '?oauth_token='.$this->oauth_token.'&oauth_callback='.urlencode($callback_url);
	  header("Location: $auth_url");
	}

	function verifyLogin() { // get access token
		$error = '';
		$this->oauth_token = $GLOBALS["TSFE"]->fe_user->getKey("ses", "easylogin_oauth_token");
		$this->oauth_token_secret = $GLOBALS["TSFE"]->fe_user->getKey("ses", "easylogin_oauth_token_secret");

		$tokenObj = t3lib_div::makeInstance('OAuthConsumer', $this->oauth_token, $this->oauth_token_secret);
	  $acc_req = OAuthRequest::from_consumer_and_token($this->consumer, $tokenObj, "GET", $this->provider['accessTokenUrl'], array());
	  if ($verifier = t3lib_div::_GP('oauth_verifier')) {
	  	$acc_req->set_parameter('oauth_verifier', $verifier); // xing expects this parameter
	  }
	  $acc_req->sign_request($this->sigMethod, $this->consumer, $tokenObj);

		$response = tx_dixeasylogin_div::makeCURLRequest((string)$acc_req, 'GET', array());

	  $params = array();
	  parse_str($response, $params);
	  // problem here: according to oauth specs there is no need for a response parameter identifing the user. 
		// twitter uses "user_id" but other oauth providers may use "userid", "uid", "user", "id" or worst: nothing at all
		if (!$params['oauth_token']) {
			$error = sprintf($GLOBALS['piObj']->pi_getLL('error_getting_accesstoken'), $response); // Error: Could not get access token (%s)
			return $error; 
		}
	  $this->oauth_token = $params['oauth_token'];
	  $this->oauth_token_secret = $params['oauth_token_secret'];
		
		$userinfo = $this->getUserInfo($params, $error);
		if ($error) { return $error; }
		return tx_dixeasylogin_div::loginFromIdentifier($userinfo['id'], $userinfo);
	}

	function getUserInfo($accessTokenParams, &$error) {
		$endpoint = $this->provider['requestProfileUrl'];
		$markerNames = $this->extractMarker($endpoint);
		foreach ($markerNames as $v) {
			$endpoint = str_replace('###'.$v.'###', $accessTokenParams[$v], $endpoint);
		}
		$tokenObj = t3lib_div::makeInstance('OAuthConsumer', $this->oauth_token, $this->oauth_token_secret);
	  $req = OAuthRequest::from_consumer_and_token($this->consumer, $tokenObj, "GET", $endpoint, array());
	  $req->sign_request($this->sigMethod, $this->consumer, $tokenObj);

		$response = tx_dixeasylogin_div::makeCURLRequest((string)$req, 'GET', array());
		$details = json_decode($response, true);
		if ($details['users']) { $details = $details['users']; } // when the details are stored in an object capsulated in an array capsulated in an object (xing)
		if ($details[0]) { $details = $details[0]; } // when the details are stored in an object capsulated in an array (twitter)
		$userinfo = array();
		foreach ($this->provider['profileMap.'] as $dbField => $detailsField) {
			$userinfo[$dbField] = $details[$detailsField];
		}
		if (!$userinfo['id']) {
			$error = $GLOBALS['piObj']->pi_getLL('error_getting_userinfo'); // Error: While retrieving user details, the user id was empty
		}
		$userinfo['id'] = 'oauth1-'.$this->provider['key'].'-'.$userinfo['id'];

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dix_easylogin']['hook_userInfo'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dix_easylogin']['hook_userInfo'] as $_classRef) {
				$_procObj = &t3lib_div::getUserObj($_classRef);
				$_procObj->process($userinfo, $details, $this);
			}
		}

		return $userinfo;
	}
	
	function extractMarker($str) {
		$result = array();
		while (strpos($str, '###') !== false) {
			$start = strpos($str, '###') + 3;
			$stop = strpos($str, '###', $start);
			$result[] = substr($str, $start, $stop-strlen($str));
			$str = substr($str, $stop+3);
		}
		return $result;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_oauth1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_oauth1.php']);
}

?>