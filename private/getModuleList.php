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
		$dir = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/';
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
			$dh = opendir($dir);
			while( false !== ($filename = readdir($dh))) {
				// Skip all files starting with ., and core
				// and other reserved named modules which should be always available
				if( $filename[0] == '.' 
					) {
					continue;
				}
				if( is_dir($dir . $filename) && file_exists($dir . $filename . '/_info.ini')) {
					array_push($modules, $filename);
				}
			}
			closedir($dh);
		}

		$rsp = array();
		foreach($modules as $module) {
			if( file_exists($dir . $module . '/_info.ini') ) {
				$info = parse_ini_file($dir . $module . '/_info.ini');
				if( isset($info['name']) && $info['name'] != '' ) {
					// Assume active is No, this function just returns what is installed
					array_push($rsp, array('label'=>$info['name'], 'package'=>$package, 'name'=>$module, 'installed'=>'Yes', 'active'=>'No'));
				}
			}
		}
	}

	return array('stat'=>'ok', 'modules'=>$rsp);
}
?>
