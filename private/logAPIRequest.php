<?php
//
// Description
// -----------
// This function will add a request to the api logs.
//
function ciniki_core_logAPIRequest($ciniki) {
    //
    // Log a API request 
    //
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
    if( isset($_SERVER['REMOTE_ADDR']) ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $_SERVER['REMOTE_ADDR']) . "', ";
    } else {
        $strsql .= "'localhost', ";
    }
    $strsql .= "UTC_TIMESTAMP())";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    return ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.core');
}
?>
