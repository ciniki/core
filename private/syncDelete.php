<?php
//
// Description
// -----------
// This function will remote the sync from the database.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business on the local side to check sync.
// sync_id:         The ID of the sync to check compatibility with.
//
function ciniki_core_syncDelete($ciniki, $business_id, $sync_id) {

    //
    // Delete from local server
    //
    $strsql = "DELETE FROM ciniki_business_syncs "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $sync_id) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
