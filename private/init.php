<?php
//
// Description
// -----------
// This function will initialize the $moss variable which
// must be passed to all moss function.  This function
// must be called before any others.
//
// This function will also:
// - load config
// - init database
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// config_file:			The path to the config file must be passed.
//
function moss_core_init($moss_root, $output_format) {

	//
	// Initialize the moss structure, and setup the return value
	// to include the stat.
	//
	$moss = array();

	//
	// Load the config
	//
	require_once($moss_root . '/moss-modules/core/private/loadMossConfig.php');
	if( moss_core_loadMossConfig($moss, $moss_root) == false ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'9', 'msg'=>'Internal configuration error'));
	}

	//
	// Initialize the request variables
	//
	$moss['request'] = array();
	$moss['request']['api_key'] = '';
	$moss['request']['auth_token'] = '';
	$moss['request']['method'] = '';
	$moss['request']['args'] = array();

	//
	// Initialize the response variables, 
	// default to respond with xml.
	//
	$moss['response'] = array();
	$moss['response']['format'] = $output_format;

	//
	// Initialize Database
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbInit.php');
	$rc = moss_core_dbInit($moss);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Initialize Session
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/sessionInit.php');
	$rc = moss_core_sessionInit($moss);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'moss'=>$moss);
}
?>
