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
function ciniki_core_errorLogList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.errorLogList');
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
        . "IFNULL(ciniki_businesses.name, '--System--') AS business_name, "
        . "ciniki_core_error_logs.user_id, "
        . "CONCAT_WS(' ', ciniki_users.firstname, ciniki_users.lastname) AS user_name, "
        . "ciniki_core_error_logs.method, "
        . "ciniki_core_error_logs.session_key, "
        . "DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS log_date, "
        . "CAST((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date)) as DECIMAL(12,0)) AS age "
        . "FROM ciniki_core_error_logs "
        . "LEFT JOIN ciniki_businesses ON (ciniki_core_error_logs.business_id = ciniki_businesses.id) "
        . "LEFT JOIN ciniki_users ON (ciniki_core_error_logs.user_id = ciniki_users.id) "
        . "";
    if( isset($args['status']) && $args['status'] != '' ) {
        $strsql .= "WHERE ciniki_core_error_logs.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    $strsql .= "ORDER BY ciniki_core_error_logs.log_date DESC "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
        array('container'=>'errors', 'fname'=>'id', 'name'=>'error',
            'fields'=>array('id', 'status', 'business_id', 'business_name', 
                'user_id', 'user_name', 'method', 'session_key', 'log_date', 'age'),
            'maps'=>array('status'=>array('10'=>'Entered', '50'=>'Archived')),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return $rc;
}
?>
