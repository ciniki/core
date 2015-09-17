<?php
//
// Description
// ===========
// This function will update the sequences for themes.
//
// Arguments
// =========
// ciniki:
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_core_sequencesUpdate($ciniki, $business_id, $obj_name, $id_field, $id_value, $new_seq, $old_seq) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');

	//
	// Load the object
	//
	list($pkg, $mod, $obj) = explode('.', $obj_name);
	$rc = ciniki_core_objectLoad($ciniki, $obj_name);
	if( $rc['stat'] != 'ok' ) {	
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2547', 'msg'=>'Invalid object'));
	}
	$object = $rc['object'];
	$m = "$pkg.$mod";

	//
	// Get the sequences
	//
	$strsql = "SELECT id, sequence AS number "
		. "FROM " . $object['table'] . " "
		. "WHERE $id_field = '" . ciniki_core_dbQuote($ciniki, $id_value) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	// Use the last_updated to determine which is in the proper position for duplicate numbers
	if( $new_seq < $old_seq || $old_seq == -1) {
		$strsql .= "ORDER BY sequence, last_updated DESC";
	} else {
		$strsql .= "ORDER BY sequence, last_updated ";
	}
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $m, 'sequence');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, $m);
		return $rc;
	}
	$cur_number = 1;
	if( isset($rc['rows']) ) {
		$sequences = $rc['rows'];
		foreach($sequences as $sid => $seq) {
			//
			// If the number is not where it's suppose to be, change
			//
			if( $cur_number != $seq['number'] ) {
				$strsql = "UPDATE " . $object['table'] . " SET "
					. "sequence = '" . ciniki_core_dbQuote($ciniki, $cur_number) . "' "
					. ", last_updated = UTC_TIMESTAMP() "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND id = '" . ciniki_core_dbQuote($ciniki, $seq['id']) . "' "
					. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, $m);
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, $m);
				}
				ciniki_core_dbAddModuleHistory($ciniki, $m, $object['history_table'], $business_id, 
					2, $object['table'], $seq['id'], 'sequence', $cur_number);
				$ciniki['syncqueue'][] = array('push'=>$obj_name, 'args'=>array('id'=>$seq['id']));
				
			}
			$cur_number++;
		}
	}
	
	return array('stat'=>'ok');
}
?>
