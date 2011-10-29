<?php
//
// Description
// -----------
// This function will notify users attached to a thread there has been an update.
//
// FIXME: Add throttling, so if there's lots of updates, don't email each one, wait and group
//
// FIXME: This thread should queue all the emails, so the API call can return success.  
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
//
function ciniki_core_threadNotifyUsers($ciniki, $module, $table, $prefix, $id, $perms, $subject, $msg) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	//
	// Don't worry about autocommit here, it's taken care of in the calling function
	//

	// Required functions
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQueryList.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/emailUser.php');

	
	$strsql = "SELECT user_id FROM " . ciniki_core_dbQuote($ciniki, $table) . " "
		. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = '" . ciniki_core_dbQuote($ciniki, $id) . "' ";
	//
	// If specified, only select users which match the permissions
	//
	if( $perms > 0 ) {
		$strsql .= "AND perms& " . ciniki_core_dbQuote($ciniki, $perms) . " > 0 ";
	}

	$rc = ciniki_core_dbQueryList($ciniki, $strsql, $module, 'user_ids', 'user_id');
	if( $rc['stat'] != 'ok' || !isset($rc['user_ids']) || !is_array($rc['user_ids']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'997', 'msg'=>'Unable to find users', 'err'=>$rc['err']));
	}

	//
	// No users to email, just return
	//
	if( count($rc['user_ids']) <= 0 ) {
		return array('stat'=>'ok', 'users'=>'0', 'emailed'=>'0');
	}

	//
	// Email each user
	//
	foreach($rc['user_ids'] as $user_id) {
		$rc = ciniki_users_emailUser($ciniki, $user_id, $subject, $msg);
	}

	return array('stat'=>'ok');

}
?>
