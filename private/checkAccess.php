<?php
//
// Description
// -----------
// This function will check if the user has access to a specified module and function.
//
// Info
// ----
// Status: 				beta
//
// Arguments
// ---------
// moss:
// business_id:			The business ID to check the session user against.
//
// Returns
// -------
// <rsp stat='ok' />
//
function moss_core_checkAccess($moss, $business_id, $method) {
	//
	// Methods which don't require authentication
	//
	$noauth_methods = array(
		'moss.core.echoTest',
		);
	if( in_array($method, $noauth_methods) ) {
		return array('stat'=>'ok');
	}

	//
	// Check the user is authenticated
	//
	if( !isset($moss['session'])
		|| !isset($moss['session']['user'])
		|| !isset($moss['session']['user']['id'])
		|| $moss['session']['user']['id'] < 1 ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'98', 'msg'=>'User not authenticated'));
	}

	//
	// Check if the requested method is a public method
	//
	$public_methods = array(
		'moss.core.getAddressCountryCodes',
		'moss.core.parseDatetime',
		'moss.core.parseDate',
		);
	if( in_array($method, $public_methods) ) {
		return array('stat'=>'ok');
	}

	//
	// If the user is a sysadmin, they have access to all functions
	//
	if( ($moss['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok');
	}

	//
	// By default fail
	//
	return array('stat'=>'fail', 'err'=>array('code'=>'100', 'msg'=>'Access denied'));
}
?>
