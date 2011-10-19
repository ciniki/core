<?php
//
// Description
// -----------
// This function will initialize a WWW connection, and
// setup the WWW user.  The function will get the WWW user from the
// database and setup a fake session.  We don't need a real
// session until the customer logs into the website.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
//
function moss_core_initWWW($moss_root, $output_format) {

	//
	// FIXME: add support for API on remote machine
	//        - check config infomation for
	//

	//
	// Initialize the core of MOSS
	//
	require($moss_root . '/moss-modules/core/private/init.php');
	$rc = moss_core_init($moss_root, $output_format);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$moss = $rc['moss'];

	//
	// Select the www user from the database
	//
	$strsql = "SELECT id, email, username, perms "
		. "FROM users "
		. "WHERE username = 'www' ";

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
		return array('stat'=>'fail', 'err'=>array('code'=>'296', 'msg'=>'No www user'));
	}

	if( !isset($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'297', 'msg'=>'No www user'));
	}
	if( $rc['user']['id'] <= 0 ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'298', 'msg'=>'No www user'));
	}
	$moss['session'] = array('api_key'=>$moss['request']['api_key'], 'user'=>$rc['user']);

	return array('stat'=>'ok', 'moss'=>$moss);
}
?>
