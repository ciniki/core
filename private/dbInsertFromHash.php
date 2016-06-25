<?php
//
// Description
// -----------
// This function will add a new customer given a hash of keys.
//
// Info
// ----
// Status: started
//
// Arguments
// ---------
// ciniki:
//
function ciniki_core_dbInsertFromHash(&$ciniki, $fields, $record, $prefix, $middle, $suffix) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashToSQL');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

    //
    // Build the SQL string using the provide information
    //
    $rc = ciniki_core_dbHashToSQL($ciniki, $fields, $record, $prefix, $middle, $suffix);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    // 
    // If an SQL string was built, then try to run it
    //
    if( isset($rc['strsql']) && $rc['strsql'] != '') {
        $new_db_record = ciniki_core_dbInsert($ciniki, $rc['strsql'], 'ciniki.customers');
        return $new_db_record;
    } 

    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'70', 'msg'=>'Internal error', 'pmsg'=>'Unable to build SQL insert string'));
}
?>
