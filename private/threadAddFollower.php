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
// user_id:				The user who submitted the followup.
// content:				The content of the followup.
// 
// Returns
// -------
//
function moss_core_threadAddFollower($moss, $module, $table, $prefix, $id, $args) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "INSERT INTO " . moss_core_dbQuote($moss, $table) . " (" . moss_core_dbQuote($moss, "{$prefix}_id") . ", "
		. "user_id, perms, date_added, last_updated"
		. ") VALUES (";

	// $prefix_id (bug_id, help_id, comment_id, etc...
	if( $id != null && $id > 0 ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $id) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'218', 'msg'=>'Required argument missing', 'pmsg'=>"No {$prefix}_id"));
	}

	// user_id
	if( isset($args['user_id']) && $args['user_id'] != '' && $args['user_id'] > 0 ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['user_id']) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'219', 'msg'=>'Required argument missing', 'pmsg'=>'No user_id'));
	}

	$strsql .= "0x01, UTC_TIMESTAMP(), UTC_TIMESTAMP())";

	$rc = moss_core_dbInsert($moss, $strsql, $module);
	//
	// Chech for a duplicate key error, and then run an update
	//
	if( $rc['stat'] != 'ok' && ($rc['err']['dberrno'] == 1062 || $rc['err']['dberrno'] == 1022) ) {
		//
		// If the insert failed, then try to update an existing row
		//
		$strsql = "UPDATE " . moss_core_dbQuote($moss, $table) . " SET perms = (perms | 0x01) "
			. "WHERE " . moss_core_dbQuote($moss, "{$prefix}_id") . " = '" . moss_core_dbQuote($moss, $id) . "' "
			. "AND user_id = '" . moss_core_dbQuote($moss, $moss['session']['user']['id']) . "'";
		return moss_core_dbUpdate($moss, $strsql, $module);
	}

	return $rc;
}
?>
