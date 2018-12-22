<?php
//
// Description
// -----------
// This function will add a file to the ciniki-storage directory structure.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_core_storageFileAdd(&$ciniki, $tnid, $obj_name, $args) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    //
    // Get the tenant UUID
    //
    $strsql = "SELECT uuid FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.170', 'msg'=>'Unable to get tenant details'));
    }
    $tenant_uuid = $rc['tenant']['uuid'];

    //
    // Get a new UUID
    //
    if( !isset($args['uuid']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, "$pkg.$mod");
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $uuid = $rc['uuid'];
    } else {
        $uuid = $args['uuid'];
    }

    //
    // Move the file to ciniki-storage
    //
    $storage_dirname = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
        . $tenant_uuid[0] . '/' . $tenant_uuid
        . "/$pkg.$mod/"
        . (isset($args['subdir']) && $args['subdir'] != '' ? $args['subdir'] . '/' : '')
        . $uuid[0];
    $storage_filename = $storage_dirname . '/' . $uuid;

    if( !is_dir($storage_dirname) ) {
        if( !mkdir($storage_dirname, 0700, true) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.171', 'msg'=>'Unable to add file'));
        }
    }
    if( !rename($_FILES['uploadfile']['tmp_name'], $storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.172', 'msg'=>'Unable to add file'));
    }

    return array('stat'=>'ok', 'uuid'=>$uuid);
}
?>
