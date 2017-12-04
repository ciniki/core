<?php
//
// Description
// -----------
// This function will load the sync details for running sync commands.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
// sync_id:         The ID of the sync to check compatibility with.
//
function ciniki_core_syncLoad(&$ciniki, $tnid, $sync_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    //
    // Get the sync information required to send the request
    //
    $strsql = "SELECT ciniki_tenant_syncs.id, ciniki_tenants.uuid AS local_uuid, "
        . "ciniki_tenants.sitename, "
        . "ciniki_tenant_syncs.status, "
        . "ciniki_tenant_syncs.flags, local_private_key, "
        . "ciniki_tenant_syncs.remote_name, ciniki_tenant_syncs.remote_uuid, "
        . "ciniki_tenant_syncs.remote_url, ciniki_tenant_syncs.remote_public_key, "
        . "UNIX_TIMESTAMP(last_sync) AS last_sync "
        . "FROM ciniki_tenants, ciniki_tenant_syncs "
        . "WHERE ciniki_tenants.id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_tenants.id = ciniki_tenant_syncs.tnid "
        . "AND ciniki_tenant_syncs.id = '" . ciniki_core_dbQuote($ciniki, $sync_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'sync');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sync']) || !is_array($rc['sync']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.219', 'msg'=>'Invalid sync'));
    }
    $sync = $rc['sync'];
    $sync['type'] = 'tenant';
    
    //
    // Setup logging
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLog');
    if( isset($ciniki['config']['ciniki.core']['sync.log_dir']) ) {
        $ciniki['synclogfile'] = $ciniki['config']['ciniki.core']['sync.log_dir'] . "/sync_" . $sync['sitename'] . "_$sync_id.log";
    }
    $ciniki['synclogprefix'] = '[' . $sync['sitename'] . '-' . $sync['remote_name'] . ']';

    //
    // Get the user uuidmaps
    //
    $strsql = "SELECT remote_uuid, local_id "
        . "FROM ciniki_tenant_sync_uuidmaps "
        . "WHERE ciniki_tenant_sync_uuidmaps.sync_id = '" . ciniki_core_dbQuote($ciniki, $sync['id']) . "' "
        . "AND table_name = 'ciniki_users' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.tenants', 'uuids');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['uuids']) ) {
        $sync['uuidmaps'] = array('ciniki_users'=>$rc['uuids']);
    } else {
        $sync['uuidmaps'] = array();
    }

    //
    // Setup cache
    // The uuidcache is for remote_uuid lookup cache
    // the idcache is for local_id lookup cache
    //
    $sync['uuidcache'] = array();
    $sync['idcache'] = array();

    return array('stat'=>'ok', 'sync'=>$sync);
}
?>
