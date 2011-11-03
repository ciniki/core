<?php
//
// Description
// -----------
// This function will look for a user_agent string in the database.  If one is found,
// it will be returned, otherwise nothing.
//
// Info
// ----
// Status: 				alpha
//
// Arguments
// ---------
// ciniki:				
// user_agent:			The user_agent string to search for.
// 
function ciniki_core_userAgentAdd($ciniki, $device) {
	//
	// Check device is setup properly
	//
	if( !isset($device['user_agent']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'127', 'msg'=>'Invalid device specification'));
	}
	if( !isset($device) || !isset($device['user_agent']) 
		|| !isset($device['type_status'])
		|| !isset($device['size'])
		|| !isset($device['engine'])
		|| !isset($device['device']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'126', 'msg'=>'Invalid device specification'));
	}

	if( !isset($device['flags']) ) {
		$device['flags'] = 0;
	}

	//
	// Create SQL string to insert the user_agent
	//
	$strsql = "INSERT INTO core_user_agents (user_agent, type_status, size, flags, "
		. "engine, engine_version, "
		. "os, os_version, "
		. "browser, browser_version, "
		. "device, device_version, device_manufacturer, "
		. "date_added, last_updated) VALUES ( "
		. "'" . ciniki_core_dbQuote($ciniki, $device['user_agent']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $device['type_status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $device['size']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $device['flags']) . "', ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	foreach(array('engine', 'engine_version', 'os', 'os_version', 
		'browser', 'browser_version', 'device', 'device_version', 'device_manufacturer') 
		as $field) {
		if( isset($device[$field]) && $device[$field] != '' ) {
			$strsql .= "'" . ciniki_core_dbQuote($ciniki, $device[$field]) . "',";
		} else {
			$strsql .= "'',";
		}
	}
	$strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	return ciniki_core_dbInsert($ciniki, $strsql, 'core');
}
?>
