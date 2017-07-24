<?php
//
// Description
// -----------
// This function will return the size of tables in the database.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_core_checkDbTableSizes($ciniki) {
    //
    // Check access restrictions to checkAPIKey
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.checkDbTableSizes');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $strsql = "SELECT table_name, "
        . "ROUND(((data_length + index_length) / 1024 / 1024), 2) AS mb "
        . "FROM information_schema.TABLES "
        . "WHERE table_schema = '" . ciniki_core_dbQuote($ciniki, $ciniki['config']['ciniki.core']['database.ciniki.database']) . "' "
        . "ORDER BY mb DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.core', array(
        array('container'=>'tables', 'fname'=>'table_name', 'fields'=>array('table_name', 'mb')),
        ));
    return $rc;
}
?>
