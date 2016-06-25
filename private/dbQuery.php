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
// ciniki:          The ciniki data structure.
// strsql:          The SQL string to query the database.
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
//
function ciniki_core_dbQuery(&$ciniki, $strsql, $module) {
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
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'93', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    return array('stat'=>'ok', 'handle'=>$result);
}
?>
