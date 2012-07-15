<?php
//
// Description
// -----------
// This function will remove any sessions for a user_id.  If a sysadmin
// or other user is removed from the database, this function should
// be called to remove any open sessions for the deleted user.
//
// Arguments
// ---------
// ciniki:
// user_id:			The user to end the session for.
//
function ciniki_core_sessionEndUser($ciniki, $user_id) {

	//
	// Remove the session from the database
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDelete.php');

	$strsql = "DELETE FROM ciniki_core_session_data WHERE user_id = '" . ciniki_core_dbQuote($ciniki, $user_id) . "'";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
