<?php
//
// Description
// -----------
// This method will echo back the arguments sent.  This function is
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
// <rsp stat='ok' 'date'='2011-06-09' />
//
function ciniki_core_parseDate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'date'=>array('required'=>'no', 'default'=>'', 'blank'=>'no', 'type'=>'datetime', 'name'=>'Date'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access restrictions to checkAPIKey
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.parseDate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $rsp = array('stat'=>'ok', 'date'=>$args['date']);

    if( $args['date'] != '' ) {
        $dt = strtotime($args['date']);
        $rsp['year'] = date('Y', $dt);
        $rsp['month'] = date('m', $dt);
        $rsp['day'] = date('d', $dt);
        $rsp['time'] = date('h:i A', $dt);
    }

    return $rsp;
}
?>
