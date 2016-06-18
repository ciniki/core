<?php
//
// Description
// -----------
// This function should be used when a single column will contain the COUNT(*) of rows. 
// If multiple rows are returned the first column is summed and returned
//
// Arguments
// ---------
// ciniki:                 The ciniki data structure with current session.
// strsql:                The SQL string to query the database with.
// module:                The name of the module for the transaction, which should include the 
//                        package in dot notation.  Example: ciniki.artcatalog
// container_name:        The container name to attach the data when only one row returned.
//
function ciniki_core_dbSingleCount(&$ciniki, $strsql, $module, $container_name) {
    //
    // Check connection to database, and open if necessary
    //
    $rc = ciniki_core_dbConnect($ciniki, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Prepare and Execute Query
    //
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        error_log("SQLERR: " . mysqli_error($dh) . " -- '$strsql'");
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3447', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // FIXME: If hash, then return all rows together as a hash
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_rows'] = 0;

    //
    // Build array of rows
    //
    $rsp[$container_name] = 0;
    while( $row = mysqli_fetch_row($result) ) {
        $rsp[$container_name] += $row[0];
        $rsp['num_rows']++;
    }

    mysqli_free_result($result);

    return $rsp;
}
?>
