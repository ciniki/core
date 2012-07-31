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

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbConnect.php');

	$rc = ciniki_core_dbConnect($ciniki, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return mysql_real_escape_string($str, $rc['dh']);
}
?>
