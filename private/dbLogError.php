<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function ciniki_core_dbLogError(&$ciniki, $err) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    //
    // Don't log if password passed
    //
    if( isset($ciniki['request']['method']) 
        && (
            $ciniki['request']['method'] == 'ciniki.users.auth' 
            || $ciniki['request']['method'] == 'ciniki.users.setPassword' 
            || $ciniki['request']['method'] == 'ciniki.users.resetPassword' 
            || $ciniki['request']['method'] == 'ciniki.users.changePassword' 
            || $ciniki['request']['method'] == 'ciniki.users.changeTempPassword' 
            )
        ) { 
        return array('stat'=>'ok');
    }

    //
    // Don't log if session expired error
    //
    $ignore_err_codes = array(5, 27, 37);
    if( isset($err['code']) && in_array($err['code'], $ignore_err_codes) ) {
        return array('stat'=>'ok');
    }

    $tnid = 0;
    if( isset($ciniki['request']['args']['tnid']) ) {
        $tnid = $ciniki['request']['args']['tnid'];
    }
    $strsql = "INSERT INTO ciniki_core_error_logs ("
        . "status, tnid, user_id, "
        . "session_key, method, "
        . "request_array, session_array, err_array, "
        . "log_date) VALUES ("
        . "10, "
        . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['method']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, serialize($ciniki['request'])) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, serialize($ciniki['session'])) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, serialize($err)) . "', "
        . "UTC_TIMESTAMP()"
        . ")";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    return ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.core');
}
?>
