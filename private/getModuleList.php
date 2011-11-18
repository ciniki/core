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

	//
	// This list has to be built from the directory structure
	//
	if( isset($ciniki['config']['core']['packages']) && $ciniki['config']['core']['packages'] != '' ) {
		$packages = preg_split('/,/', $ciniki['config']['core']['packages']);
	} else {
		$packages = 'ciniki';				// Default to ciniki
	}

	//
	// Build the list of modules from package directories, unless 
	// otherwise specified in the config
	//
	foreach($packages as $package) {
		//
		// Check if there is a list of modules overriding in the config file for this package
		//
		if( isset($ciniki['config']['core'][$package . '.modules']) 
			&& $ciniki['config']['core'][$package . '.modules'] != ''
			&& $ciniki['config']['core'][$package . '.modules'] != '*' ) {
			$modules = preg_split('/,/', $ciniki['config']['core'][$package . '.modules']);
		} 
	
		//
		// If nothing set in config, build from directory, ignoring core
		//
		else {
			$modules = array();
			$dir = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/';
			$dh = opendir($dir);
			while( false !== ($filename = readdir($dh))) {
				// Skip all files starting with ., and core
				// and other reserved named modules which should be always available
				if( $filename[0] == '.' 
					|| $filename == 'core' 
					|| $filename == 'businesses' 
					|| $filename == 'users' 
					|| $filename == 'images' 
					) {
					continue;
				}
				if( is_dir($dir . $filename) ) {
					array_push($modules, $filename);
				}
			}
		}

		$rsp = array();
		foreach($modules as $module) {
			array_push($rsp, array('label'=>$module, 'name'=>$module, 'installed'=>'Yes', 'active'=>'Yes'));	
		}
	}

	return $rsp;


	return array(
		array('label'=>'Customers', 'name'=>'customers',			'installed'=>'Yes', 'active'=>'No', 'bits'=>0x0001),
		array('label'=>'Products', 'name'=>'products',				'installed'=>'Yes', 'active'=>'No', 'bits'=>0x0002),
		array('label'=>'Inventory', 'name'=>'inventory',			'installed'=>'No', 'active'=>'No', 'bits'=>0x0004),
		array('label'=>'Website', 'name'=>'website',				'installed'=>'No', 'active'=>'No', 'bits'=>0x0008),
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
		array('label'=>'FAQ', 'name'=>'faq',		 				'installed'=>'No', 'active'=>'No', 'bits'=>0x4000),
		array('label'=>'Customer Service', 'name'=>'cserve',	 	'installed'=>'No', 'active'=>'No', 'bits'=>0x8000),
		array('label'=>'Toolbox', 'name'=>'toolbox',	 			'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00010000),
		array('label'=>'Friends', 'name'=>'friends',	 			'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00020000),
		array('label'=>'Media', 'name'=>'media',	 				'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00040000),
		array('label'=>'Documents', 'name'=>'documents',	 		'installed'=>'No', 'active'=>'No', 'bits'=>0x00080000),
		array('label'=>'Appearances', 'name'=>'appearances',	 	'installed'=>'No', 'active'=>'No', 'bits'=>0x00100000),
		array('label'=>'Wine Production', 'name'=>'wineproduction',	'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00200000),
		array('label'=>'Subscriptions', 'name'=>'subscriptions',	'installed'=>'Yes', 'active'=>'No', 'bits'=>0x00400000),
		array('label'=>'Tasks', 'name'=>'tasks',					'installed'=>'No', 'active'=>'No', 'bits'=>0x00800000),
		array('label'=>'Cron', 'name'=>'cron',						'installed'=>'Yes', 'active'=>'No', 'bits'=>0x01000000),
		);
}
?>
