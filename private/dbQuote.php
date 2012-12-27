<?php
//
// Description
// -----------
// This function will escape a string to be used in an SQL query
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// ciniki:
// str:				The string to escape.
//
function ciniki_core_dbQuote(&$ciniki, $str) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbConnect');

	$rc = ciniki_core_dbConnect($ciniki, 'ciniki.core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return mysqli_real_escape_string($rc['dh'], $str);
}
?>
