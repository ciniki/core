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
function moss_core_sessionInit(&$moss) {

	$moss['session'] = array();

	//
	// Set default session variables
	//
	$moss['session']['api_key'] = '';
	$moss['session']['auth_token'] = '';

	//
	// Create a structure to store the user information
	//
	$moss['session']['user'] = array('id'=>0, 'perms'=>0);

	return array('stat'=>'ok');
}
?>
