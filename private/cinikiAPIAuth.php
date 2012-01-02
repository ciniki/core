<?php
//
// Description
// -----------
// This function will
//
// Arguments
// ---------
// 
//
function ciniki_core_cinikiAPIAuth(&$ciniki, $url, $api_key, $username, $password) {
	
	$api = array(
		'token'=>'',
		'url'=>$url,
		'key'=>$api_key,
		);

	## http://ravenwood.ciniki.ca/ciniki-json.php?method=ciniki.users.auth&api_key=$api_key&format=php", username=$username&password=$password
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'cinikiAPIPost');
	$rc = ciniki_core_cinikiAPIPost($ciniki, $api, 'ciniki.users.auth', null, array('username'=>$username, 'password'=>$password));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$api['token'] = $rc['auth']['token'];
	
	return array('stat'=>'ok', 'api'=>$api);
}
?>
