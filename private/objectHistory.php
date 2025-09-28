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
function ciniki_core_objectHistory(&$ciniki, $tnid, $obj_name, $args) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    //
    // Load the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');
    $rc = ciniki_core_objectLoad($ciniki, $obj_name);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $o = $rc['object'];
    $m = "$pkg.$mod";

    //
    // Open database connection
    //
    $rc = ciniki_core_dbConnect($ciniki, $m);
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

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Check if customer_id is specified
    //
    $customer = isset($o['history_customer']) ? $o['history_customer'] : 'no';

    //
    // Get the history log from ciniki_core_change_logs table.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');

    $strsql = "SELECT history.user_id, "
        . ($customer == 'yes' ? "history.customer_id, IFNULL(customers.display_name, '') AS customer_name, " : '')
        . "history.log_date as date, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(history.log_date) as DECIMAL(12,0)) as age, "
        . "history.action, "
        . "history.table_key, ";
    if( isset($args['flagbit']) ) {
        $strsql .= "IF( (new_value&{$args['flagbit']})={$args['flagbit']}, '" . ciniki_core_dbQuote($ciniki, $args['flagon']) . "', '" . ciniki_core_dbQuote($ciniki, $args['flagoff']) . "') AS value ";
    } else {
        $strsql .= "history.new_value as value ";
    }
    $strsql .= "FROM " . ciniki_core_dbQuote($ciniki, $o['history_table']) . " AS history ";
    if( $customer == 'yes' ) {
        $strsql .= "LEFT JOIN ciniki_customers AS customers ON ("
            . "history.customer_id = customers.id "
            . "AND customers.tnid ='" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    }
    $strsql .= "WHERE history.tnid ='" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND history.table_name = '" . ciniki_core_dbQuote($ciniki, $o['table']) . "' ";
    if( is_array($args['key']) ) {
        $strsql .= "AND history.table_key IN (" . ciniki_core_dbQuoteList($ciniki, $args['key']) . ") ";
    } else {
        $strsql .= "AND history.table_key = '" . ciniki_core_dbQuote($ciniki, $args['key']) . "' ";
    }
    $strsql .= "AND history.table_field = '" . ciniki_core_dbQuote($ciniki, $args['field']) . "' "
        . "ORDER BY history.log_date DESC "
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
    while( $row = mysqli_fetch_assoc($result) ) {
        $rsp['history'][$num_history] = [
            'action' => [
                'user_id' => $row['user_id'], 
                'date' => $row['date'], 
                'value' => $row['value'],
                ]];
/*        if( $format != 'date' && $format != 'time' && is_callable($format) ) {
            $rsp['history'][$num_history]['action']['value'] = $format($row['value']);
        }
        else*/
        if( isset($o['fields'][$args['field']]['type']) ) {
            $format = $o['fields'][$args['field']]['type'];
            if( $format == 'utcdate' && $row['value'] != '' ) {
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
                if( is_numeric($row['value']) ) {
                    $rsp['history'][$num_history]['action']['value'] = '$' . number_format($row['value'], 2);
                }
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
        }
        if( $customer == 'yes' ) {
            $rsp['history'][$num_history]['action']['customer_name'] = $row['customer_name'];
        }
        if( is_array($args['key']) ) {
            $rsp['history'][$num_history]['action']['key'] = $row['table_key'];
        }
        // Format the date
        $date = new DateTime($row['date'], new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($intl_timezone));
        $rsp['history'][$num_history]['action']['date'] = $date->format($datetime_format);

        if( $row['user_id'] != 0 ) {
            array_push($user_ids, $row['user_id']);
        }
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.48', 'msg'=>'Unable to merge user information', 'err'=>$rc['err']));
    }
    $users = $rc['users'];

    //
    // Merge user list information into array
    //
    foreach($rsp['history'] as $k => $v) {
        if( $v['action']['user_id'] == -2 ) {
            $rsp['history'][$k]['action']['user_display_name'] = 'Website'
                . (isset($v['action']['customer_name']) && $v['action']['customer_name'] != '' ? " ({$v['action']['customer_name']})" : '');
        }
        elseif( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] != 0 
            && isset($users[$v['action']['user_id']]) && isset($users[$v['action']['user_id']]['display_name']) ) {
            $rsp['history'][$k]['action']['user_display_name'] = $users[$v['action']['user_id']]['display_name'];
        } 
        elseif( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] == 0 ) {
            $rsp['history'][$k]['action']['user_display_name'] = 'unknown';
        }
    }

    return $rsp;
}
?>
