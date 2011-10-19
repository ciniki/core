<?php
//
// Description
// -----------
// This function will initialize the session variable, but will not
// open an existing or start a new session.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
//
//
function ciniki_core_sessionInit(&$ciniki) {

	$ciniki['session'] = array();

	//
	// Set default session variables
	//
	$ciniki['session']['api_key'] = '';
	$ciniki['session']['auth_token'] = '';

	//
	// Create a structure to store the user information
	//
	$ciniki['session']['user'] = array('id'=>0, 'perms'=>0);

	return array('stat'=>'ok');
}
?>
