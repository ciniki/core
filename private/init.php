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
	require_once($ciniki_root . '/ciniki-api/core/private/loadCinikiConfig.php');
	if( ciniki_core_loadCinikiConfig($ciniki, $ciniki_root) == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'9', 'msg'=>'Internal configuration error'));
	}

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

	$ciniki['syncqueue'] = array();

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
