<?php
//
// Description
// -----------
// This method retrieves the history elements for a module field.  The users display_name is 
// attached to each record as user_display_name.
//
// Arguments
// ---------
// user_id: 		The user making the request
//
//
function ciniki_core_dbCopyModuleHistory($ciniki, $module, $history_table, $business_id, $table_name, $old_table_key, $new_table_key, $table_field) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
	//
	$rc = ciniki_core_dbConnect($ciniki, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	// Get the history log from ciniki_core_change_logs table.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	$strsql = "INSERT INTO " . ciniki_core_dbQuote($ciniki, $history_table) . " "
		. "(uuid, business_id, user_id, session, action, table_name, table_key, table_field, new_value, log_date) "
		. "SELECT UUID(), business_id, user_id, session, action, table_name, "
		. "'" . ciniki_core_dbQuote($ciniki, $new_table_key) . "'"
		. ", table_field, new_value, log_date "
		. "FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
		. " WHERE business_id ='" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. " AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
		. " AND table_key = '" . ciniki_core_dbQuote($ciniki, $old_table_key) . "' "
		. " AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_field) . "' "
		. "";
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'767', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}
	
	return array('stat'=>'ok');
}
?>
