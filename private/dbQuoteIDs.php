<?php
//
// Description
// -----------
// This function will create a comma delimited list of integers
// for use in WHERE variable IN (list) sql statements.  This ensures
// that everything is safe and properly escaped.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// user_id: 		The user making the request
//
function moss_core_dbQuoteIDs($moss, $arr) {

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbConnect.php');

	$rc = moss_core_dbConnect($moss, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$str = '';
	$comma = '';
	foreach($arr as $i) {
		if( is_int($i) ) {
			$str .= $comma . mysql_real_escape_string($i);
			$comma = ',';
		}
	}

	return $str;
}
?>
