<?php
//
// Description
// -----------
// This function will add a followup response to a thread.
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
function moss_core_threadAddFollowup($moss, $module, $table, $prefix, $id, $args) {
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
		. "user_id, content, date_added, last_updated"
		. ") VALUES (";

	// $prefix_id (bug_id, help_id, comment_id, etc...
	if( $id != null && $id > 0 ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $id) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'233', 'msg'=>'Required argument missing', 'pmsg'=>"No {$prefix}_id"));
	}

	// user_id
	if( isset($args['user_id']) && $args['user_id'] != '' && $args['user_id'] > 0 ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['user_id']) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'216', 'msg'=>'Required argument missing', 'pmsg'=>'No user_id'));
	}

	// content
	if( isset($args['content']) && $args['content'] != '' ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['content']) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'217', 'msg'=>'Required argument missing', 'pmsg'=>'No content'));
	}

	$strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";

	$rc = moss_core_dbInsert($moss, $strsql, $module);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'209', 'msg'=>'Unable to add followup', 'err'=>$rc['err']));
	}

	//
	// Update the thread last_updated field
	//
	$strsql = "UPDATE " . moss_core_dbQuote($moss, $table) . " SET last_updated = UTC_TIMESTAMP() "
		. "WHERE " . moss_core_dbQuote($moss, "{$prefix}_id") . " = '" . moss_core_dbQuote($moss, $id) . "'";
	$rc = moss_core_dbUpdate($moss, $strsql, $module);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'208', 'msg'=>'Unable to add followup', 'err'=>$rc['err']));
	}

	return array('stat'=>'ok');
}
?>
