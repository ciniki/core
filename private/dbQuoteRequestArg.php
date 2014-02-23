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
function ciniki_core_dbQuoteRequestArg(&$ciniki, $arg) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbConnect');

	$rc = ciniki_core_dbConnect($ciniki, 'ciniki.core');
	if( $rc['stat'] != 'ok' ) {
		return '';
	}

	if( !isset($ciniki['request']) || !isset($ciniki['request']['args']) || !isset($ciniki['request']['args'][$arg]) ) {
		return '';
	}

	return mysqli_real_escape_string($rc['dh'], $ciniki['request']['args'][$arg]);
}
?>
