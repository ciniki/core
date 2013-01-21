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
//
function ciniki_core_syncObjectDelete(&$ciniki, &$sync, $business_id, $o, $args) {
	//
	// Check for custom delete function
	//
	if( isset($o['delete']) && $o['delete'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectFunction');
		return ciniki_core_syncObjectFunction($ciniki, $sync, $business_id, $o['delete'], $args);
	}

	//
	// Check the args
	//
	if( !isset($args['uuid']) || $args['uuid'] == '' 
		|| !isset($args['history']) || $args['history'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1086', 'msg'=>'No ' . $o['name'] . ' specified'));
	}
	$uuid = $args['uuid'];
	$history = $args['history'];

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the local object to delete
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectGet');
		$rc = ciniki_core_syncObjectGet($ciniki, $sync, $business_id, $o, array('uuid'=>$args['uuid'], 'translate'=>'no'));
		if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1087', 'msg'=>'Unable to get ' . $o['name'], 'err'=>$rc['err']));
		}
		if( !isset($rc['object']) ) {
			// Already deleted
			return array('stat'=>'ok');
		}
		$local_object = $rc['object'];
	}

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, $o['pmod']);
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	$db_updated = 0;

	//
	// Remove from the local server
	//
	$table = $o['table'];
	$strsql = "DELETE FROM $table "
		. "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, $o['pmod']);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1088', 'msg'=>'Unable to delete the local ' . $o['name'], 'err'=>$rc['err']));
	}
	if( $rc['num_affected_rows'] > 0 ) {
		$db_updated = 1;
	}

	//
	// Update history
	//
	if( isset($local_object['history']) ) {
		$rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $business_id, $o, $local_object['id'], 
			array($history['uuid']=>$history), array());
//		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, $o['pmod'],
//			$o['history']['table'], $local_object['id'], $o['table'], array($history['uuid']=>$history), $local_object['history'], array(
//				'customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
//				'related_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
//			));
	} else {
		$rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $business_id, $o, $local_object['id'], 
			array($history['uuid']=>$history), array());
//		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
//			'ciniki_customer_history', $local_customer['id'], 'ciniki_customer_customers', array($history['uuid']=>$history), array(), array(
//				'customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
//				'related_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
//			));
	}
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, $o['pmod']);
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1089', 'msg'=>'Unable to update ' . $o['name'] . ' history', 'err'=>$rc['err']));
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, $o['pmod']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Add to syncQueue to sync with other servers.  This allows for cascading syncs.  Don't need to
	// include the delete_id because the history is already specified.
	//
	if( $db_updated > 0 ) {
		$ciniki['syncqueue'][] = array('push'=>$o['pmod'] . '.' . $o['oname'], 
			'args'=>array('delete_uuid'=>$args['uuid'], 'history'=>$args['history'], 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok');
}
?>
