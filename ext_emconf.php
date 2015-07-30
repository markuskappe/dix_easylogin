<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "dix_easylogin".
 *
 * Auto generated 17-07-2014 15:04
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Easy Login and Register with OpenID (FE)',
	'description' => 'Do you know facebook connect? Easylogin is even better because it works not only with facebook but also with Google, Yahoo, myOpenId, twitter, and all other providers that offer OpenID or OAuth. It also integrates the common felogin (Username/Password)',
	'category' => 'fe',
	'author' => 'Markus Kappe',
	'author_email' => 'markus.kappe@dix.at',
	'shy' => '',
	'dependencies' => 'fluid,extbase',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.4.2',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-6.2.99',
			'fluid' => '',
			'extbase' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:44:{s:9:"ChangeLog";s:4:"0a99";s:20:"class.ext_update.php";s:4:"6545";s:33:"class.tx_dixeasylogin_realurl.php";s:4:"96c5";s:21:"ext_conf_template.txt";s:4:"396d";s:12:"ext_icon.gif";s:4:"f9b3";s:17:"ext_localconf.php";s:4:"a778";s:14:"ext_tables.php";s:4:"df17";s:14:"ext_tables.sql";s:4:"dcb1";s:36:"icon_tx_dixeasylogin_identifiers.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"1f59";s:10:"README.txt";s:4:"ff95";s:7:"tca.php";s:4:"41bf";s:14:"doc/manual.sxw";s:4:"58d5";s:33:"pi1/class.tx_dixeasylogin_div.php";s:4:"1f61";s:38:"pi1/class.tx_dixeasylogin_facebook.php";s:4:"f58f";s:36:"pi1/class.tx_dixeasylogin_oauth1.php";s:4:"aab8";s:36:"pi1/class.tx_dixeasylogin_oauth2.php";s:4:"d8ac";s:36:"pi1/class.tx_dixeasylogin_openid.php";s:4:"bd72";s:33:"pi1/class.tx_dixeasylogin_pi1.php";s:4:"f779";s:17:"pi1/locallang.xml";s:4:"e13a";s:29:"res/dope/class.dopeopenid.php";s:4:"d654";s:21:"res/icons/blogger.ico";s:4:"6c92";s:22:"res/icons/facebook.jpg";s:4:"d707";s:20:"res/icons/flickr.ico";s:4:"9bac";s:20:"res/icons/google.gif";s:4:"ca68";s:22:"res/icons/linkedin.png";s:4:"7ee6";s:22:"res/icons/myopenid.ico";s:4:"b22b";s:21:"res/icons/twitter.gif";s:4:"a8b3";s:23:"res/icons/wordpress.ico";s:4:"6cec";s:18:"res/icons/xing.png";s:4:"b84d";s:19:"res/icons/yahoo.ico";s:4:"1698";s:19:"res/oauth/OAuth.php";s:4:"ee05";s:25:"res/yadis/HTTPFetcher.php";s:4:"5138";s:21:"res/yadis/Manager.php";s:4:"5d0a";s:33:"res/yadis/ParanoidHTTPFetcher.php";s:4:"4489";s:23:"res/yadis/ParseHTML.php";s:4:"965f";s:30:"res/yadis/PlainHTTPFetcher.php";s:4:"09c9";s:17:"res/yadis/XML.php";s:4:"09c4";s:18:"res/yadis/XRDS.php";s:4:"bfe5";s:19:"res/yadis/Yadis.php";s:4:"7567";s:20:"static/constants.txt";s:4:"3777";s:16:"static/setup.txt";s:4:"5117";s:20:"templates/login.tmpl";s:4:"561a";s:19:"templates/xrds.tmpl";s:4:"adfe";}',
	'suggests' => array(
	),
);

?>