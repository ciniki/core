<?php
//
// Description
// -----------
// This function is a generic wrapper that can call any method.
// It takes an array as an argument, and withing that must
// contain api_key, and method.
//
// Info
// ----
// status:		beta
//
// Arguments
// ---------
// api_key:		The key assigned to the client application.  This
//				will be verified in the ciniki_core_api_keys module
//
// auth_token:	The auth_token is assigned after authentication.  If
//				auth_token is blank, then only certain method calls are allowed.
//
// method:		The method to call.  This is a decimal notated
//
// format:		What is the requested format of the response.  This can be
//				xml, html, tmpl or hash.  If the request would like json, 
//				xml-rpc, rest or php_serial, then the format
//
function ciniki_core_callPublicMethod(&$ciniki) {
	//
	// Check if the api_key is specified
	//
	if( !isset($ciniki['request']['api_key']) || $ciniki['request']['api_key'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2', 'msg'=>'No api_key supplied'));
	}

	//
	// Check the API Key 
	//
	require_once($ciniki['config']['core']['modules_dir']. '/core/private/checkAPIKey.php');
	$rc = ciniki_core_checkAPIKey($ciniki);
	if( $rc['stat'] != 'ok' || $ciniki['request']['method'] == 'ciniki.core.checkAPIKey' ) { 
		return $rc;
	}

	//
	// FIXME: Log the last_access for the API key
	//
	


	//
	// Check if method has been specified
	//
	if( !isset($ciniki['request']['method']) || $ciniki['request']['method'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3', 'msg'=>'No method supplied'));
	}

	//
	// Parse the method, and the function name.  
	//
	$method_filename = $ciniki['config']['core']['root_dir'] . '/'
		. preg_replace('/([a-z]+)\.([a-z0-9]+)\./', '\1-api/\2/public/', $ciniki['request']['method']) . '.php';
	$method_function = preg_replace('/\./', '_', $ciniki['request']['method']);

	//
	// FIXME: Log the request in the Action Log, update with output
	// at the end of this function if successful
	//
	// require_once($ciniki['config']['core']['modules_dir']. '/core/private/actionLogEntry.php');
	// ciniki_core_actionLogEntry($ciniki);

	//
	// If the user has not authenticated, then only a small number of 
	// methods are available, and they must be listed here.
	//
	$no_auth_methods = array(
		'ciniki.users.auth', 
		'ciniki.users.passwordRequestReset',
		'ciniki.users.changeTempPassword',
		'ciniki.core.echoTest', 
		'ciniki.core.getAddressCountryCodes'
		);

	//
	// Load the session if an auth_token was passed
	//
	if( isset($ciniki['request']['auth_token']) && $ciniki['request']['auth_token'] != '' ) {
		require_once($ciniki['config']['core']['modules_dir']. '/core/private/sessionOpen.php');
		$rc = ciniki_core_sessionOpen($ciniki);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	} 

	//
	// Check if the user needs to be authenticated for this
	//
	if( !in_array($ciniki['request']['method'], $no_auth_methods) 
		&& (!isset($ciniki['session']) || !is_array($ciniki['session']) 
		|| !is_array($ciniki['session']['user']) || !isset($ciniki['session']['user']['id'])
		|| $ciniki['session']['user']['id'] <= 0)
		) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'5', 'msg'=>'Not authenticated'));
	}

	//
	// Check if the method exists, after we check for authentication,
	// because we don't want people to be able to figure out valid
	// function calls by probing.
	//
	if( $method_filename == '' || $method_function == '' || !file_exists($method_filename) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'4', 'msg'=>'Method does not exist'));
	}

	//
	// Include the method function
	//
	require_once($method_filename);

	if( !is_callable($method_function) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1', 'msg'=>'Method does not exist'));
	}

	$method_rc = $method_function($ciniki);

	//
	// Save the session if successful transaction
	//
	if( isset($ciniki['session']['auth_token']) && $ciniki['session']['auth_token'] != '' ) {
		require_once($ciniki['config']['core']['modules_dir']. '/core/private/sessionSave.php');
		$rc = ciniki_core_sessionSave($ciniki);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// FIXME: Update the action log with the results from the request
	//
	// require_once($ciniki['config']['core']['modules_dir']. '/core/private/actionLogResult.php');
	// ciniki_core_actionLogResult($ciniki, );

	return $method_rc;
}
