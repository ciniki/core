<?php
//
// Description
// -----------
// This function will check if an object exists.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant.
// object:          The name of the object to check.
// object_id:       The ID of the object to check for existence.
//
// Returns
// -------
//
function ciniki_core_objectCheckExists(&$ciniki, $tnid, $object, $object_id) {
    //
    // Break apart object
    //
    list($pkg, $mod, $obj) = explode('.', $object);

    //
    // Load the object file
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');
    $rc = ciniki_core_objectLoad($ciniki, $object);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $o = $rc['object'];
    $m = "$pkg.$mod";

    //
    // Query for the object id
    //
    $strsql = "SELECT id "
        . "FROM " . $o['table'] . " "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, $m, 'object');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['object']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.core.102', 'msg'=>'Object does not exist'));
    }

    return array('stat'=>'ok');
}
?>
