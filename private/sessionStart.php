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
function moss_core_sessionStart(&$moss, $username, $password) {

	//
	// End any currently active sessions
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/sessionEnd.php');
	require_once($moss['config']['core']['modules_dir'] . '/users/private/logAuthFailure.php');
	require_once($moss['config']['core']['modules_dir'] . '/users/private/logAuthSuccess.php');
	moss_core_sessionEnd($moss);

	//
	// Verify api_key is specified
	//
	if( !isset($moss['request']['api_key']) || $moss['request']['api_key'] == '' ) {
		moss_users_logAuthFailure($moss, $username, 30);
		return array('stat'=>'fail', 'err'=>array('code'=>'30', 'msg'=>'No api_key specified'));
	}

	//
	// Check username and password were passed to function
	//
	if( $username == '' || $password == '' ) {
		moss_users_logAuthFailure($moss, $username, 31);
		return array('stat'=>'fail', 'err'=>array('code'=>'31', 'msg'=>'Invalid password'));
	}

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');

	//
	// Check the username and password in the database.
	// Make sure only select active users (status = 2)
	//
	$strsql = "SELECT id, email, username, avatar_id, perms, status, timeout, login_attempts "
		. "FROM users "
		. "WHERE (email = '" . moss_core_dbQuote($moss, $username) . "' "
			. "OR username = '" . moss_core_dbQuote($moss, $username) . "') "
		. "AND password = SHA1('" . moss_core_dbQuote($moss, $password) . "') ";

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = moss_core_dbHashQuery($moss, $strsql, 'users', 'user');
	if( $rc['stat'] != 'ok' ) {
		moss_users_logAuthFailure($moss, $username, $rc['err']['code']);
		return $rc;
	}

	//
	// Perform an extra check to make sure only 1 row was found, other return error
	//
	if( $rc['num_rows'] != 1 ) {
		moss_users_logAuthFailure($moss, $username, 33);
		return array('stat'=>'fail', 'err'=>array('code'=>'33', 'msg'=>'Invalid password'));
	}

	if( !isset($rc['user']) ) {
		moss_users_logAuthFailure($moss, $username, 34);
		return array('stat'=>'fail', 'err'=>array('code'=>'34', 'msg'=>'Invalid password'));
	}
	if( $rc['user']['id'] <= 0 ) {
		moss_users_logAuthFailure($moss, $username, 35);
		return array('stat'=>'fail', 'err'=>array('code'=>'35', 'msg'=>'Invalid password'));
	}
	$user = $rc['user'];

	// Check if the account should be locked
	if( $user['login_attempts'] > 7 && $user['status'] < 10 ) {
		$strsql = "UPDATE users SET status = 10 WHERE status = 1 AND id = '" . moss_core_dbQuote($moss, $rc['user']['id']) . "'";
		moss_core_alertGenerate($moss, 
			array('alert'=>'2', 'msg'=>'The account ' . $rc['user']['email'] . ' was locked.'));
		moss_core_dbUpdate($moss, $strsql, 'users');
		$user['status'] = 10;
	}
	// Check if the account is locked
	if( $user['status'] == 10 ) {
		moss_users_logAuthFailure($moss, $username, 236);
		return array('stat'=>'fail', 'err'=>array('code'=>'236', 'msg'=>'Account locked'));
	}
	
	// Check if the account is deleted
	if( $user['status'] == 11 ) {
		moss_users_logAuthFailure($moss, $username, 237);
		return array('stat'=>'fail', 'err'=>array('code'=>'237', 'msg'=>'Invalid password'));
	}

	// Check if the account is active
	if( $user['status'] < 1 || $user['status'] > 2 ) {
		moss_users_logAuthFailure($moss, $username, 238);
		return array('stat'=>'fail', 'err'=>array('code'=>'238', 'msg'=>'Invalid password'));
	}

	unset($user['login_attempts']);

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbDetailsQueryHash.php');
	$rc = moss_core_dbDetailsQueryHash($moss, 'user_details', 'user_id', $user['id'], 'settings', 'users');
	if( $rc['stat'] != 'ok' ) {
		moss_users_logAuthFailure($moss, $username, $rc['err']['code']);
		return $rc;
	}
	if( isset($rc['details']['settings']) && $rc['details']['settings'] != null ) {
		$user['settings'] = $rc['details']['settings'];
	}
	
	//
	// Default session timeout to 60 seconds, unless another is specified
	//
	$session_timeout = 60;
	if( isset($user['timeout']) && $user['timeout'] > 0 ) {
		$session_timeout = $user['timeout'];
	} elseif( isset($moss['config']['core']['session_timeout']) && $moss['config']['core']['session_timeout'] > 0 ) {
		$session_timeout = $moss['config']['core']['session_timeout'];
	}
	
	//
	// Initialize the session variable within the moss data structure
	//
	$moss['session'] = array('init'=>'yes', 'api_key'=>$moss['request']['api_key'], 'user'=>$user);
	
	//
	// Generate a random 32 character string as the session id.
	// FIXME: Check to make sure this is a secure enough method for generating a session id.
	// 
	$moss['session']['auth_token'] = md5(date('Y-m-d-H-i-s') . rand());
	$moss['session']['change_log_id'] = date('ymd.His');

	//
	// Serialize the data for storage
	//
	$serialized_session_data = serialize($moss['session']);

	$strsql = "INSERT INTO core_session_data "
		. "(auth_token, api_key, user_id, date_added, timeout, last_saved, session_data) "
		. " VALUES "
		. "('" . moss_core_dbQuote($moss, $moss['session']['auth_token']) . "' "
		. ", '" . moss_core_dbQuote($moss, $moss['session']['api_key']) . "' "
		. ", '" . moss_core_dbQuote($moss, $user['id']) . "' "
		. ", UTC_TIMESTAMP(), " . moss_core_dbQuote($moss, $session_timeout)
		. ", UTC_TIMESTAMP(), '" . moss_core_dbQuote($moss, $serialized_session_data) . "')";

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	$rc = moss_core_dbInsert($moss, $strsql, 'core');
	if( $rc['stat'] != 'ok' ) {
		moss_users_logAuthFailure($moss, $username, $rc['err']['code']);
		return $rc;
	}

	//
	// Update the last_login field for the user, and reset the login_attempts field.
	//
	$strsql = "UPDATE users SET login_attempts = 0, last_login = UTC_TIMESTAMP() WHERE id = '" . moss_core_dbQuote($moss, $user['id']) . "'";
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = moss_core_dbUpdate($moss, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		moss_users_logAuthFailure($moss, $username, $rc['err']['code']);
		return $rc;
	}

	//
	// FIXME: Check for primary key violation, and choose new key
	//
	
	moss_users_logAuthSuccess($moss);

	return array('stat'=>'ok', 'auth'=>array('token'=>$moss['session']['auth_token']), 'id'=>$user['id'], 'perms'=>$user['perms'], 'avatar_id'=>$user['avatar_id']);
}
?>
