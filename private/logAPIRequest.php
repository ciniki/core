<?php
//
// Description
// -----------
// This function will add a request to the api logs.
//
function ciniki_core_logAPIRequest($ciniki) {

    //
    // Check if request should be logged to the file
    //
    if( isset($ciniki['config']['ciniki.core']['logging.api.file']) 
        && $ciniki['config']['ciniki.core']['logging.api.file'] == 'yes'
        ) {
//        $logfile = $ciniki['config']['ciniki.core']['log_dir'] . '/api.
        $dt = new DateTime('now', new DateTimezone('UTC'));
        $msg = '[' . $dt->format('d/m/Y:H:i:s O') . ']';
        if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '' ) {
            $msg .= " " . ciniki_core_dbQuote($ciniki, $_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif( isset($_SERVER['REMOTE_ADDR']) ) {
            $msg .= " " . ciniki_core_dbQuote($ciniki, $_SERVER['REMOTE_ADDR']);
        } else {
            $msg .= " LOCALHOST";
        }
        if( isset($ciniki['request']['args']['tnid']) ) {
            $msg .= " " . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['tnid']);
        } else {
            $msg .= " 0";
        }
        if( isset($ciniki['session']['user']['id']) ) {
            $msg .= " (" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . ")";
        } else {
            $msg .= " (0)";
        }
        if( isset($ciniki['session']['change_log_id']) ) {
            $msg .= " " . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']);
        } else {
            $msg .= " -";
        }
        $msg .= " " . $ciniki['request']['method'];
        if( isset($ciniki['request']['action']) ) {
            $msg .= " " . ciniki_core_dbQuote($ciniki, $ciniki['request']['action']);
        } else {
            $msg .= " -";
        }

        if( isset($ciniki['config']['ciniki.core']['logging.api.dir']) ) {
            $log_dir = $ciniki['config']['ciniki.core']['logging.api.dir'] . '/ciniki.core';
        } else {
            $log_dir = $ciniki['config']['ciniki.core']['log_dir'] . '/ciniki.core';
        }
        if( !file_exists($log_dir) ) {
            mkdir($log_dir);
        }

        file_put_contents($log_dir . '/api.' . $dt->format('Y-m') . '.log', $msg . "\n", FILE_APPEND);
    }

    //
    // Check if request should be logged to database
    //
    if( !isset($ciniki['config']['ciniki.core']['logging.api.db']) 
        || $ciniki['config']['ciniki.core']['logging.api.db'] == 'yes'
        ) {
        $strsql = "INSERT INTO ciniki_core_api_logs (uuid, user_id, tnid, session_key, method, action, ip_address, "
            . "log_date ) VALUES (uuid(), "
            . "";
        if( isset($ciniki['session']['user']['id']) ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', ";
        } else {
            $strsql .= "0, ";
        }
        if( isset($ciniki['request']['args']['tnid']) ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['tnid']) . "', ";
        } else {
            $strsql .= "0, ";
        }
        if( isset($ciniki['session']['change_log_id']) ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', ";
        } else {
            $strsql .= "'', ";
        }
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['method']) . "', "
            . "";
        if( isset($ciniki['request']['action']) ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['action']) . "', ";
        } else {
            $strsql .= "'', ";
        }
        if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $_SERVER['HTTP_X_FORWARDED_FOR']) . "', ";
        } elseif( isset($_SERVER['REMOTE_ADDR']) ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $_SERVER['REMOTE_ADDR']) . "', ";
        } else {
            $strsql .= "'localhost', ";
        }
        $strsql .= "UTC_TIMESTAMP())";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
        $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.core');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
