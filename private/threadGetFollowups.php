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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQueryPlusDisplayNames.php');

	// 
	// Setup the SQL statement to insert the new thread
	//
	$date_format = ciniki_users_datetimeFormat($ciniki);
	$strsql = "SELECT id, " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . ", "
		. "user_id, "
		. "DATE_FORMAT(date_added, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as date_added, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(date_added) as DECIMAL(12,0)) as age, content "
		. "FROM " . ciniki_core_dbQuote($ciniki, $table) . " "
		. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = '" . ciniki_core_dbQuote($ciniki, $id) . "' "
		. "ORDER BY " . ciniki_core_dbQuote($ciniki, $table) . ".date_added ASC "
		. "";
	
	return ciniki_core_dbRspQueryPlusDisplayNames($ciniki, $strsql, $module, 'followups', 'followup', array('stat'=>'ok', 'followups'=>array()));
}
?>
