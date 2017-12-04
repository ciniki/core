<?php
//
// Description
// -----------
// This function will return the active sessions from the ciniki_core_session_data table.
//
// Info
// ----
// Status:      beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// last_timestamp:          The last timestamp from the previous request.
//
// Returns
// -------
//  <sessions>
//      <session id='' date="2011/02/03 00:03:00" value="Value field set to" user_id="1" display_name="" />
//  </sessions>
//
function ciniki_core_bigboard($ciniki) {
    //
    // Check access restrictions to monitorChangeLogs
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.bigboard');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');

    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    $date_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Sort the list ASC by date, so the oldest is at the bottom, and therefore will get insert at the top of the list in ciniki-manage
    //
    $strsql = "SELECT ciniki_users.display_name, "
        . "ciniki_core_session_data.api_key, ciniki_core_api_keys.appname, ciniki_core_session_data.user_id,  "
//      . "DATE_FORMAT(ciniki_core_session_data.date_added, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as date_added, "
//      . "DATE_FORMAT(ciniki_core_session_data.last_saved, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as last_saved, "
        . "DATE_FORMAT(CONVERT_TZ(ciniki_core_session_data.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_added, "
        . "DATE_FORMAT(CONVERT_TZ(ciniki_core_session_data.last_saved, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_saved, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_core_session_data.date_added) as DECIMAL(12,0)) as age, "
        . "ciniki_core_session_data.session_key "
        . "FROM ciniki_core_session_data "
        . "LEFT JOIN ciniki_core_api_keys ON (ciniki_core_session_data.api_key = ciniki_core_api_keys.api_key) "
        . "LEFT JOIN ciniki_users ON (ciniki_core_session_data.user_id = ciniki_users.id) "
        . "ORDER BY age "
        . "";
    $sessions = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.core', 'sessions', 'session', array('stat'=>'ok', 'sessions'=>array()));
/*
    // Sort the list ASC by date, so the oldest is at the bottom, and therefore will get insert at the top of the list in ciniki-manage
    $strsql = "SELECT "
    // DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as log_date"
        . "DATE_FORMAT(CONVERT_TZ(log_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS log_date "
        . ", CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age "
        . ", UNIX_TIMESTAMP(log_date) as TS"
        . ", ciniki_core_api_logs.id, user_id, ciniki_users.display_name, "
        . "IFNULL(ciniki_tenants.name, '') AS name, session_key, method, action "
        . "FROM ciniki_core_api_logs "
        . "LEFT JOIN ciniki_users ON (ciniki_core_api_logs.user_id = ciniki_users.id) "
        . "LEFT JOIN ciniki_tenants ON (ciniki_core_api_logs.tnid = ciniki_tenants.id) "
        . "WHERE ciniki_core_api_logs.user_id > 0 AND ciniki_core_api_logs.tnid > 0 "
//      . "WHERE UNIX_TIMESTAMP(ciniki_core_api_logs.log_date) > '" . ciniki_core_dbQuote($ciniki, $req_last_timestamp) . "' "
        . "ORDER BY TS DESC "
        . "LIMIT 25 ";
    $actions = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.core', 'actions', 'log', array('stat'=>'ok', 'actions'=>array()));
//  if( $actions['stat'] == 'ok' ) {
//      $actions['timestamp'] = $ts['timestamp']['cur'];
//  }

    return array('stat'=>'ok', 'sessions'=>$sessions['sessions'], 'actions'=>$actions['actions']);
*/
    return array('stat'=>'ok', 'sessions'=>$sessions['sessions']);
}
?>
