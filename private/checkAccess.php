<?php
//
// Description
// -----------
// This function will check if the user has access to a specified module and function.
//
// Info
// ----
// Status:              beta
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_core_checkAccess($ciniki, $business_id, $method) {
    //
    // Methods which don't require authentication
    //
    $noauth_methods = array(
        'ciniki.core.echoTest',
        );
    if( in_array($method, $noauth_methods) ) {
        return array('stat'=>'ok');
    }

    //
    // Check the user is authenticated
    //
    if( !isset($ciniki['session'])
        || !isset($ciniki['session']['user'])
        || !isset($ciniki['session']['user']['id'])
        || $ciniki['session']['user']['id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.24', 'msg'=>'User not authenticated'));
    }

    //
    // Check if the requested method is a public method
    //
    $public_methods = array(
        'ciniki.core.getAddressCountryCodes',
        'ciniki.core.parseDatetime',
        'ciniki.core.parseDate',
        );
    if( in_array($method, $public_methods) ) {
        return array('stat'=>'ok');
    }

    //
    // If the user is a sysadmin, they have access to all functions
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok');
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.25', 'msg'=>'Access denied'));
}
?>
