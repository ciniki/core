<?php
//
// Description
// -----------
// This function will add an object to the database.
//
// Arguments
// ---------
// ciniki:
// pkg:			The package the object is a part of.
// mod:			The module the object is a part of.
// obj:			The name of the object in the module.
// args:		The arguments passed to the API.
// tmsupdate:	The default is yes, and it will create a transaction,
//				update the module last_change date, and insert
//				into the sync queue.
//				
//				0x01 - run in a transaction
//				0x02 - update the module last change date
//				0x04 - Insert into sync queue
//
// Returns
// -------
//
function ciniki_core_objectUpdate(&$ciniki, $business_id, $obj_name, $oid, $args, $tmsupdate=0x07) {
	//
	// Break apart object name
	//
	list($pkg, $mod, $obj) = explode('.', $obj_name);

	//
	// Load the object file
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');
	$rc = ciniki_core_objectLoad($ciniki, $obj_name);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$o = $rc['object'];
	$m = "$pkg.$mod";

	//
	// Start transaction
	//
	if( ($tmsupdate&0x01) == 1 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
		$rc = ciniki_core_dbTransactionStart($ciniki, $m);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Build the SQL string to update object
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$strsql = "UPDATE " . $o['table'] . " SET last_updated = UTC_TIMESTAMP()";
	foreach($o['fields'] as $field => $options) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
		}
	}
	$strsql .= " WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $oid) . "' "
		. "";

	//
	// Update the object
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, $m);
	if( $rc['stat'] != 'ok' ) {	
		if( ($tmsupdate&0x01) == 1 ) { ciniki_core_dbTransactionRollback($ciniki, $m); }
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		if( ($tmsupdate&0x01) == 1 ) { ciniki_core_dbTransactionRollback($ciniki, $m); }
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1344', 'msg'=>'Unable to update object'));
	}

	//
	// Add the history
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefClear');
	foreach($o['fields'] as $field => $options) {
		if( isset($args[$field]) && (!isset($options['history']) || $options['history'] == 'yes') ) {
			ciniki_core_dbAddModuleHistory($ciniki, $m, $o['history_table'], $business_id,
				2, $o['table'], $oid, $field, $args[$field]);
		}
		//
		// Check if this column is a reference to another modules object, 
		// and see if there should be a reference updated
		//
		if( isset($options['ref']) && $options['ref'] != '' && isset($args[$field]) ) {
			//
			// Clear any old refs
			//
			$rc = ciniki_core_objectRefClear($ciniki, $business_id, $options['ref'], array(
				'object'=>$obj_name,			// The local object ref (this objects ref)
				'object_id'=>$oid,				// The local object ID
				));
			if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
				return $rc;
			}
			//
			// Add the new ref 
			//
			if( $args[$field] != '' && $args[$field] > 0 ) {
				$rc = ciniki_core_objectRefAdd($ciniki, $business_id, $options['ref'], array(
					'ref_id'=>$args[$field],		// The remote ID (other modules object id)
					'object'=>$obj_name,			// The local object ref (this objects ref)
					'object_id'=>$oid,		// The local object ID
					'object_field'=>$field,			// The local object table field name of remote ID
					));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}
	}

	//
	// Commit the transaction
	//
	if( ($tmsupdate&0x01) == 1 ) {
		$rc = ciniki_core_dbTransactionCommit($ciniki, $m);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Update the last change date of the module
	//
	if( ($tmsupdate&0x02) == 2 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
		ciniki_businesses_updateModuleChangeDate($ciniki, $business_id, $pkg, $mod);
	}

	//
	// Add the change to the sync queue
	//
	if( ($tmsupdate&0x04) == 4 ) {
		$ciniki['syncqueue'][] = array('push'=>$obj_name, 'args'=>array('id'=>$oid));
	}	

	return array('stat'=>'ok');
}
?>
