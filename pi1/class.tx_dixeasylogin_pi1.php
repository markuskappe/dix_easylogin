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

// @requires cUrl, extbase, fluid

//require_once(PATH_tslib.'class.tslib_pibase.php');
//require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(t3lib_extMgm::extPath("dix_easylogin")."res/dope/class.dopeopenid.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."res/oauth/OAuth.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."res/yadis/Yadis.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."res/yadis/HTTPFetcher.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."res/yadis/PlainHTTPFetcher.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."res/yadis/ParanoidHTTPFetcher.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."res/yadis/ParseHTML.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."res/yadis/XML.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."res/yadis/XRDS.php");

require_once(t3lib_extMgm::extPath("dix_easylogin")."pi1/class.tx_dixeasylogin_div.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."pi1/class.tx_dixeasylogin_facebook.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."pi1/class.tx_dixeasylogin_oauth1.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."pi1/class.tx_dixeasylogin_oauth2.php");
require_once(t3lib_extMgm::extPath("dix_easylogin")."pi1/class.tx_dixeasylogin_openid.php");

/**
 * Plugin 'Easy Login' for the 'dix_easylogin' extension.
 *
 * @author	Markus Kappe <markus.kappe@dix.at>
 * @package	TYPO3
 * @subpackage	tx_dixeasylogin
 */
class tx_dixeasylogin_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_dixeasylogin_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_dixeasylogin_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'dix_easylogin';	// The extension key.
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		if (!function_exists('curl_exec')) { return ('Error: easylogin requires the PHP cURL extension.'); }
		$GLOBALS['piObj'] = &$this;

		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;    // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!

		if ($this->piVars['action'] == 'xrds') {
			$content = tx_dixeasylogin_div::renderFluidTemplate('xrds.tmpl', tx_dixeasylogin_div::getVerifyUrl());
			echo $content; exit();
		}
		$this->providers = $this->getProvider();
		
		if ($loginType = $this->piVars['loginType']) {
			$GLOBALS["TSFE"]->fe_user->setKey("ses", "easylogin_loginType", $loginType);
		} else {
			$loginType = $GLOBALS["TSFE"]->fe_user->getKey("ses", "easylogin_loginType");
			if (!$this->providers[$loginType]) {  $loginType = null; }
		}
		if ($loginType) {
			$provider = $this->providers[$loginType];
			switch ($provider['type']) {
				case 'FACEBOOK':
					$obj = t3lib_div::makeInstance('tx_dixeasylogin_facebook');
					break;
				case 'OAUTH1':
					$obj = t3lib_div::makeInstance('tx_dixeasylogin_oauth1');
					break;
				case 'OAUTH2':
					$obj = t3lib_div::makeInstance('tx_dixeasylogin_oauth2');
					break;
				case 'OPENID':
					$obj = t3lib_div::makeInstance('tx_dixeasylogin_openid');
					break;
				default:
					return "undefined authentication method &quot;".$provider['type']."&quot;, please check TypoScript";
			}
			$obj->init($provider, $this->piVars);
			$error = $obj->main();
		}
		
		$values = array(
			'provider' => $this->providers,
			'formaction' => tx_dixeasylogin_div::getSelfUrl(),
			'anchorPrefix' => t3lib_div::getIndpEnv('REQUEST_URI'),
			'prefix' => $this->prefixId,
			'user' => $GLOBALS['TSFE']->fe_user->user,
			'error' => $error,
			'constants' => array('CONTENTELEMENT' => 'CONTENTELEMENT'),
			'associated' => tx_dixeasylogin_div::getAssociatedProvider($GLOBALS['TSFE']->fe_user->user['uid']),
			'verifyUrl' => tx_dixeasylogin_div::getVerifyUrl(),
		);

		$content = tx_dixeasylogin_div::renderFluidTemplate('login.tmpl', $values);
		return $this->pi_wrapInBaseClass($content);
	}

	function getProvider() {
		$result = array();
		foreach ($this->conf['provider.'] as $key=>$type) {
			if (!(int)$key || strstr($key, '.')) { continue; } // just continue for the numeric ones, e.g. '10' but not '10.'
			$conf = $this->conf['provider.'][$key.'.'];
			$conf['type'] = trim(strtoupper($type));
			$conf['key'] = $key;
			$conf['icon'] = $conf['icon'] ? tx_dixeasylogin_div::getFileRelFileName($conf['icon']) : '';
			$conf['showMe'] = (!(bool)$GLOBALS['TSFE']->fe_user->user['uid'] || $conf['showWhenLoggedIn']);
			switch ($conf['type']) {
				case 'CONTENTELEMENT':
					$conf['content'] = tx_dixeasylogin_div::render_ttContent($conf['uid']);
					break;
				case 'OAUTH':
					break;
				case 'OPENID':
					$conf['withUsername'] = (bool) (strstr($conf['url'], '###NAME###'));
					break;
			} 
			$result[$key] = $conf;
		}
		return $result;
	}

	function sendXrdsHeader($content, $conf) { // called as USER_INT from TypoScript (page.2 = USER_INT)
		$xrdsLocation = t3lib_div::locationHeaderUrl('index.php?id='.$conf['pid'].'&tx_dixeasylogin_pi1[action]=xrds');
		header('X-XRDS-Location:'.$xrdsLocation);
	}
}







if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_pi1.php']);
}

?>