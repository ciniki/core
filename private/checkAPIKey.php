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
function ciniki_core_checkAPIKey($ciniki) {
	//
	// Required functions
	//
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');

	if( !isset($ciniki['request']['api_key']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'17', 'msg'=>'Internal Error', 'pmsg'=>"ciniki_core_checkAPIKey called before ciniki_core_init."));
	}

	$strsql = "SELECT api_key, status, perms FROM ciniki_core_api_keys "
		. "WHERE api_key = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['api_key']) . "' "
		. "AND status = 1 AND (expiry_date = 0 OR UTC_TIMESTAMP() < expiry_date)";

	return ciniki_core_dbRspQuery($ciniki, $strsql, 'core', 'api_key', '', array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'7', 'msg'=>'Invalid API Key')));
}
?>
