<?php
//
// Description
// -----------
// This function will remove the specified permissions for the user attached to a thread.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// module:				The package.module the thread is located in.
// user_id:				The user who submitted the followup.
// content:				The content of the followup.
// 
// Returns
// -------
//
function ciniki_core_threadRemoveUserPerms($ciniki, $module, $table, $prefix, $id, $user_id, $perms) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');

	//
	// If the insert failed, then try to update an existing row
	//
	$strsql = "UPDATE " . ciniki_core_dbQuote($ciniki, $table) . " SET perms = (perms &~ '" . ciniki_core_dbQuote($ciniki, $perms) . "') "
		. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = '" . ciniki_core_dbQuote($ciniki, $id) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $user_id) . "'";
		
	return ciniki_core_dbUpdate($ciniki, $strsql, $module);
}
?>
