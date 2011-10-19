<?php
//
// Description
// -----------
// This function will remove any sessions for a user_id.  If a sysadmin
// or other user is removed from the database, this function should
// be called to remove any open sessions for the deleted user.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
//
//
function moss_core_sessionEndUser($moss, $user_id) {

	//
	// Remove the session from the database
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbDelete.php');

	$strsql = "DELETE FROM core_session_data WHERE user_id = '" . moss_core_dbQuote($moss, $user_id) . "'";
	$rc = moss_core_dbDelete($moss, $strsql, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
