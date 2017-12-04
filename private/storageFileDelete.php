<?php
//
// Description
// -----------
// This function will remove a file from the ciniki-storage system for a tenant.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_core_storageFileDelete(&$ciniki, $tnid, $obj_name, $args) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    if( !isset($args['uuid']) || $args['uuid'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.173', 'msg'=>'No uuid specified to remove from storage.'));
    }

    //
    // Get the tenant UUID
    //
    if( !isset($args['tenant_uuid']) ) {
        $strsql = "SELECT uuid FROM ciniki_tenants "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['tenant']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.174', 'msg'=>'Unable to get tenant details'));
        }
        $tenant_uuid = $rc['tenant']['uuid'];
    } else {
        $tenant_uuid = $args['tenant_uuid'];
    }

    //
    // remove the file from ciniki-storage
    //
    $storage_filename = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
        . $tenant_uuid[0] . '/' . $tenant_uuid
        . "/$pkg.$mod/"
        . (isset($args['subdir']) && $args['subdir'] != '' ? $args['subdir'] . '/' : '')
        . $args['uuid'][0] . '/' . $args['uuid'];
    if( file_exists($storage_filename) ) {
        unlink($storage_filename);
    }

    return array('stat'=>'ok');
}
?>
