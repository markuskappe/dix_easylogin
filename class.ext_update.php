<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Markus Kappe <markus.kappe@dix.at>
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
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Update Class for DB Updates of version <= 0.2.5
 *
 * Sets new randomized passwords to each user in fe_users registered via 3rd party authentication provider
 *
 * @author Markus Kappe <markus.kappe@dix.at>
 * @credits Matteo Saivo <msavio@ajado.com>
 */
class ext_update {
	function main() {
		$content = '<p>If you upgrade from version 0.2.5 or earlier you are advised to randomize the passwords from users that registered or connected via 3rd party authentication. Please press &quot;UPDATE Passwords&quot; button to start.</p>';
		$content .= '<form name="updateForm" action="" method="post">';
		$content .= '<input type="hidden" name="updatePW" value="updatePW">';
		$content .= '<p><input type="submit" name="submitButton" value="UPDATE Passwords"></p>';
		$content .= '</form>';

		if (t3lib_div::_GP('updatePW')) {
			$content .= $this->doUpdatePW();
		}

		$content .= '<br /><br /><p>If you upgrade from below 0.3.0 you are advised to update the identifiers. This is due to a change in the database. This script transfers the old identifiers to the new place. Please press &quot;UPDATE Identifiers&quot; button to start.</p>';
		$content .= '<form name="updateForm" action="" method="post">';
		$content .= '<input type="hidden" name="updateID" value="updateID">';
		$content .= '<p><input type="submit" name="submitButton" value="UPDATE Identifiers"></p>';
		$content .= '</form>';


		if (t3lib_div::_GP('updateID')) {
			$content .= $this->doUpdateID();
		}
		return $content;
 
	}

	function doUpdatePW() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', "tx_dixeasylogin_openid <> ''"); 
		
		$content = '<p>Start randomizing passwords</p>';
		
		$i = 0;
		
		while ($user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'fe_users',
				'uid = ' . $user['uid'],
				array('password' => t3lib_div::getRandomHexString(32))
			);
			$i++;
		}
		
		$content .= '<p><strong style="color:green;">Finished update. ' . $i . ' user(s) updated.</strong> You can redo this update any time.</p>';
		
		return $content;
	}

	function doUpdateID() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'fe_users', "tx_dixeasylogin_openid <> ''"); 
		
		$content = '<p>Start transfering identifiers</p>';
		
		$i = 0;
		
		while ($user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'fe_users',
				'uid = ' . $user['uid'],
				array('tx_dixeasylogin_openid' => '')
			);
			$i++;

			$values = array(
				'user' => $user['uid'],
				'crdate' => time(),
				'tstamp' => time(),
				'identifier' => $user['tx_dixeasylogin_openid'],
				'pid' => $user['pid'],
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dixeasylogin_identifiers', $values);
		}

		
		$content .= '<p><strong style="color:green;">Finished update. ' . $i . ' user(s) updated.</strong>.</p>';
		
		return $content;
	}


	/**
	 * Checks how many rows are found and returns true if there are any
	 * (this function is called from the extension manager)
	 *
	 * @param	string		$what: what should be updated
	 * @return	boolean
	 */
	function access() {
		return true;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/class.ext_update.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/class.ext_update.php']);
}

?>