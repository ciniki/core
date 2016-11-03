<?php
//
// Description
// -----------
// The ciniki.core.dbRspQuery method will format a query response which
// can be directly handed back through the API if necessary.
//
// Info
// ----
// status:              beta
//
// Arguments
// ---------
// ciniki:              
// strsql:              The SQL string to query the database with.
// module:              The name of the module for the transaction, which should include the 
//                      package in dot notation.  Example: ciniki.artcatalog
// container_name:      The container name to attach the data when only one row returned.
// no_row_error:        The error code and msg to return when no rows were returned from the query.
//
function ciniki_core_dbRspQuery(&$ciniki, $strsql, $module, $container_name, $row_name, $no_row_error) {
    //
    // Check connection to database, and open if necessary
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.73', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    if( mysqli_num_rows($result) <= 0 ) {
        return $no_row_error;
    }

    //
    // FIXME: If hash, then return all rows together as a hash
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_rows'] = 0;

    //
    // Build array of rows
    //
    $rsp[$container_name] = array();
    while( $row = mysqli_fetch_assoc($result) ) {
        $rsp[$container_name][$rsp['num_rows']] = array($row_name=>$row);
        if( isset($row['age']) ) {
            $rsp[$container_name][$rsp['num_rows']][$row_name]['age'] = ciniki_core_dbParseAge($ciniki, $row['age']);
        }
        $rsp['num_rows']++;
    }

    mysqli_free_result($result);

    // 
    // FIXME: If tmpl, then  apply template to each row 
    //

    return $rsp;
}
?>
