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
function ciniki_core_syncObjectLookup(&$ciniki, &$sync, $business_id, $o, $args) {
	//
	// Check for custom lookup function
	//
	if( isset($o['lookup']) && $o['lookup'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectFunction');
		return ciniki_core_syncObjectFunction($ciniki, $sync, $business_id, $o['lookup'], $args);
	}
	
	//
	// Check the args
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

	//
	// Look for the user based on the UUID, and if not found make a request to
	// add from remote side
	//
	$table = $o['table'];
	$history_table = $o['history_table'];
	if( isset($args['remote_uuid']) && $args['remote_uuid'] != '' ) {
		$strsql = "SELECT id FROM $table "
			. "WHERE $table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND $table.uuid = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $o['pmod'], 'object');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1204', 'msg'=>"Unable to get the " . $o['name'] . " id", 'err'=>$rc['err']));
		}
		if( isset($rc['object']) ) {
			return array('stat'=>'ok', 'id'=>$rc['object']['id']);
		}
		
		//
		// If the id was not found in the objects table, try looking up in the history
		//
		$strsql = "SELECT table_key FROM $history_table "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND action = 1 "
			. "AND table_name = '" . ciniki_core_dbQuote($ciniki, $table) . "' "
			. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "AND table_field = 'uuid' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $o['pmod'], 'object');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1205', 'msg'=>'Unable to get ' . $o['name'] . ' id from history', 'err'=>$rc['err']));
		}
		if( isset($rc['object']) ) {
			return array('stat'=>'ok', 'id'=>$rc['object']['table_key']);
		}

		//
		// Check to see if it exists on the remote side, and add object if necessary
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, $business_id, array('method'=>$o['pmod'] . '.' . $o['oname'] . '.get', 'uuid'=>$args['remote_uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1206', 'msg'=>'Unable to get ' . $o['name'] . ' from remote server', 'err'=>$rc['err']));
		}

		if( isset($rc['object']) ) {
			$rc = ciniki_core_syncObjectUpdate($ciniki, $sync, $business_id, $o, array('object'=>$rc['object']));
//			$rc = ciniki_customers_customer_update($ciniki, $sync, $business_id, array('customer'=>$rc['customer']));
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1207', 'msg'=>'Unable to add ' . $o['name'] . ' to local server', 'err'=>$rc['err']));
			}
			return array('stat'=>'ok', 'id'=>$rc['object']['id']);
		}

		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1208', 'msg'=>'Unable to find ' . $o['name']));
	}

	//
	// If requesting the local_id, the lookup in local database, don't bother with remote,
	// ID won't be there.
	//
	elseif( isset($args['local_id']) && $args['local_id'] != '' ) {
		$strsql = "SELECT uuid FROM $table "
			. "WHERE $table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND $table.id = '" . ciniki_core_dbQuote($ciniki, $args['local_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $o['pmod'], 'object');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1209', 'msg'=>"Unable to get the " . $o['name'] . " uuid", 'err'=>$rc['err']));
		}
		if( isset($rc['object']) ) {
			return array('stat'=>'ok', 'uuid'=>$rc['object']['uuid']);
		}
		
		//
		// If the id was not found in the customers table, try looking up in the history from when it was added
		//
		$strsql = "SELECT new_value FROM $history_table "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND action = 1 "
			. "AND table_name = '$table' "
			. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $args['local_id']) . "' "
			. "AND table_field = 'uuid' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $o['pmod'], 'object');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1210', 'msg'=>'Unable to get ' . $o['name'] . ' id from history', 'err'=>$rc['err']));
		}
		if( isset($rc['object']) ) {
			return array('stat'=>'ok', 'uuid'=>$rc['object']['new_value']);
		}
		
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1211', 'msg'=>'Unable to find ' . $o['name']));
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1212', 'msg'=>'No ' . $o['name'] . ' specified'));
}
?>
