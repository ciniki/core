<?php
//
// Description
// -----------
// This function will query the database and return a hash of rows.
//
// Info
// ----
// status:          beta
//
// Arguments
// ---------
// ciniki:          
// strsql:          The SQL string to query the database.
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
// container_name:  The name of the xml/hash tag to return the data under, 
//                  when there is only one row returned.
//
function ciniki_core_dbHashQuery(&$ciniki, $strsql, $module, $container_name) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
    $rc = ciniki_core_dbConnect($ciniki, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Prepare and Execute Query
    //
    $start_time = microtime(true);
    try {
        $result = mysqli_query($dh, $strsql);
    } catch(mysqli_sql_exception $e) {
        error_log("SQLERR: [" . $e->getCode() . "] " . $e->getMessage() . " -- '$strsql'");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.60', 'msg'=>'Database Error', 'pmsg'=>$e->getMessage()));
    }
    $mid_time = microtime(true);
/*    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        error_log("SQLERR: [" . mysqli_errno($dh) . "] " . mysqli_error($dh) . " -- '$strsql'");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.60', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    } */

    //
    // Check if any rows returned from the query
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_rows'] = 0;

    //
    // Build array of rows
    //
    $rsp['rows'] = array();
    while( $row = mysqli_fetch_assoc($result) ) {
        $rsp['rows'][$rsp['num_rows']++] = $row;
    }

    mysqli_free_result($result);
    $end_time = microtime(true);

    if( isset($ciniki['config']['ciniki.core']['database.log.querytimes'])
        && $ciniki['config']['ciniki.core']['database.log.querytimes']
        ) {
        error_log('[' . round($mid_time-$start_time, 2) . ':' . round($end_time-$mid_time, 2) . '] ' . $strsql);
    }

    //
    // Setup the container name if specified
    //
    if( $rsp['num_rows'] == 1 && $container_name != '' ) {
        $rsp[$container_name] = $rsp['rows']['0'];
    }

    return $rsp;
}
?>
