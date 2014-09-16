<?php
//
// Description
// -----------
// This function will initialize the $ciniki variable which
// must be passed to all ciniki function.  This function
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
function ciniki_core_init($ciniki_root, $output_format) {

	//
	// Initialize the ciniki structure, and setup the return value
	// to include the stat.
	//
	$ciniki = array();

	//
	// Load the config
	//
	require_once($ciniki_root . '/ciniki-mods/core/private/loadCinikiConfig.php');
	if( ciniki_core_loadCinikiConfig($ciniki, $ciniki_root) == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'9', 'msg'=>'Internal configuration error'));
	}

	//
	// Initialize the object variable.  This stores all the object information as loaded, so no need to load again.
	//
	$ciniki['objects'] = array();
	
	//
	// Initialize the business variable.  This is used to store settings for the business
	//
	$ciniki['business'] = array('settings'=>array(), 'modules'=>array(), 'user'=>array('perms'=>0));

	//
	// Initialize the request variables
	//
	$ciniki['request'] = array();
	$ciniki['request']['api_key'] = '';
	$ciniki['request']['auth_token'] = '';
	$ciniki['request']['method'] = '';
	$ciniki['request']['args'] = array();

	//
	// Initialize the response variables, 
	// default to respond with xml.
	//
	$ciniki['response'] = array();
	$ciniki['response']['format'] = $output_format;

	$ciniki['emailqueue'] = array();
	$ciniki['syncqueue'] = array();
	if( isset($ciniki['config']['ciniki.core']['sync.log_lvl']) ) {
		$ciniki['syncloglvl'] = $ciniki['config']['ciniki.core']['sync.log_lvl'];
	} else {
		$ciniki['syncloglvl'] = 0;
	}
	$ciniki['synclogfile'] = '';

	//
	// Initialize Database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInit');
	$rc = ciniki_core_dbInit($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Initialize Session
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sessionInit');
	$rc = ciniki_core_sessionInit($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'ciniki'=>$ciniki);
}
?>
