<?php
//
// Description
// -----------
// This function will add a request to the api logs.
//
function ciniki_core_logAPIRequest($ciniki) {
	//
	// Log a API request 
	//
	$strsql = "INSERT INTO ciniki_core_api_logs (uuid, user_id, business_id, session_key, method, action, ip_address, "
		. "log_date ) VALUES (uuid(), "
		. "";
	if( isset($ciniki['session']['user']['id']) ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', ";
	} else {
		$strsql .= "0, ";
	}
	if( isset($ciniki['request']['args']['business_id']) ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['business_id']) . "', ";
	} else {
		$strsql .= "0, ";
	}
	if( isset($ciniki['session']['change_log_id']) ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', ";
	} else {
		$strsql .= "'', ";
	}
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['method']) . "', "
		. "";
	if( isset($ciniki['request']['action']) ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['action']) . "', ";
	} else {
		$strsql .= "'', ";
	}
	if( isset($_SERVER['REMOTE_ADDR']) ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $_SERVER['REMOTE_ADDR']) . "', ";
	} else {
		$strsql .= "'localhost', ";
	}
	$strsql .= "UTC_TIMESTAMP())";

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	return ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.core');
}
?>
