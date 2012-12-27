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

	// 
	// Setup the SQL statement to insert the new thread
	//
	$datetime_format = ciniki_users_datetimeFormat($ciniki);
	$strsql = "SELECT id, " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . ", "
		. "user_id, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(date_added) as DECIMAL(12,0)) as age, content "
		. "FROM " . ciniki_core_dbQuote($ciniki, $table) . " "
		. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = '" . ciniki_core_dbQuote($ciniki, $id) . "' "
		. "ORDER BY " . ciniki_core_dbQuote($ciniki, $table) . ".date_added ASC "
		. "";
	
	return ciniki_core_dbRspQueryPlusDisplayNames($ciniki, $strsql, $module, 'followups', 'followup', array('stat'=>'ok', 'followups'=>array()));
}
?>
