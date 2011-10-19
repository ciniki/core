<?php
//
// Description
// -----------
// This function will escape one of the arguments passed to the API.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// arg: 		The user making the request
// 
//
//
function moss_core_dbQuoteRequestArg($moss, $arg) {

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbConnect.php');

	$rc = moss_core_dbConnect($moss, 'core');
	if( $rc['stat'] != 'ok' ) {
		return '';
	}

	if( !isset($moss['request']) || !isset($moss['request']['args']) || !isset($moss['request']['args'][$arg]) ) {
		return '';
	}

	return mysql_real_escape_string($moss['request']['args'][$arg], $rc['dh']);
}
?>
