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
// user_id: 		The user making the request
//
function moss_core_dbQuote(&$moss, $str) {

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbConnect.php');

	$rc = moss_core_dbConnect($moss, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return mysql_real_escape_string($str, $rc['dh']);
}
?>
