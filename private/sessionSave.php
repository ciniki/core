<?php
//
// Description
// -----------
// This function will save an existing session.
//
// Arguments
// ---------
// ciniki:
//
function ciniki_core_sessionSave($ciniki) {

	if( !isset($ciniki['session']) || !is_array($ciniki['session']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'28', 'msg'=>'Internal configuration error', 'pmsg'=>'$ciniki[session] not set'));
	}

	//
	// Only save sessions which have all three specified
	//
	if( !isset($ciniki['session']['api_key']) || $ciniki['session']['api_key'] == '' 
		|| !isset($ciniki['session']['auth_token']) || $ciniki['session']['auth_token'] == '' 
		|| !isset($ciniki['session']['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'29', 'msg'=>'Internal configuration error', 'pmsg'=>'Required session variables not set.'));
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$strsql = "UPDATE ciniki_core_session_data SET "
		. "session_data = '" . ciniki_core_dbQuote($ciniki, serialize($ciniki['session'])) . "' "
		. ", last_saved = UTC_TIMESTAMP() "
		. "WHERE auth_token = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['auth_token']) . "' "
		. "AND api_key = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['api_key']) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "";

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

//	This was removed, because when no columns are changed, the num_affected returns 0.
//	if( $rc['num_affected_rows'] < 1 ) {
//		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'36', 'msg'=>'Internal Error', 'pmsg'=>'No session data was updated.'));
//	}

	return array('stat'=>'ok');
}
?>
