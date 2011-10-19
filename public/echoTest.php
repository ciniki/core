<?php
//
// Description
// -----------
// This method echos back the arguments sent.  This function is
// for simple testing, similar to ping in network tests.
//
// Info
// ----
// status:			beta
// 
// Arguments
// ---------
// api_key:			
// *args:			Any additional arguments passed to the method will be returned in the response.
//
// Returns
// -------
// <request api_key='0123456789abcdef0123456789abcdef' auth_token='' method='moss.core.echoTest'>
// 	<args args1="test" />
// </reqeust>
//
function moss_core_echoTest($moss) {
	//
	// Check access restrictions to checkAPIKey
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/checkAccess.php');
	$rc = moss_core_checkAccess($moss, 0, 'moss.core.echoTest');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'request'=>$moss['request']);
}
?>
