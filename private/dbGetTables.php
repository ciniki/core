<?php
//
// Description
// -----------
// This method will return the tables that are required for ciniki and other packages installed.
//
// Arguments
// ---------
// ciniki:
// module:			The name of the module for the transaction, which should include the 
//					package in dot notation.  Example: ciniki.artcatalog
//
function ciniki_core_dbGetTables($ciniki) {
	//
	// The following array is used but the upgrade process to tell what
	// tables are required, and their current versions
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'getModuleList');
	$rc = ciniki_core_getModuleList($ciniki);	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$modules = $rc['modules'];

	//
	// Get the tables required for each module
	//
	$rsp = array();
	foreach($modules as $module) {
		$dir = $ciniki['config']['ciniki.core']['root_dir'] . '/' . $module['package'] . '-mods/' . $module['name'] . '/db/';
		if( !is_dir($dir) ) {
			continue;  		// No tables
		}
		$dh = opendir($dir);
		while( false !== ($filename = readdir($dh))) {
			// Skip all files starting with ., and core
			// and other reserved named modules which should be always available
			if( $filename[0] == '.' ) {
				continue;
			}
			if( preg_match('/^(.*)\.schema$/', $filename, $matches) ) {
				$table = $matches[1];
				$rsp[$table] = array('package'=>$module['package'], 'module'=>$module['name'], 
					'database_version'=>'-', 'schema_version'=>'-');
			}
		}

		closedir($dh);
	}
	
	return array('stat'=>'ok', 'tables'=>$rsp);
}
?>
