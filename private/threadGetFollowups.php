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
function moss_core_threadGetFollowups($moss, $module, $table, $prefix, $id, $args) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbRspQueryPlusUsers.php');

	// 
	// Setup the SQL statement to insert the new thread
	//
	$date_format = moss_users_datetimeFormat($moss);
	$strsql = "SELECT id, " . moss_core_dbQuote($moss, "{$prefix}_id") . ", "
		. "user_id, "
		. "DATE_FORMAT(date_added, '" . moss_core_dbQuote($moss, $date_format) . "') as date_added, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(date_added) as DECIMAL(12,0)) as age, content "
		. "FROM " . moss_core_dbQuote($moss, $table) . " "
		. "WHERE " . moss_core_dbQuote($moss, "{$prefix}_id") . " = '" . moss_core_dbQuote($moss, $id) . "' ";
	
	return moss_core_dbRspQueryPlusUsers($moss, $strsql, $module, 'followups', 'followup', array('stat'=>'ok', 'followups'=>array()));
}
?>
