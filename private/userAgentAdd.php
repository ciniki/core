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
// moss:				
// user_agent:			The user_agent string to search for.
// 
function moss_core_userAgentAdd($moss, $device) {
	//
	// Check device is setup properly
	//
	if( !isset($device['user_agent']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'127', 'msg'=>'Invalid device specification'));
	}
	if( !isset($device) || !isset($device['user_agent']) 
		|| !isset($device['type_status'])
		|| !isset($device['size'])
		|| !isset($device['engine'])
		|| !isset($device['device']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'126', 'msg'=>'Invalid device specification'));
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
		. "'" . moss_core_dbQuote($moss, $device['user_agent']) . "', "
		. "'" . moss_core_dbQuote($moss, $device['type_status']) . "', "
		. "'" . moss_core_dbQuote($moss, $device['size']) . "', "
		. "'" . moss_core_dbQuote($moss, $device['flags']) . "', ";
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	foreach(array('engine', 'engine_version', 'os', 'os_version', 
		'browser', 'browser_version', 'device', 'device_version', 'device_manufacturer') 
		as $field) {
		if( isset($device[$field]) && $device[$field] != '' ) {
			$strsql .= "'" . moss_core_dbQuote($moss, $device[$field]) . "',";
		} else {
			$strsql .= "'',";
		}
	}
	$strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	return moss_core_dbInsert($moss, $strsql, 'core');
}
?>
