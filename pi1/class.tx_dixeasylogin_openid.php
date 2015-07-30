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

class tx_dixeasylogin_openid {

	function init($provider, $piVars) {
		$this->provider = $provider;
		$this->piVars = $piVars;
	}

	function main() {
		if ($this->piVars['process']) {
			$error = $this->redirToProvider();
		} elseif ($this->piVars['action']=="verify" && t3lib_div::_GP('openid_mode') != "cancel") {
			$error = $this->verifyLogin();
		}
		return $error;
	}

	function redirToProvider() {
		$error = null;
		$openid_url = $this->getOpenidUrl(trim($this->piVars['userName']), $error);
		if ($error) { return $error; }

		$openid = t3lib_div::makeInstance('Dope_OpenID', $openid_url);
		$openid->setReturnURL(tx_dixeasylogin_div::getVerifyUrl());
		$trustRoot = t3lib_div::locationHeaderUrl('/');
		$openid->SetTrustRoot($trustRoot);
		if ($GLOBALS['piObj']->conf['optionalInfo']) {
			$openid->setOptionalInfo(t3lib_div::trimExplode(',', $GLOBALS['piObj']->conf['optionalInfo'])); // config
		}
		$openid->setRequiredInfo(t3lib_div::trimExplode(',', $GLOBALS['piObj']->conf['requiredInfo'])); // config
		//$openid->setPapePolicies('http://schemas.openid.net/pape/policies/2007/06/phishing-resistant '); // config
		//$openid->setPapeMaxAuthAge(120); // config
		
		/*
		* Attempt to discover the user's OpenID provider endpoint
		*/
		$endpoint_url = $openid->getOpenIDEndpoint();
		if($endpoint_url){
			$openid->redirect();
		} else {
			$the_error = $openid->getError();
			$error = sprintf($GLOBALS['piObj']->pi_getLL('error_endpoint'), $the_error['code'], $the_error['description']); // Error while getting OpenID endpoint (%s): %s
		}
		return $error;
	}

	function getOpenidUrl($name, &$error) {
		$url = str_replace('###NAME###', $name, $this->provider['url']);
		if (!tx_dixeasylogin_div::validateUrl($url)) {
			$error = sprintf($GLOBALS['piObj']->pi_getLL('invalid_url'), htmlspecialchars($url)); // Error: OpenID Identifier is not in proper format (%s).
		}
		return $url;
	}

	function verifyLogin() {
		$openid_id = t3lib_div::_GP('openid_identity');
		$openid = t3lib_div::makeInstance('Dope_OpenID', $openid_id);
		$validate_result = $openid->validateWithServer();
		if ($validate_result === TRUE) {

			$userinfo = $openid->filterUserInfo($_GET);
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dix_easylogin']['hook_userInfo'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dix_easylogin']['hook_userInfo'] as $_classRef) {
					$_procObj = &t3lib_div::getUserObj($_classRef);
					$_procObj->process($userinfo, $_GET, $this);
				}
			}

			return tx_dixeasylogin_div::loginFromIdentifier($openid_id, $userinfo);
		} else if ($openid->isError() === TRUE){
			$the_error = $openid->getError();
			$error = sprintf($GLOBALS['piObj']->pi_getLL('error_validate'), $the_error['code'], $the_error['description']); // Error: Could not validate the OpenID
		} else {
			$error = sprintf($GLOBALS['piObj']->pi_getLL('error_validate_nocode'), $openid_id); // Error: Could not validate the OpenID
		}
		return $error;
	}

}





if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_openid.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_openid.php']);
}

?>