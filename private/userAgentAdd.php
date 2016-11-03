<?php
//
// Description
// -----------
// This function will look for a user_agent string in the database.  If one is found,
// it will be returned, otherwise nothing.
//
// Arguments
// ---------
// ciniki:              
// device:          The device string to search for a user agent string.
// 
function ciniki_core_userAgentAdd($ciniki, $device) {
    //
    // Check device is setup properly
    //
    if( !isset($device['user_agent']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.385', 'msg'=>'Invalid device specification'));
    }
    if( !isset($device) || !isset($device['user_agent']) 
        || !isset($device['type_status'])
        || !isset($device['size'])
        || !isset($device['engine'])
        || !isset($device['device']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.386', 'msg'=>'Invalid device specification'));
    }

    if( !isset($device['flags']) ) {
        $device['flags'] = 0;
    }

    //
    // Create SQL string to insert the user_agent
    //
    $strsql = "INSERT INTO ciniki_core_user_agents (user_agent, type_status, size, flags, "
        . "engine, engine_version, "
        . "os, os_version, "
        . "browser, browser_version, "
        . "device, device_version, device_manufacturer, "
        . "date_added, last_updated) VALUES ( "
        . "'" . ciniki_core_dbQuote($ciniki, $device['user_agent']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $device['type_status']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $device['size']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $device['flags']) . "', ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    foreach(array('engine', 'engine_version', 'os', 'os_version', 
        'browser', 'browser_version', 'device', 'device_version', 'device_manufacturer') 
        as $field) {
        if( isset($device[$field]) && $device[$field] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $device[$field]) . "',";
        } else {
            $strsql .= "'',";
        }
    }
    $strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
    return ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.core');
}
?>
