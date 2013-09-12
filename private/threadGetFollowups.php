<?php
//
// Description
// -----------
// This function will retrieve the list of thread subjects from the
// database.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// module:				The package.module the thread is located in.
//
// Returns
// -------
// <followups>
// 	<followup id="1" subject="The thread subject"
// </followups>
//
function ciniki_core_threadGetFollowups($ciniki, $module, $table, $prefix, $id, $args) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQueryPlusDisplayNames');

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	// Validate table and prefix
	if( preg_match('/[^_a-z]/', $table) ) {
//		|| preg_match('/[^_a_z]/', $prefix) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1336', 'msg'=>'Invalid database parameters' . $table . $prefix));
	}

	// 
	// Setup the SQL statement to insert the new thread
	//
	$datetime_format = ciniki_users_datetimeFormat($ciniki);
	$strsql = "SELECT {$table}.id, " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . ", "
		. "{$table}.user_id, "
		. "DATE_FORMAT(CONVERT_TZ({$table}.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP({$table}.date_added) as DECIMAL(12,0)) as age, "
		. "{$table}.content, "
		. "ciniki_users.display_name AS user_display_name "
		. "FROM {$table} "
		. "LEFT JOIN ciniki_users ON (" . ciniki_core_dbQuote($ciniki, $table) . ".user_id = ciniki_users.id ) "
		. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = '" . ciniki_core_dbQuote($ciniki, $id) . "' "
		. "ORDER BY " . ciniki_core_dbQuote($ciniki, $table) . ".date_added ASC "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');	
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, $module, array(
		array('container'=>'followups', 'fname'=>'id', 'name'=>'followup',
			'fields'=>array('id', 'user_id', 'date_added', 'age', 'content', 'user_display_name')),
		));
	return $rc;	
//	return ciniki_core_dbRspQueryPlusDisplayNames($ciniki, $strsql, $module, 'followups', 'followup', array('stat'=>'ok', 'followups'=>array()));
}
?>
