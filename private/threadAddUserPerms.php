<?php
//
// Description
// -----------
// This function will add a user to a thread and set permissions for that user.  The permissions
// are OR'd together with existing permissions.  To remove a permission, use threadRemoveUser.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// user_id:				The user who submitted the followup.
// content:				The content of the followup.
// 
// Returns
// -------
//
function ciniki_core_threadAddUserPerms($ciniki, $module, $table, $prefix, $id, $user_id, $perms) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "INSERT INTO " . ciniki_core_dbQuote($ciniki, $table) . " (" . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . ", "
		. "user_id, perms, date_added, last_updated"
		. ") VALUES (";

	// $prefix_id (bug_id, help_id, comment_id, etc...
	if( $id != null && $id > 0 ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $id) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'221', 'msg'=>'Required argument missing', 'pmsg'=>"No {$prefix}_id"));
	}

	// user_id
	if( $user_id != '' && $user_id > 0 ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $user_id) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'220', 'msg'=>'Required argument missing', 'pmsg'=>'No user_id'));
	}

	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $perms) . "', UTC_TIMESTAMP(), UTC_TIMESTAMP())";

	$rc = ciniki_core_dbInsert($ciniki, $strsql, $module);
	//
	// Chech for a duplicate key error, and then run an update
	//
	if( $rc['stat'] != 'ok' && ($rc['err']['dberrno'] == 1062 || $rc['err']['dberrno'] == 1022) ) {
		//
		// If the insert failed, then try to update an existing row
		//
		$strsql = "UPDATE " . ciniki_core_dbQuote($ciniki, $table) . " SET perms = (perms | '" . ciniki_core_dbQuote($ciniki, $perms) . "') "
			. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = '" . ciniki_core_dbQuote($ciniki, $id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $user_id) . "'";
		error_log($strsql);
		return ciniki_core_dbUpdate($ciniki, $strsql, $module);
	}

	return $rc;
}
?>
