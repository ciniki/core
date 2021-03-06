<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_core_syncInfo($ciniki) {
    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.syncInfo');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Get the list of syncs setup for this tenant
    //
    $strsql = "SELECT ciniki_tenants.name AS tenant_name, "
        . "ciniki_tenants.uuid AS tenant_uuid, "
        . "ciniki_tenant_syncs.id AS id, ciniki_tenant_syncs.tnid, "
        . "ciniki_tenant_syncs.flags, ciniki_tenant_syncs.flags AS type, "
        . "ciniki_tenant_syncs.status, ciniki_tenant_syncs.status AS status_text, "
        . "remote_name, remote_url, remote_uuid, "
        . "IFNULL(DATE_FORMAT(last_sync, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "'), '') as last_sync, "
        . "IFNULL(DATE_FORMAT(last_partial, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "'), '') as last_partial, "
        . "IFNULL(DATE_FORMAT(last_full, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "'), '') as last_full, "
        . "(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(last_sync)) AS last_sync_age, "
        . "(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(last_partial)) AS last_partial_age, "
        . "(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(last_full)) AS last_full_age "
        . "FROM ciniki_tenant_syncs "
        . "LEFT JOIN ciniki_tenants ON (ciniki_tenant_syncs.tnid = ciniki_tenants.id) "
        . "ORDER BY ciniki_tenants.name, ciniki_tenant_syncs.remote_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'syncs', 'fname'=>'id', 'name'=>'sync',
            'fields'=>array('id', 'tnid', 'tenant_name', 'tenant_uuid', 'flags', 'type', 'status', 'status_text', 'remote_name', 'remote_url', 'remote_uuid',
                'last_sync', 'last_sync_age', 'last_partial', 'last_partial_age', 'last_full', 'last_full_age'),
            'maps'=>array('status_text'=>array('10'=>'Active', '60'=>'Suspended'),
                'type'=>array('1'=>'Push', '2'=>'Pull', '3'=>'Bi'))),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Decide what status each sync should have
    //
    $syncs = array();
    if( isset($rc['syncs']) ) {
        foreach($rc['syncs'] as $sid => $sync) {
            $alert = '';
            $warn = '';
            if( $sync['sync']['last_sync_age'] > 1800 ) {
                // 30 minutes
                $rc['syncs'][$sid]['sync']['last_sync_status'] = 'alert';
                $alert = 'alert';
            } elseif( $sync['sync']['last_sync_age'] > 600 ) {
                // 10 minutes
                $rc['syncs'][$sid]['sync']['last_sync_status'] = 'warn';
                $warn = 'warn';
            } else {
                $rc['syncs'][$sid]['sync']['last_sync_status'] = 'ok';
            }
            if( $sync['sync']['last_partial_age'] > 180000 ) {
                // 2 days 1 hour old
                $rc['syncs'][$sid]['sync']['last_partial_status'] = 'alert';
                $alert = 'alert';
            } elseif( $sync['sync']['last_partial_age'] > 90000 ) { 
                // 1 day 1 hour old
                $rc['syncs'][$sid]['sync']['last_partial_status'] = 'warn';
                $warn = 'warn';
            } else {
                $rc['syncs'][$sid]['sync']['last_partial_status'] = 'ok';
            }
            if( $sync['sync']['last_full_age'] > 734400 ) {
                // 8 days 12 hours
                $rc['syncs'][$sid]['sync']['last_full_status'] = 'alert';
                $alert = 'alert';
            } elseif( $sync['sync']['last_full_age'] > 648000 ) {
                // 7 days 12 hours
                $rc['syncs'][$sid]['sync']['last_full_status'] = 'warn';
                $warn = 'warn';
            } else {
                $rc['syncs'][$sid]['sync']['last_full_status'] = 'ok';
            }

            if( $alert != '' ) {
                $rc['syncs'][$sid]['sync']['sync_status'] = $alert;
            } elseif( $warn != '' ) {
                $rc['syncs'][$sid]['sync']['sync_status'] = $warn;
            } else {
                $rc['syncs'][$sid]['sync']['sync_status'] = 'ok';
            }
        }
        $syncs = $rc['syncs'];
    }
    
    return array('stat'=>'ok', 'name'=>$ciniki['config']['core']['sync.name'], 'local_url'=>$ciniki['config']['core']['sync.url'], 'syncs'=>$syncs);
}
?>
