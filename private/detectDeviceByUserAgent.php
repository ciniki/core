<?php
//
// Description
// -----------
// This function will use the USER_AGENT string passed from the
// browser to determine the characteristics of the device.
//
// All USER_AGENT strings should be located in the core_user_agents table.
//
// If a USER_AGENT string is not found in the database, then an alert
// is sent to the system admin, and then it is tested against some 
// regex's to determine what is might be.
//
// Info
// ----
// Status: started
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_agent:		The USER_AGENT string passed from the browser.
//
//
function ciniki_core_detectDeviceByUserAgent($ciniki, $user_agent) {

	//
	// Setup the default device
	// Assume we know nothing about it, and send back the small output and the most basic html
	// no extra stylesheets will be loaded
	//
	$device = array('viewport'=>'small', 'make'=>'', 'model'=>'', 'browser'=>'', 'browser_version'=>'');
	
	//
	// If the USER_AGENT string is empty, return
	//
	if( !is_string($user_agent) || $user_agent == '' ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'41', 'msg'=>'No USER_AGENT string'),
			'device'=>$device);
	}
	
	//
	// Check the USER_AGENT against the database
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$strsql = "SELECT viewport, make, model, browser, browser_version "
		. "FROM core_user_agents "
		. "WHERE user_agent = '" . ciniki_core_dbQuote($ciniki, $user_agent) . "'";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'core', 'device');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// If a row was found in the database, return the device information.
	//
	if( isset($rc['device']) && is_array($rc['device']) ) {
		return $rc;
	}


	//
	// The USER_AGENT string was not found in the database,
	// an alert needs to be send to the system admin, and
	// a guess should be made to try and determine the level of browser
	//
	if( $rc['num_rows'] == 0 ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/alertGenerate.php');
		ciniki_core_alertGenerate($ciniki, 
			array('alert'=>'1', 'msg'=>'USER_AGENT string not found'), $rc);

		if( preg_match('/Mozilla\/[0-9].* Firefox\/[0-9]/', $user_agent) ) {
			$device['viewport'] = 'medium';
			$device['browser'] = 'mozilla';
		}
		elseif( preg_match('/Mozilla\/[0-9].* Version\/[45].* Safari\//', $user_agent) ) {
			$device['viewport'] = 'medium';
			$device['browser'] = 'safari';
		} 
	}



	//
	// When all else fails, return default.  The calling function can 
	// determine if it's important enough to fail, or ignore the error.
	//
	return array('stat'=>'fail', 'device'=>$device, 
		'err'=>array('code'=>'42', 'msg'=>'Unable to identify remote device.'));
}
?>
