<?php
//
// Description
// -----------
// This function will add a change log entry for a changed field. This will
// be entered in the core_change_logs table.
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// module:			The module the table_name is in.
// user_id: 		The user making the request
// business_id:
// table_name:		The table name that the data was inserted/replaced in.
// table_key:		The key to be able to get back to the row that was 
//					changed in the table_name.
// table_field:		The field in the table_name that was updated.
// value:			The new value for the field.
//
function moss_core_dbAddChangeLog($moss, $module, $business_id, $table_name, $table_key, $table_field, $value) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
	//
	$rc = moss_core_dbConnect($moss, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	$strsql = "INSERT INTO core_change_logs (user_id, session, transaction, status, "
		. "business_id, table_name, table_key, table_field, new_value, log_date) VALUES ("
		. "'" . moss_core_dbQuote($moss, $moss['session']['user']['id']) . "', "
		. "'" . moss_core_dbQuote($moss, $moss['session']['change_log_id']) . "', "
		. "'', 0, "
		. "'" . moss_core_dbQuote($moss, $business_id) . "', "
		. "'" . moss_core_dbQuote($moss, $table_name) . "', "
		. "'" . moss_core_dbQuote($moss, $table_key) . "', ";
	if( $table_field == 'NOW()' ) {
		$strsql .= "NOW(), ";
	} else {
		$strsql .= "'" . moss_core_dbQuote($moss, $table_field) . "', ";
	}
	$strsql .= "'" . moss_core_dbQuote($moss, $value) . "', UTC_TIMESTAMP())";

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	return moss_core_dbInsert($moss, $strsql, 'core');
}
?>
