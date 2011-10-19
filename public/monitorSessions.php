<?php
//
// Description
// -----------
// This function will return the active sessions from the core_session_data table.
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
function moss_core_monitorSessions($moss) {
	//
	// Check access restrictions to monitorChangeLogs
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/checkAccess.php');
	$rc = moss_core_checkAccess($moss, 0, 'moss.core.monitorSessions');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	require_once($moss['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbRspQueryPlusUsers.php');

	$date_format = moss_users_datetimeFormat($moss);

	//
	// Sort the list ASC by date, so the oldest is at the bottom, and therefore will get insert at the top of the list in MOSSi
	//
	$strsql = "SELECT core_session_data.api_key, core_api_keys.appname, core_session_data.user_id,  "
		. "DATE_FORMAT(core_session_data.date_added, '" . moss_core_dbQuote($moss, $date_format) . "') as date_added, "
		. "DATE_FORMAT(core_session_data.last_saved, '" . moss_core_dbQuote($moss, $date_format) . "') as last_saved, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(core_session_data.date_added) as DECIMAL(12,0)) as age "
		. "FROM core_session_data "
		. "LEFT JOIN core_api_keys ON (core_session_data.api_key = core_api_keys.api_key) ";
	$rsp = moss_core_dbRspQueryPlusUsers($moss, $strsql, 'core', 'sessions', 'session', array('stat'=>'ok', 'sessions'=>array()));
	return $rsp;
}
?>
