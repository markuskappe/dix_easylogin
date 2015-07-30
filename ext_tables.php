<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$TCA['tx_dixeasylogin_identifiers'] = array(
	'ctrl' => array(
		'title'     => 'LLL:EXT:dix_easylogin/locallang_db.xml:tx_dixeasylogin_identifiers',        
		'label'     => 'conn_name',    
		'label_alt' => 'identifier',    
		'label_alt_force' => true,
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate DESC',    
		'delete' => 'deleted',    
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_dixeasylogin_identifiers.gif',
	),
);
/*
if (version_compare(TYPO3_branch, '6.1', '<')) {
	t3lib_div::loadTCA('tt_content');
}
*/
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages';


t3lib_extMgm::addPlugin(array(
	'LLL:EXT:dix_easylogin/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
),'list_type');

$tempColumns = array (
/*
	'tx_dixeasylogin_openid' => array (        
		'exclude' => 0,        
		'label' => 'LLL:EXT:dix_easylogin/locallang_db.xml:fe_users.tx_dixeasylogin_openid',        
		'config' => array (
			'type' => 'input',    
			'size' => '30',
		)
	),
*/
	'tx_dixeasylogin_identifiers' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:dix_easylogin/locallang_db.xml:fe_users.tx_dixeasylogin_identifiers',
		'config' => array (
			'type' => 'inline', // doku: http://typo3.org/documentation/document-library/core-documentation/doc_core_api/4.3.0/view/4/2/ (search for "inline")
			'languageMode' => 'inherit',
			
			'foreign_table' => 'tx_dixeasylogin_identifiers',
			'foreign_field' => 'user',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 20,
		)
	),
);

/*
if (version_compare(TYPO3_branch, '6.1', '<')) {
	t3lib_div::loadTCA('fe_users');
}
*/
t3lib_extMgm::addTCAcolumns('fe_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_users','tx_dixeasylogin_identifiers;;;;1-1-1');

t3lib_extMgm::addStaticFile($_EXTKEY,'static/', 'Easylogin');

/*
			'behaviour' => array(
				'localizationMode' => 'select',
				'localizeChildrenAtParentLocalization' => 1,
			),
			'appearance' => array(
				'showPossibleLocalizationRecords' => 1,
				'showAllLocalizationLink' => 1,
				'showSynchronizationLink' => 1,
				'useSortable' => 1,
				'levelLinksPosition' => 'bottom',
			),
*/


?>