<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant the reference is for.
//
// args:            The arguments for adding the reference.
//
//                  object - The object that is referring to the object.
//                  object_id - The ID of the object that is referrign to the object.
//
// Returns
// -------
// <rsp stat="ok" id="45" />
//
function ciniki_core_objectRefClear(&$ciniki, $tnid, $obj_name, $args, $options=0) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    //
    // Check if there is a reference table for module being referred to
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');
    $rc = ciniki_core_objectLoad($ciniki, $pkg . '.' . $mod . '.ref');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok');
    }

    $o = $rc['object'];
    $m = "$pkg.$mod";

    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.116', 'msg'=>'No reference object specified'));
    }
    if( !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.117', 'msg'=>'No reference object id specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');


    //
    // Grab the uuid of the reference
    //
    $strsql = "SELECT id, uuid FROM " . $o['table'] . " "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
        . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, $m, 'ref');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.core.118', 'msg'=>'Reference does not exist'));
    }
    $refs = $rc['rows'];

    foreach($refs as $rowid => $ref) {
        $strsql = "DELETE FROM " . $o['table'] . " "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $ref['id']) . "' "
            . "";
        $rc = ciniki_core_dbDelete($ciniki, $strsql, $m);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.119', 'msg'=>'Unable to remove object reference', 'err'=>$rc['err'])); 
        }
        ciniki_core_dbAddModuleHistory($ciniki, $m, $o['history_table'], 
            $tnid, 3, $o['table'], $ref['id'], '*', '');
        $ciniki['syncqueue'][] = array('push'=>"$pkg.$mod.ref",
            'args'=>array('delete_uuid'=>$ref['uuid'], 'delete_id'=>$ref['id']));
        
        //
        // FIXME: Add check for number of remaining references, possibly delete object
        //
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, $pkg, $mod);

    return array('stat'=>'ok');
}
?>
