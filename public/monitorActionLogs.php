<?php
//
// Description
// -----------
// This function will get the history of a field from the ciniki_core_api_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
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
// session_key:             The session key to get only the action logs from.
//
// Returns
// -------
//  <logs timestamp=''>
//      <log id='' date="2011/02/03 00:03:00" value="Value field set to" user_id="1" display_name="" />
//  </logs>
//
function ciniki_core_monitorActionLogs($ciniki) {
    //
    // Get the args
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'last_timestamp'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Last Timestamp'),
        'session_key'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Session Key'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access restrictions to monitorActionLogs
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.monitorActionLogs');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    $strsql = "SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) as cur";
    $ts = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'timestamp');
    if( $ts['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'175', 'msg'=>'No timestamp available'));
    }

    //
    // Verify the field was passed, and is valid.
    //
    $last_timestamp = $ts['timestamp']['cur'] - 43200;      // Get anything that is from the last 12 hours by default.
    $last_timestamp = $ts['timestamp']['cur'] - 86400;      // Get anything that is from the last 24 hours by default.
    $req_last_timestamp = $last_timestamp;
    if( isset($args['last_timestamp']) && $args['last_timestamp'] != '' ) {
        $req_last_timestamp = $args['last_timestamp'];
    }
    // Force last_timestamp to be no older than 1 week
    if( $req_last_timestamp < ($ts['timestamp']['cur'] - 604800) ) {
        $req_last_timestamp = $ts['timestamp']['cur'] - 604800;
    }

    $date_format = ciniki_users_datetimeFormat($ciniki);

    // Sort the list ASC by date, so the oldest is at the bottom, and therefore will get insert at the top of the list in ciniki manage
    $strsql = "SELECT DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as log_date"
        . ", CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age "
        . ", UNIX_TIMESTAMP(log_date) as TS"
        . ", ciniki_core_api_logs.id, user_id, ciniki_users.display_name, "
        . "IFNULL(ciniki_businesses.name, 'System Admin') AS name, session_key, method, action "
        . "FROM ciniki_core_api_logs "
        . "LEFT JOIN ciniki_users ON (ciniki_core_api_logs.user_id = ciniki_users.id) "
        . "LEFT JOIN ciniki_businesses ON (ciniki_core_api_logs.business_id = ciniki_businesses.id) ";
    if( isset($args['session_key']) && $args['session_key'] != '' ) {
        $strsql .= "WHERE session_key = '" . ciniki_core_dbQuote($ciniki, $args['session_key']) . "' ";
    } else {
        $strsql .= "WHERE UNIX_TIMESTAMP(ciniki_core_api_logs.log_date) > '" . ciniki_core_dbQuote($ciniki, $req_last_timestamp) . "' ";
    }
//      . "AND ciniki_core_api_logs.user_id = ciniki_users.id "
    $strsql .= ""
        . "ORDER BY TS DESC "
        . "LIMIT 100 ";
    $rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.core', 'logs', 'log', array('stat'=>'ok', 'logs'=>array()));
    if( $rsp['stat'] == 'ok' ) {
        $rsp['timestamp'] = $ts['timestamp']['cur'];
    }

    return $rsp;
}
?>
