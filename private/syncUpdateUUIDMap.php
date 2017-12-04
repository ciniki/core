<?php
//
// Description
// -----------
// This function will update a uuid mapping between a remote tenant and a local tenant.  This is
// used primarily when the same user exists on different systems (sysadmin typically) and has a
// different uuid by same email address.  The map to show the different is stored here.
//
// Arguments
// ---------
//
function ciniki_core_syncUpdateUUIDMap(&$ciniki, &$sync, $tnid, $table_name, $remote_uuid, $local_id) {

    $strsql = "INSERT INTO ciniki_tenant_sync_uuidmaps (sync_id, table_name, "
        . "remote_uuid, local_id) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $sync['id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $table_name) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $remote_uuid) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $local_id) . "' "
        . ")";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
    //
    // Ignore error if a duplicate record warning
    //
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.core.73' ) {
        return $rc;
    }

    if( !isset($sync['uuidmaps'][$table_name]) ) {
        $sync['uuidmaps'][$table_name] = array();
    }

    $sync['uuidmaps'][$table_name][$remote_uuid] = $local_id;

    return array('stat'=>'ok');
}
?>
