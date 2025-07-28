<?php
//
// Description
// ===========
// This function will find the next sequence number for the object
//
// Arguments
// =========
// ciniki:
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_core_sequencesNext($ciniki, $tnid, $obj_name, $id_field, $id_value) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');

    //
    // Load the object
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);
    $rc = ciniki_core_objectLoad($ciniki, $obj_name);
    if( $rc['stat'] != 'ok' ) { 
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.141', 'msg'=>'Invalid object'));
    }
    $object = $rc['object'];
    $m = "$pkg.$mod";

    //
    // Get the next sequence
    //
    $strsql = "SELECT MAX(sequence) AS max_sequence "
        . "FROM " . $object['table'] . " "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( $id_field != null && $id_field != '' ) {
        $strsql .= "AND $id_field = '" . ciniki_core_dbQuote($ciniki, $id_value) . "' ";
    }
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, $m, 'seq');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['seq']) && isset($rc['seq']['max_sequence']) ) {
        return array('stat'=>'ok', 'sequence'=>$rc['seq']['max_sequence'] + 1);
    } 

    return array('stat'=>'ok', 'sequence'=>1);
}
?>
