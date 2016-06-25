<?php
//
// Description
// -----------
// This method echos back the arguments sent.  This function is
// for simple testing, similar to ping in network tests.
//
// Info
// ----
// status:          beta
// 
// Arguments
// ---------
// api_key:         
// *args:           Any additional arguments passed to the method will be returned in the response.
//
// Returns
// -------
// <request api_key='0123456789abcdef0123456789abcdef' auth_token='' method='ciniki.core.echoTest'>
//  <args args1="test" />
// </reqeust>
//
function ciniki_core_echoTest($ciniki) {
    //
    // Check access restrictions to checkAPIKey
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.echoTest');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'request'=>$ciniki['request']);
}
?>
