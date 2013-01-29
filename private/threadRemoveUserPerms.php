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
function ciniki_core_threadRemoveUserPerms(&$ciniki, $module, $object, $business_id, $table, $history_table, $prefix, $id, $user_id, $perms) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

	//
	// If the insert failed, then try to update an existing row
	//
	$strsql = "UPDATE " . ciniki_core_dbQuote($ciniki, $table) . " "
		. "SET perms = (perms &~ '" . ciniki_core_dbQuote($ciniki, $perms) . "'), "
		. "last_updated = UTC_TIMESTAMP() "
		. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = '" . ciniki_core_dbQuote($ciniki, $id) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $user_id) . "'";
		
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the new perms and update history
	//
	if( $rc['num_affected_rows'] > 0 ) {
		$strsql = "SELECT id, uuid, perms "
			. "FROM $table "
			. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = "
				. "'" . ciniki_core_dbQuote($ciniki, $id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $user_id) . "'"
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $module, 'user');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['user']) ) {
			$user = $rc['user'];
			ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
				2, $table, $user['id'], 'perms', $user['perms']);

			$ciniki['syncqueue'][] = array('push'=>$module . '.' . $object, 
				'args'=>array('id'=>$user['id']));
		}
	}

	return array('stat'=>'ok');
}
?>
