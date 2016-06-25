<?php
//
// Description
// -----------
// This function will start a new session, destroying the old
// one if it exists.
//
// Arguments
// ---------
// ciniki:
// username:        The username to authenticate with the password.
// password:        The password submitted to be used for authentication.
//
function ciniki_core_sessionStart(&$ciniki, $username, $password) {

    //
    // End any currently active sessions
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sessionEnd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'logAuthFailure');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'logAuthSuccess');
    ciniki_core_sessionEnd($ciniki);

    //
    // Verify api_key is specified
    //
    if( !isset($ciniki['request']['api_key']) || $ciniki['request']['api_key'] == '' ) {
        ciniki_users_logAuthFailure($ciniki, $username, 30);
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'30', 'msg'=>'No api_key specified'));
    }

    //
    // Check username and password were passed to function
    //
    if( $username == '' || $password == '' ) {
        ciniki_users_logAuthFailure($ciniki, $username, 31);
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'31', 'msg'=>'Invalid password'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    //
    // Check the username and password in the database.
    // Make sure only select active users (status = 2)
    //
    $strsql = "SELECT id, email, username, avatar_id, perms, status, timeout, login_attempts, display_name "
        . "FROM ciniki_users "
        . "WHERE (email = '" . ciniki_core_dbQuote($ciniki, $username) . "' "
            . "OR username = '" . ciniki_core_dbQuote($ciniki, $username) . "') "
        . "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') ";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' ) {
        ciniki_users_logAuthFailure($ciniki, $username, $rc['err']['code']);
        return $rc;
    }

    //
    // Perform an extra check to make sure only 1 row was found, other return error
    //
    if( $rc['num_rows'] != 1 ) {
        ciniki_users_logAuthFailure($ciniki, $username, 33);
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'33', 'msg'=>'Invalid password'));
    }

    if( !isset($rc['user']) ) {
        ciniki_users_logAuthFailure($ciniki, $username, 34);
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'34', 'msg'=>'Invalid password'));
    }
    if( $rc['user']['id'] <= 0 ) {
        ciniki_users_logAuthFailure($ciniki, $username, 35);
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'35', 'msg'=>'Invalid password'));
    }
    $user = $rc['user'];

    // Check if the account should be locked
    if( $user['login_attempts'] > 7 && $user['status'] < 10 ) {
        $strsql = "UPDATE ciniki_users SET status = 10 WHERE status = 1 AND id = '" . ciniki_core_dbQuote($ciniki, $rc['user']['id']) . "'";
        ciniki_core_alertGenerate($ciniki, 
            array('alert'=>'2', 'msg'=>'The account ' . $rc['user']['email'] . ' was locked.'));
        ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
        $user['status'] = 10;
    }
    // Check if the account is locked
    if( $user['status'] == 10 ) {
        ciniki_users_logAuthFailure($ciniki, $username, 236);
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'236', 'msg'=>'Account locked'));
    }
    
    // Check if the account is deleted
    if( $user['status'] == 11 ) {
        ciniki_users_logAuthFailure($ciniki, $username, 237);
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'237', 'msg'=>'Invalid password'));
    }

    // Check if the account is active
    if( $user['status'] < 1 || $user['status'] > 2 ) {
        ciniki_users_logAuthFailure($ciniki, $username, 238);
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'238', 'msg'=>'Invalid password'));
    }

    unset($user['login_attempts']);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryHash');
    $rc = ciniki_core_dbDetailsQueryHash($ciniki, 'ciniki_user_details', 'user_id', $user['id'], 'settings', 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        ciniki_users_logAuthFailure($ciniki, $username, $rc['err']['code']);
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
    } elseif( isset($ciniki['config']['core']['session_timeout']) && $ciniki['config']['core']['session_timeout'] > 0 ) {
        $session_timeout = $ciniki['config']['core']['session_timeout'];
    }
    
    //
    // Initialize the session variable within the ciniki data structure
    //
    $ciniki['session'] = array('init'=>'yes', 'api_key'=>$ciniki['request']['api_key'], 'user'=>$user);
    
    //
    // Generate a random 32 character string as the session id.
    // FIXME: Check to make sure this is a secure enough method for generating a session id.
    // 
    date_default_timezone_set('UTC');
    $ciniki['session']['auth_token'] = md5(date('Y-m-d-H-i-s') . rand());
  
    $ciniki['session']['change_log_id'] = date('ymd.His') . '.' . substr($ciniki['session']['auth_token'], 0, 6);

    //
    // Serialize the data for storage
    //
    $serialized_session_data = serialize($ciniki['session']);

    $strsql = "INSERT INTO ciniki_core_session_data "
        . "(auth_token, api_key, user_id, date_added, timeout, last_saved, session_key, session_data) "
        . " VALUES "
        . "('" . ciniki_core_dbQuote($ciniki, $ciniki['session']['auth_token']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['api_key']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
        . ", UTC_TIMESTAMP(), " . ciniki_core_dbQuote($ciniki, $session_timeout)
        . ", UTC_TIMESTAMP(), "
        . "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $serialized_session_data) . "')";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.core');
    if( $rc['stat'] != 'ok' ) {
        ciniki_users_logAuthFailure($ciniki, $username, $rc['err']['code']);
        return $rc;
    }

    //
    // Update the last_login field for the user, and reset the login_attempts field.
    //
    $strsql = "UPDATE ciniki_users SET login_attempts = 0, last_login = UTC_TIMESTAMP() WHERE id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "'";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        ciniki_users_logAuthFailure($ciniki, $username, $rc['err']['code']);
        return $rc;
    }

    //
    // FIXME: Check for primary key violation, and choose new key
    //
    
    ciniki_users_logAuthSuccess($ciniki);

    $version_file = $ciniki['config']['ciniki.core']['root_dir'] . "/_versions.ini";
    if( is_file($version_file) ) {
        $version_info = parse_ini_file($version_file, true);
        $version = $version_info['package']['version'];
    } else {
        $version = '';
    }

    return array('stat'=>'ok', 'version'=>$version, 'auth'=>array('token'=>$ciniki['session']['auth_token'], 'id'=>$user['id'], 'perms'=>$user['perms'], 'avatar_id'=>$user['avatar_id']));
}
?>
