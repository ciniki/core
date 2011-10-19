<?php
//
// Description
// -----------
// This function will save an existing session.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
//
//
function moss_core_sessionSave($moss) {

	if( !isset($moss['session']) || !is_array($moss['session']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'28', 'msg'=>'Internal configuration error', 'pmsg'=>'$moss[session] not set'));
	}

	//
	// Only save sessions which have all three specified
	//
	if( !isset($moss['session']['api_key']) || $moss['session']['api_key'] == '' 
		|| !isset($moss['session']['auth_token']) || $moss['session']['auth_token'] == '' 
		|| !isset($moss['session']['user']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'29', 'msg'=>'Internal configuration error', 'pmsg'=>'Required session variables not set.'));
	}

	//
	// Check if a session is already started based on the auth_token
	// and api_key.
	//
	// A combination of the api_key and auth_token are used, so somebody
	// would have to guess an api_key and auth_token of somebody logged
	// in using that api_key, ie from the same application.  Adds an
	// extra layer of security for session.
	//
	// Don't check for timeout here, we want to be able to have session saved,
	// even if over the timeout, because the session was opened before the timeout.
	// Sessions are only open as long as it takes to run a method.
	// 
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$strsql = "UPDATE core_session_data SET "
		. "session_data = '" . moss_core_dbQuote($moss, serialize($moss['session'])) . "' "
		. ", last_saved = UTC_TIMESTAMP() "
		. "WHERE auth_token = '" . moss_core_dbQuote($moss, $moss['session']['auth_token']) . "' "
		. "AND api_key = '" . moss_core_dbQuote($moss, $moss['session']['api_key']) . "' "
		. "AND user_id = '" . moss_core_dbQuote($moss, $moss['session']['user']['id']) . "' "
		. "";

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = moss_core_dbUpdate($moss, $strsql, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

//	This was removed, because when no columns are changed, the num_affected returns 0.
//	if( $rc['num_affected_rows'] < 1 ) {
//		return array('stat'=>'fail', 'err'=>array('code'=>'36', 'msg'=>'Internal Error', 'pmsg'=>'No session data was updated.'));
//	}

	return array('stat'=>'ok');
}
?>
