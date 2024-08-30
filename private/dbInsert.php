<?php
//
// Description
// -----------
// This function will run an insert query against the database. 
//
// Arguments
// ---------
// ciniki:
// strsql:          The SQL statement to execute which will INSERT a row into the database.
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
//
function ciniki_core_dbInsert(&$ciniki, $strsql, $module) {
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

    if( isset($ciniki['config']['ciniki.core']['database.defaults.fix']) && $ciniki['config']['ciniki.core']['database.defaults.fix'] == 'yes' ) {
        mysqli_query($dh, "SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
    }

    //
    // Prepare and Execute Query
    //
    try {
        $result = mysqli_query($dh, $strsql);
    } catch(mysqli_sql_exception $e) {
        if( $e->getCode() == 1062 || $e->getCode() == 1022 ) {
            return array('stat'=>'exists', 'err'=>array('code'=>'ciniki.core.73', 'msg'=>'Database Error - Duplicate', 'pmsg'=>$e->getMessage(), 'dberrno'=>$e->getCode(), 'sql'=>$strsql));
        } else {
            error_log("SQLERR: [" . $e->getCode() . "] " . $e->getMessage() . " -- '$strsql'");
        }
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.66', 'msg'=>'Database Error', 'pmsg'=>$e->getMessage(), 'dberrno'=>$e->getCode(), 'sql'=>$strsql));
    }

    //
    // Check if any rows returned from the query
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_affected_rows'] = mysqli_affected_rows($dh);
    $rsp['insert_id'] = mysqli_insert_id($dh);

    unset($rc);

    return $rsp;
}
?>
