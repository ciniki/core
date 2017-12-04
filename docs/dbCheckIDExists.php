<?php
//
// Description
// -----------
// This function will query the specified database table, and check the 
// specified ID exists and belongs to the specified tenant.
//
// Arguments
// ---------
// ciniki:          
// module:          The module to check for the id.
// table:           The table to search for the id.
// id_field:        The name of the field to search.
// tnid:     The ID of the tenant to match in the table.  If 0, it will be
//                  ignored.
// id_value:        The value of the ID to search for.
//
function ciniki_core_dbCheckIDExists($ciniki, $module, $table, $id_field, $tnid, $id_value) {

    //
    // Prepare the SQL string to check for the ID
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT " . ciniki_core_dbQuote($ciniki, $id_field) . " "
        . "FROM '" . ciniki_core_dbQuote($ciniki, $table) . "' "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $id_value) . "' ";
    if( $tnid > 0 ) {
        $strsql .= "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, $module, 'idcheck');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['idcheck']) && $rc['idcheck'][$id_field] = $id_value ) {
        return array('stat'=>'ok');
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.7', 'msg'=>'ID not found'));
}
?>
