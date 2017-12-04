<?php
//
// Description
// -----------
// This function will update the last_sync time in the database
// for a tenant sync.  It will also update last_partial and last_full
// if relevant.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
// sync_id:         The ID of the sync to check compatibility with.
//
function ciniki_core_syncUpdateLastTime($ciniki, $tnid, $sync_id, $type, $last_sync_time) {

    //
    // Prepare the SQL
    //
    $strsql = "UPDATE ciniki_tenant_syncs SET "
        . "last_sync = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') ";
    $strsql .= "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $sync_id) . "' "
        . "AND last_sync < FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    if( $type == 'partial' || $type == 'full' ) {
        $strsql = "UPDATE ciniki_tenant_syncs SET "
            . "last_partial = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $sync_id) . "' "
            . "AND last_partial < FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
    } 

    // The full sync, updates the partial and incremental dates as well
    if( $type == 'full' ) {
        $strsql = "UPDATE ciniki_tenant_syncs SET "
            . "last_full = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $sync_id) . "' "
            . "AND last_full < FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
