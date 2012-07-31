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
// ciniki:
// arg: 		The argument passed in the request to the API.
//
function ciniki_core_dbQuoteRequestArg($ciniki, $arg) {

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbConnect.php');

	$rc = ciniki_core_dbConnect($ciniki, 'core');
	if( $rc['stat'] != 'ok' ) {
		return '';
	}

	if( !isset($ciniki['request']) || !isset($ciniki['request']['args']) || !isset($ciniki['request']['args'][$arg]) ) {
		return '';
	}

	return mysql_real_escape_string($ciniki['request']['args'][$arg], $rc['dh']);
}
?>
