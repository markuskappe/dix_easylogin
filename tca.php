<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_dixeasylogin_identifiers'] = array(
	'ctrl' => $TCA['tx_dixeasylogin_identifiers']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,user,conn_type,identifier'
	),
	'feInterface' => $TCA['tx_dixeasylogin_identifiers']['feInterface'],
	'columns' => array(
		'hidden' => array(		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array(
				'type'    => 'check',
				'default' => '0'
			)
		),
		'user' => array(		
			'exclude' => 0,		
			'label' => 'LLL:EXT:dix_easylogin/locallang_db.xml:tx_dixeasylogin_identifiers.user',		
			'config' => array(
				'type' => 'group',	
				'internal_type' => 'db',	
				'allowed' => 'fe_users',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'conn_type' => array(		
			'exclude' => 0,		
			'label' => 'LLL:EXT:dix_easylogin/locallang_db.xml:tx_dixeasylogin_identifiers.conn_type',		
			'config' => array(
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'trim',
			)
		),
		'conn_name' => array(		
			'exclude' => 0,		
			'label' => 'LLL:EXT:dix_easylogin/locallang_db.xml:tx_dixeasylogin_identifiers.conn_name',		
			'config' => array(
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'trim',
			)
		),
		'identifier' => array(		
			'exclude' => 0,		
			'label' => 'LLL:EXT:dix_easylogin/locallang_db.xml:tx_dixeasylogin_identifiers.identifier',		
			'config' => array(
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required,trim',
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden;;1;;1-1-1, user, conn_name, conn_type, identifier')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
?>