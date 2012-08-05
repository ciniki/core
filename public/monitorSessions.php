<?php
//
// Description
// -----------
// This function will return the active sessions from the ciniki_core_session_data table.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// last_timestamp:  		The last timestamp from the previous request.
//
// Returns
// -------
//	<sessions>
//		<session id='' date="2011/02/03 00:03:00" value="Value field set to" user_id="1" display_name="" />
//	</sessions>
//
function ciniki_core_monitorSessions($ciniki) {
	//
	// Check access restrictions to monitorChangeLogs
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/checkAccess.php');
	$rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.monitorSessions');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');

	$date_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Sort the list ASC by date, so the oldest is at the bottom, and therefore will get insert at the top of the list in ciniki-manage
	//
	$strsql = "SELECT ciniki_users.display_name, "
		. "ciniki_core_session_data.api_key, ciniki_core_api_keys.appname, ciniki_core_session_data.user_id,  "
		. "DATE_FORMAT(ciniki_core_session_data.date_added, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as date_added, "
		. "DATE_FORMAT(ciniki_core_session_data.last_saved, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as last_saved, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_core_session_data.date_added) as DECIMAL(12,0)) as age "
		. "FROM ciniki_core_session_data "
		. "LEFT JOIN ciniki_core_api_keys ON (ciniki_core_session_data.api_key = ciniki_core_api_keys.api_key) "
		. "LEFT JOIN ciniki_users ON (ciniki_core_session_data.user_id = ciniki_users.id) "
		. "ORDER BY age "
		. "";
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.core', 'sessions', 'session', array('stat'=>'ok', 'sessions'=>array()));
}
?>
