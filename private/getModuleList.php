<?php
//
// Description
// -----------
// This function will return an array with the list of modules available in the system.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// 
//
//
function ciniki_core_getModuleList($ciniki) {

	return array(
		array('label'=>'Customers', 'name'=>'customers',			'installed'=>'Yes', 'active'=>'No', 'bits'=>0x0001),
		array('label'=>'Products', 'name'=>'products',				'installed'=>'Yes', 'active'=>'No', 'bits'=>0x0002),
		array('label'=>'Inventory', 'name'=>'inventory',			'installed'=>'No', 'active'=>'No', 'bits'=>0x0004),
		array('label'=>'Website', 'name'=>'website',				'installed'=>'Yes', 'active'=>'No', 'bits'=>0x0008),
		array('label'=>'POS', 'name'=>'pos', 						'installed'=>'No', 'active'=>'No', 'bits'=>0x0010),
		array('label'=>'Manufacturing', 'name'=>'manufacturing',	'installed'=>'No', 'active'=>'No', 'bits'=>0x0020),
		array('label'=>'Newsletters', 'name'=>'newsletters',		'installed'=>'No', 'active'=>'No', 'bits'=>0x0040),
		array('label'=>'Security Cameras', 'name'=>'cameras',	 	'installed'=>'No', 'active'=>'No', 'bits'=>0x0080),
		array('label'=>'Scheduling', 'name'=>'scheduling',	 		'installed'=>'No', 'active'=>'No', 'bits'=>0x0100),
		array('label'=>'Online Surveys', 'name'=>'surveys',		 	'installed'=>'No', 'active'=>'No', 'bits'=>0x0200),
		array('label'=>'Event Management', 'name'=>'events',		'installed'=>'No', 'active'=>'No', 'bits'=>0x0400),
		array('label'=>'Bug Tracking', 'name'=>'bugs',		 		'installed'=>'Yes', 'active'=>'No', 'bits'=>0x0800),
		array('label'=>'Feature Requests','name'=>'features',		'installed'=>'Yes', 'active'=>'No', 'bits'=>0x1000),
		array('label'=>'Questions', 'name'=>'questions',			'installed'=>'Yes', 'active'=>'No', 'bits'=>0x2000),
		array('label'=>'FAQ', 'name'=>'faq',		 				'installed'=>'Yes', 'active'=>'No', 'bits'=>0x4000),
		array('label'=>'Customer Service', 'name'=>'cserve',	 	'installed'=>'No', 'active'=>'No', 'bits'=>0x8000),
		array('label'=>'Toolbox', 'name'=>'toolbox',	 			'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00010000),
		array('label'=>'Friends', 'name'=>'friends',	 			'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00020000),
		array('label'=>'Media', 'name'=>'media',	 				'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00040000),
		array('label'=>'Documents', 'name'=>'documents',	 		'installed'=>'No', 'active'=>'No', 'bits'=>0x00080000),
		array('label'=>'Appearances', 'name'=>'appearances',	 	'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00100000),
		array('label'=>'Wine Production', 'name'=>'wineproduction',	'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00200000),
		array('label'=>'Subscriptions', 'name'=>'subscriptions',	'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00400000),
		array('label'=>'Tasks', 'name'=>'tasks',					'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00800000),
		array('label'=>'Cron', 'name'=>'cron',						'installed'=>'Yes', 'active'=>'No', 'bits'=>0x01000000),
		);
}
?>
