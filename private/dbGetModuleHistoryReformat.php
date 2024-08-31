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
function ciniki_core_dbGetModuleHistoryReformat(&$ciniki, $module, $history_table, $tnid, $table_name, $table_key, $table_field, $format) {
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];
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
        . "new_value as value ";
    if( $format == 'date' ) {
        $strsql .= ", DATE_FORMAT(new_value, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS formatted_value ";
    } elseif( $format == 'time' ) {
        $strsql .= ", TIME_FORMAT(new_value, '" . ciniki_core_dbQuote($ciniki, $time_format) . "') AS formatted_value ";
//  } elseif( $format == 'utcdate' ) {
//      $strsql .= ", DATE_FORMAT(CONVERT_TZ(new_value, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS formatted_value ";
    } elseif( $format == 'datetime' ) {
        $strsql .= ", DATE_FORMAT(new_value, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS formatted_value ";
    }
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
    while( $row = mysqli_fetch_assoc($result) ) {
        $rsp['history'][$num_history] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 'value'=>$row['value']));
        if( $format != 'date' && $format != 'time' && is_callable($format) ) {
            $rsp['history'][$num_history]['action']['value'] = $format($row['value']);
        }
        elseif( $format == 'utcdate' && $row['value'] != '' ) {
            $date = new DateTime($row['value'], new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone($intl_timezone));
            $rsp['history'][$num_history]['action']['value'] = $date->format($php_date_format);
        }
        elseif( $format == 'utctime' && $row['value'] != '' ) {
            $date = new DateTime($row['value'], new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone($intl_timezone));
            $rsp['history'][$num_history]['action']['value'] = $date->format($php_time_format);
        }
        elseif( $format == 'utcdatetime' && $row['value'] != '' ) {
            $date = new DateTime($row['value'], new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone($intl_timezone));
            $rsp['history'][$num_history]['action']['value'] = $date->format($php_datetime_format);
        }
        elseif( $format == 'date' || $format == 'time' || $format == 'datetime' ) {
            $rsp['history'][$num_history]['action']['formatted_value'] = $row['formatted_value'];
        }
        elseif( $format == 'currency' ) {
            if( isset($row['value']) == '' ) {
                $row['value'] = 0.00;
            }
            if( isset($row['value'][0]) && $row['value'][0] == '$' ) {
                $row['value'] = substr($row['value'], 1, strlen($row['value']));
            }
            $rsp['history'][$num_history]['action']['value'] = numfmt_format_currency(
                $intl_currency_fmt, $row['value'], $intl_currency);
        }
        elseif( $format == 'percent' ) {
            $rsp['history'][$num_history]['action']['value'] = ($row['value'] * 100) . '%';
        }
        elseif( $format == 'minsec' ) {
            $rsp['history'][$num_history]['action']['value'] = $row['value'];
            if( is_numeric($row['value']) ) {
                $m = intval($row['value']/60);
                $s = $row['value'] % 60;
                $rsp['history'][$num_history]['action']['formatted_value'] = $m . ':' . str_pad($s,2, '0', STR_PAD_LEFT);
            }
        }
        // Format the date
        $date = new DateTime($row['date'], new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($intl_timezone));
        $rsp['history'][$num_history]['action']['date'] = $date->format($php_datetime_format);

//      if( $row['user_id'] > 0 ) {
            array_push($user_ids, $row['user_id']);
//      }
        $rsp['history'][$num_history]['action']['age'] = ciniki_core_dbParseAge($ciniki, $row['age']);
        $num_history++;
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
