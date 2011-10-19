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
//				will be verified in the core_api_keys module
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
function moss_core_callPublicMethod(&$moss) {
	//
	// Check if the api_key is specified
	//
	if( !isset($moss['request']['api_key']) || $moss['request']['api_key'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'2', 'msg'=>'No api_key supplied'));
	}

	//
	// Check the API Key 
	//
	require_once($moss['config']['core']['modules_dir']. '/core/private/checkAPIKey.php');
	$rc = moss_core_checkAPIKey($moss);
	if( $rc['stat'] != 'ok' || $moss['request']['method'] == 'moss.core.checkAPIKey' ) { 
		return $rc;
	}

	//
	// FIXME: Log the last_access for the API key
	//
	


	//
	// Check if method has been specified
	//
	if( !isset($moss['request']['method']) || $moss['request']['method'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'3', 'msg'=>'No method supplied'));
	}

	//
	// Parse the method, and the function name
	//
	$method_filename = $moss['config']['core']['modules_dir'] . '/' 
		. preg_replace('/moss\.(.*)\./', '\1/public/', $moss['request']['method']) . '.php';

	$method_function = preg_replace('/\./', '_', $moss['request']['method']);

	//
	// FIXME: Log the request in the Action Log, update with output
	// at the end of this function if successful
	//
	// require_once($moss['config']['core']['modules_dir']. '/core/private/actionLogEntry.php');
	// moss_core_actionLogEntry($moss);

	//
	// If the user has not authenticated, then only a small number of 
	// methods are available, and they must be listed here.
	//
	$no_auth_methods = array(
		'moss.users.auth', 
		'moss.core.echoTest', 
		'moss.core.getAddressCountryCodes'
		);

	//
	// Load the session if an auth_token was passed
	//
	if( isset($moss['request']['auth_token']) && $moss['request']['auth_token'] != '' ) {
		require_once($moss['config']['core']['modules_dir']. '/core/private/sessionOpen.php');
		$rc = moss_core_sessionOpen($moss);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	} 

	//
	// Check if the user needs to be authenticated for this
	//
	if( !in_array($moss['request']['method'], $no_auth_methods) 
		&& (!isset($moss['session']) || !is_array($moss['session']) 
		|| !is_array($moss['session']['user']) || !isset($moss['session']['user']['id'])
		|| $moss['session']['user']['id'] <= 0)
		) {
		return array('stat'=>'fail', 'err'=>array('code'=>'5', 'msg'=>'Not authenticated'));
	}

	//
	// Check if the method exists, after we check for authentication,
	// because we don't want people to be able to figure out valid
	// function calls by probing.
	//
	if( $method_filename == '' || $method_function == '' || !file_exists($method_filename) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'4', 'msg'=>'Method does not exist'));
	}

	//
	// Include the method function
	//
	require_once($method_filename);

	if( !is_callable($method_function) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'1', 'msg'=>'Method does not exist'));
	}

	$method_rc = $method_function($moss);

	//
	// Save the session if successful transaction
	//
	if( isset($moss['session']['auth_token']) && $moss['session']['auth_token'] != '' ) {
		require_once($moss['config']['core']['modules_dir']. '/core/private/sessionSave.php');
		$rc = moss_core_sessionSave($moss);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// FIXME: Update the action log with the results from the request
	//
	// require_once($moss['config']['core']['modules_dir']. '/core/private/actionLogResult.php');
	// moss_core_actionLogResult($moss, );

	return $method_rc;
}
