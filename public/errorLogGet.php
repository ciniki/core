<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_core_errorLogGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'error_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Error'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.errorLogGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Get the list of syncs setup for this business
    //
    $strsql = "SELECT ciniki_core_error_logs.id, ciniki_core_error_logs.status, "
        . "ciniki_core_error_logs.business_id, "
        . "IFNULL(ciniki_businesses.name, '--SYSTEM--') AS business_name, "
        . "ciniki_core_error_logs.user_id, "
        . "CONCAT_WS(' ', ciniki_users.firstname, ciniki_users.lastname) AS user_name, "
        . "ciniki_core_error_logs.session_key, "
        . "ciniki_core_error_logs.method, "
        . "ciniki_core_error_logs.request_array, "
        . "ciniki_core_error_logs.session_array, "
        . "ciniki_core_error_logs.err_array, "
        . "DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS log_date, "
        . "CAST((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date)) as DECIMAL(12,0)) AS age "
        . "FROM ciniki_core_error_logs "
        . "LEFT JOIN ciniki_businesses ON (ciniki_core_error_logs.business_id = ciniki_businesses.id) "
        . "LEFT JOIN ciniki_users ON (ciniki_core_error_logs.user_id = ciniki_users.id) "
        . "WHERE ciniki_core_error_logs.id = '" . ciniki_core_dbQuote($ciniki, $args['error_id']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] != '' ) {
        $strsql .= "WHERE ciniki_core_error_logs.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    $strsql .= "ORDER BY ciniki_core_error_logs.log_date DESC "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'error');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['error']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.387', 'msg'=>'Unable to find error log'));
    }

    $rc['error']['request_array'] = unserialize($rc['error']['request_array']);
    $rc['error']['session_array'] = unserialize($rc['error']['session_array']);
    $rc['error']['err_array'] = unserialize($rc['error']['err_array']);

    $rc['error']['request_array_r'] = print_r($rc['error']['request_array'], true);
    $rc['error']['session_array_r'] = print_r($rc['error']['session_array'], true);
    $rc['error']['err_array_r'] = print_r($rc['error']['err_array'], true);
    
    return array('stat'=>'ok', 'error'=>$rc['error']);
}
?>
