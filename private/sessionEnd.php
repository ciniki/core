<?php
//
// Description
// -----------
// This function will start a new session, destroying the old
// one if it exists.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
//
//
function moss_core_sessionEnd($moss) {

	//
	// Remove the session from the database
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbDelete.php');

	if( isset($moss['session']['auth_token']) && $moss['session']['auth_token'] != '' ) {
		$strsql = "DELETE FROM core_session_data "
			. "WHERE auth_token = '" . moss_core_dbQuote($moss, $moss['session']['auth_token']) . "' ";
		$rc = moss_core_dbDelete($moss, $strsql, 'core');
		if( $rc['stat'] == 'ok' && $rc['num_affected_rows'] == 1 ) {
			// FIXME: Add code to track number of active sessions in users table, limit to X sessions.
		}
	}

	elseif( isset($moss['request']['auth_token']) && $moss['request']['auth_token'] != '' ) {
		$strsql = "DELETE FROM core_session_data "
			. "WHERE auth_token = '" . moss_core_dbQuote($moss, $moss['request']['auth_token']) . "' ";
		$rc = moss_core_dbDelete($moss, $strsql, 'core');
		if( $rc['stat'] == 'ok' && $rc['num_affected_rows'] == 1 ) {
			// FIXME: Add code to track number of active sessions in users table, limit to X sessions.
		}
	}

	//
	// Take the opportunity to clear old sessions, don't care about return code
	// FIXME: This maybe should be moved to a cronjob
	//
	$strsql = "DELETE FROM core_session_data WHERE UTC_TIMESTAMP()-TIMESTAMP(last_saved) > timeout";
	moss_core_dbDelete($moss, $strsql, 'core');

	return array('stat'=>'ok');
}
?>
