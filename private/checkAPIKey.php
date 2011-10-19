<?php
//
// Description
// -----------
// This method will check the api_key in the arguments exists
// and is an active key.
//
// Info
// ----
// status:			beta
// 
// Arguments
// ---------
// api_key:			
//
function moss_core_checkAPIKey($moss) {
	//
	// Required functions
	//
	require($moss['config']['core']['root_dir'] . '/moss-modules/core/private/dbQuote.php');
	require($moss['config']['core']['root_dir'] . '/moss-modules/core/private/dbRspQuery.php');

	if( !isset($moss['request']['api_key']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'17', 'msg'=>'Internal Error', 'pmsg'=>"moss_core_checkAPIKey called before moss_core_init."));
	}

	$strsql = "SELECT api_key, status, perms FROM core_api_keys "
		. "WHERE api_key = '" . moss_core_dbQuote($moss, $moss['request']['api_key']) . "' "
		. "AND status = 1 AND (expiry_date = 0 OR UTC_TIMESTAMP() < expiry_date)";

	return moss_core_dbRspQuery($moss, $strsql, 'core', 'api_key', '', array('stat'=>'fail', 'err'=>array('code'=>'7', 'msg'=>'Invalid API Key')));
}
?>
