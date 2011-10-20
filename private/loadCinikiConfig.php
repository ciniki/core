<?php
//
// Description
// -----------
// This function will load the config file
// and check for the core root dir variable is set.
//
// Info
// ----
// Status: 			beta
// 
// Arguments
// ---------
// ciniki: 			The ciniki data structure
// ciniki_root:		The root directory for the ciniki code.  This is where the config file will be.
// 
//
//
function ciniki_core_loadCinikiConfig(&$ciniki, $ciniki_root) {
	
	$config_file = $ciniki_root . "/ciniki-api.ini";

	if( is_file($config_file) ) {
		$ciniki['config'] = parse_ini_file($config_file, true);
	} else {
		return false;
	}

	if( $ciniki['config'] == false ) {
		return false;
	}

	if( !isset($ciniki['config']['core']) || !isset($ciniki['config']['core']['root_dir']) ) {
		return false;
	}

	return true;
}
?>
