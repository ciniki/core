<?php
//
// Description
// -----------
// This method retrieves the history elements for a module field.  The users display_name is 
// attached to each record as user_display_name.
//
// Arguments
// ---------
// ciniki:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
//
//
function ciniki_core_dbGetModuleHistoryJSON(&$ciniki, $module, $history_table, $tnid, $table_name, $table_key, $table_field, $json_field) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
    $rc = ciniki_core_dbConnect($ciniki, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    date_default_timezone_set($intl_timezone);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Get the history log from ciniki_core_change_logs table.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');

    $strsql = "SELECT user_id, "
        . "log_date as date, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
        . "action, "
        . "table_key, "
        . "new_value as value "
        . "FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
        . "WHERE tnid ='" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' ";
    if( is_array($table_key) ) {
        $strsql .= "AND table_key IN (" . ciniki_core_dbQuoteList($ciniki, $table_key) . ") ";
    } else {
        $strsql .= "AND table_key = '" . ciniki_core_dbQuote($ciniki, $table_key) . "' ";
    }
    $strsql .= "AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_field) . "' "
        . " ORDER BY log_date ASC "
        . "";
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.47', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    if( mysqli_num_rows($result) <= 0 ) {
        return array('stat'=>'ok', 'history'=>array());
    }

    $rsp = array('stat'=>'ok', 'history'=>array());
    $user_ids = array();
    $num_history = 0;
    $prev_value = null;
    while( $row = mysqli_fetch_assoc($result) ) {
        // Format the date
        $date = new DateTime($row['date'], new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($intl_timezone));
        $age = ciniki_core_dbParseAge($ciniki, $row['age']);
        if( $result == '' ) {
            continue;
        }
        $json = json_decode($row['value'], true);
        if( isset($json[$json_field]) && $json[$json_field] != null ) {
            if( $prev_value == null ) {
                $rsp['history'][] = [
                    'action' => [
                        'user_id'=>$row['user_id'], 
                        'date'=>$date->format($datetime_format), 
                        'value'=>$json[$json_field], 
                        'age'=>$age,
                    ]];
            } elseif( $prev_value != $json[$json_field] ) {
                array_unshift($rsp['history'], [
                    'action' => [
                    'user_id' => $row['user_id'], 
                    'date'=>$date->format($datetime_format), 
                    'value' => $json[$json_field], 
                    'age' => $age,
                    ]]);
            }
            if( $row['user_id'] != 0 && !in_array($row['user_id'], $user_ids) ) {
                array_push($user_ids, $row['user_id']);
            }
            $prev_value = $json[$json_field];
        }
    }
    mysqli_free_result($result);

    //
    // If there was no history, or user ids, then skip the user lookup and return
    //
    if( count($user_ids) < 1 ) {
        return $rsp;
    }

    //
    // Get the list of users
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'userListByID');
    $rc = ciniki_users_userListByID($ciniki, 'users', array_unique($user_ids), 'display_name');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.48', 'msg'=>'Unable to merge user information', 'err'=>$rc['err']));
    }
    $users = $rc['users'];

    //
    // Merge user list information into array
    //
    foreach($rsp['history'] as $k => $v) {
        if( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] != 0 
            && isset($users[$v['action']['user_id']]) && isset($users[$v['action']['user_id']]['display_name']) ) {
            $rsp['history'][$k]['action']['user_display_name'] = $users[$v['action']['user_id']]['display_name'];
        } 
        if( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] == 0 ) {
            $rsp['history'][$k]['action']['user_display_name'] = 'unknown';
        }
    }

    return $rsp;
}
?>
