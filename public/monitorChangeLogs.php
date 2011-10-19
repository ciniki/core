<?php
//
// Description
// -----------
// This function will get the history of a field from the core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
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
//	<logs timestamp=''>
//		<log id='' date="2011/02/03 00:03:00" value="Value field set to" user_id="1" display_name="" />
//	</logs>
//
function moss_core_monitorChangeLogs($moss) {
	//
	// Check access restrictions to monitorChangeLogs
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/checkAccess.php');
	$rc = moss_core_checkAccess($moss, 0, 'moss.core.monitorChangeLogs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	require_once($moss['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuoteRequestArg.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');

	$strsql = "SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) as cur";
	$ts = moss_core_dbHashQuery($moss, $strsql, 'core', 'timestamp');
	if( $ts['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'174', 'msg'=>'No timestamp available'));
	}

    //
	// Verify the field was passed, and is valid.
	//
	$last_timestamp = $ts['timestamp']['cur'] - 43200;		// Get anything that is from the last 12 hours by default.
	$last_timestamp = $ts['timestamp']['cur'] - 86400;		// Get anything that is from the last 24 hours by default.
	$req_last_timestamp = $last_timestamp;
	if( isset($moss['request']['args']['last_timestamp']) && $moss['request']['args']['last_timestamp'] != '' ) {
		$req_last_timestamp = moss_core_dbQuoteRequestArg($moss, 'last_timestamp');
	}
	// Force last_timestamp to be no older than 1 week
	if( $req_last_timestamp < ($ts['timestamp']['cur'] - 604800) ) {
		$req_last_timestamp = $ts['timestamp']['cur'] - 604800;
	}

	$session = '';
	if( isset($moss['request']['args']['session']) ) {	
		// FIXME: Use this in the query below
		$session = moss_core_dbQuoteRequestArg($moss, 'session');
	}

	$date_format = moss_users_datetimeFormat($moss);

	// Sort the list ASC by date, so the oldest is at the bottom, and therefore will get insert at the top of the list in MOSSi
	$strsql = "SELECT DATE_FORMAT(log_date, '" . moss_core_dbQuote($moss, $date_format) . "') as log_date"
		. ", CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age "
		. ", UNIX_TIMESTAMP(log_date) as TS"
		. ", core_change_logs.id, user_id, users.display_name, session, table_name, table_key, table_field, new_value "
		. "FROM core_change_logs, users  "
		. "WHERE UNIX_TIMESTAMP(core_change_logs.log_date) > '" . moss_core_dbQuote($moss, $req_last_timestamp) . "' "
		. "AND core_change_logs.user_id = users.id "
		. "ORDER BY TS DESC ";
	$rsp = moss_core_dbRspQuery($moss, $strsql, 'core', 'logs', 'log', array('stat'=>'ok', 'logs'=>array()));
	if( $rsp['stat'] == 'ok' ) {
		$rsp['timestamp'] = $ts['timestamp']['cur'];
	}

	return $rsp;
}
?>
