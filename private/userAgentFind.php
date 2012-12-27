<?php
//
// Description
// -----------
// This function will lookup the details of a device based on the
// User Agent string passed from the browser.
//
// Arguments
// ---------
// ciniki:
// user_agent:			The user_agent string the browser submitted to lookup.
// 
// Returns
// -------
//
function ciniki_core_userAgentFind($ciniki, $user_agent) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

	$strsql = "SELECT type_status, size, flags, "
		. "engine, engine_version, os, os_version, "
		. "browser, browser_version, device, device_version, device_manufacturer "
		. "FROM ciniki_core_user_agents "
		. "WHERE user_agent = '" . ciniki_core_dbQuote($ciniki, $user_agent) . "' "
		. "";
	
	return ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'device');
}
?>
