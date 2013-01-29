<?php
//
// Description
// -----------
// This function will add a user as a follower to a thread, and thereby be 
// notified of any change/responses to the thread.
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
function ciniki_core_threadAddFollower(&$ciniki, $module, $object, $business_id, $table, $history_table, $prefix, $id, $user_id) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$uuid = $rc['uuid'];

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "INSERT INTO " . ciniki_core_dbQuote($ciniki, $table) . " (uuid, business_id, "
		. "" . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . ", "
		. "user_id, perms, date_added, last_updated"
		. ") VALUES ('" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
		. "";

	// $prefix_id (bug_id, help_id, comment_id, etc...
	if( $id != null && $id > 0 ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $id) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'218', 'msg'=>'Required argument missing', 'pmsg'=>"No {$prefix}_id"));
	}

	// user_id
	if( $user_id != '' && $user_id > 0 ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $user_id) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'219', 'msg'=>'Required argument missing', 'pmsg'=>'No user_id'));
	}

	$strsql .= "0x01, UTC_TIMESTAMP(), UTC_TIMESTAMP())";

	$rc = ciniki_core_dbInsert($ciniki, $strsql, $module);
	//
	// Chech for a duplicate key error, and then run an update
	//
	if( $rc['stat'] != 'ok' && ($rc['err']['dberrno'] == 1062 || $rc['err']['dberrno'] == 1022) ) {
		//
		// If the insert failed, then try to update an existing row
		//
		$strsql = "UPDATE " . ciniki_core_dbQuote($ciniki, $table) . " "
			. "SET perms = (perms | 0x01), last_updated = UTC_TIMESTAMP() "
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
	$user_id = $rc['insert_id'];

	//
	// Add history
	//
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $user_id, 'uuid', $uuid);
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $user_id, $prefix . '_id', $id);
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $user_id, 'user_id', $user_id);
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $user_id, 'perms', 1);

	//
	// Sync push
	//
	$ciniki['syncqueue'][] = array('push'=>$module . '.' . $object, 
		'args'=>array('id'=>$user_id));

	return array('stat'=>'ok');
}
?>
