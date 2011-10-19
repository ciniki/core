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
// moss: 			The moss data structure
// moss_root:		The root directory for the moss code.  This is where the config file will be.
// 
//
//
function moss_core_loadMossConfig(&$moss, $moss_root) {
	
	$config_file = $moss_root . "/config.ini";

	if( is_file($config_file) ) {
		$moss['config'] = parse_ini_file($config_file, true);
	} else {
		return false;
	}

	if( $moss['config'] == false ) {
		return false;
	}

	if( !isset($moss['config']['core']) || !isset($moss['config']['core']['root_dir']) ) {
		return false;
	}

	return true;
}
?>
