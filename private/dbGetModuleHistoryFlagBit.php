<?php
//
// Description
// -----------
// This function will fetch the list of changes for a field from the ciniki_core_change_logs, and
// reformat the output for the specified format.
//
// Info
// ----
// status:          beta
//
// Arguments
// ---------
// ciniki:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
//
function ciniki_core_dbGetModuleHistoryFlagBit(&$ciniki, $module, $history_table, $tnid, $table_name, $table_key, $table_field, $bit, $off, $on) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
    $rc = ciniki_core_dbConnect($ciniki, 'ciniki.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Get the history log from ciniki_core_change_logs table.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');

    //
    // Load tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $date_format = ciniki_users_dateFormat($ciniki);
    $time_format = ciniki_users_timeFormat($ciniki);
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');
    $php_time_format = ciniki_users_timeFormat($ciniki, 'php');
    $php_datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Check if reformat is for price
    //
//  if( $format == 'currency' || $format == 'utcdate' || $format == 'utcdatetime' ) {
//  } else {
//      $date_format = ciniki_users_dateFormat($ciniki);
////        $datetime_format = ciniki_users_datetimeFormat($ciniki);
//  }

    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    $strsql = "SELECT user_id, "
//      . "DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date, "
        . "log_date as date, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) AS age, "
        . "IF( (new_value&" . $bit . ")=$bit, '" . ciniki_core_dbQuote($ciniki, $on) . "', '" . ciniki_core_dbQuote($ciniki, $off) . "') AS value ";
    $strsql .= " FROM $history_table "
        . " WHERE tnid ='" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . " AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
        . " AND table_key = '" . ciniki_core_dbQuote($ciniki, $table_key) . "' "
        . " AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_field) . "' "
        . " ORDER BY log_date DESC "
        . "";
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.53', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    if( mysqli_num_rows($result) <= 0 ) {
        return array('stat'=>'ok', 'history'=>array(), 'users'=>array());
    }

    $rsp = array('stat'=>'ok', 'history'=>array(), 'users'=>array());
    $user_ids = array();
    $num_history = 0;
    $prev = null;
    while( $row = mysqli_fetch_assoc($result) ) {
        if( $prev != null && $prev == $row['value'] ) {
            continue;
        }
        $rsp['history'][$num_history] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 'value'=>$row['value']));
        // Format the date
        $date = new DateTime($row['date'], new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($intl_timezone));
        $rsp['history'][$num_history]['action']['date'] = $date->format($php_datetime_format);

//      if( $row['user_id'] > 0 ) {
            array_push($user_ids, $row['user_id']);
//      }
        $rsp['history'][$num_history]['action']['age'] = ciniki_core_dbParseAge($ciniki, $row['age']);
        $num_history++;
        $prev = $row['value'];
    }

    mysqli_free_result($result);

    //
    // If there was no history, or user ids, then skip the user lookup and return
    //
    if( $num_history < 1 || count($user_ids) < 1 ) {
        return $rsp;
    }

    //
    // Get the list of users
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'userListByID');
    $rc = ciniki_users_userListByID($ciniki, 'users', array_unique($user_ids), 'display_name');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.54', 'msg'=>'Unable to merge user information', 'err'=>$rc['err']));
    }
    $users = $rc['users'];

    //
    // Merge user list information into array
    //
    foreach($rsp['history'] as $k => $v) {
        if( isset($v['action']) && isset($v['action']['user_id']) //&& $v['action']['user_id'] > 0 
            && isset($users[$v['action']['user_id']]) && isset($users[$v['action']['user_id']]['display_name']) ) {
            $rsp['history'][$k]['action']['user_display_name'] = $users[$v['action']['user_id']]['display_name'];
        }
    }

    return $rsp;
}
?>
