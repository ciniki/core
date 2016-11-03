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
function ciniki_core_storageFileAdd(&$ciniki, $business_id, $obj_name, $args) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    //
    // Get the business UUID
    //
    $strsql = "SELECT uuid FROM ciniki_businesses "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['business']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.170', 'msg'=>'Unable to get business details'));
    }
    $business_uuid = $rc['business']['uuid'];

    //
    // Get a new UUID
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, "$pkg.$mod");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $uuid = $rc['uuid'];

    //
    // Move the file to ciniki-storage
    //
    $storage_dirname = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
        . $business_uuid[0] . '/' . $business_uuid
        . "/$pkg.$mod/"
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
