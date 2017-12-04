<?php
//
// Description
// -----------
// This method will return the list of tenants to backup.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
//
//
function ciniki_core_backupTenantList($ciniki) {

    $strsql = "SELECT ciniki_tenants.id, ciniki_tenants.uuid, ciniki_tenants.name "
        . "FROM ciniki_tenants, ciniki_tenant_modules "
        . "WHERE ciniki_tenants.status = 1 "
        . "AND ciniki_tenants.id = ciniki_tenant_modules.tnid "
        . "AND ciniki_tenant_modules.package = 'ciniki' "
        . "AND ciniki_tenant_modules.module = 'tenants' "
        . "AND (ciniki_tenant_modules.flags&0x020000) > 0 "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'tenants', 'fname'=>'id',
            'fields'=>array('id', 'uuid', 'name')),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return $rc;
}
?>
