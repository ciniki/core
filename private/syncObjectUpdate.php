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
function ciniki_core_syncObjectUpdate(&$ciniki, &$sync, $business_id, $o, $args) {
	//
	// Check for custom update function
	//
	if( isset($o['update']) && $o['update'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectFunction');
		return ciniki_core_syncObjectFunction($ciniki, $sync, $business_id, $o['update'], $args);
	}

	if( isset($o['type']) && $o['type'] == 'settings' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncSettingUpdate');
		return ciniki_core_syncSettingUpdate($ciniki, $sync, $business_id, $o, $args);
	}
	
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['object']) || $args['object'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1221', 'msg'=>'No ' . $o['name'] . ' specified'));
	}

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the remote object to update
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>$o['pmod'] . '.' . $o['oname'] . '.get', 'uuid'=>$args['uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1222', 'msg'=>'Unable to get the remote ' . $o['name'], 'err'=>$rc['err']));
		}
		if( !isset($rc['object']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1223', 'msg'=>$o['oname'] . ' not found on remote server'));
		}
		$remote_object = $rc['object'];
	} else {
		$remote_object = $args['object'];
	}

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectUpdateSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectUpdateHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, $o['pmod']);
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get the local object
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectGet');
	$rc = ciniki_core_syncObjectGet($ciniki, $sync, $business_id, $o, array('uuid'=>$remote_object['uuid'], 'translate'=>'no'));
	if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1224', 'msg'=>'Unable to get ' . $o['name'], 'err'=>$rc['err']));
	}
	$db_updated = 0;
	$table = $o['table'];
	if( !isset($rc['object']) ) {
		$local_object = array();
		// FIXME: Check if the object was deleted locally before adding

		//
		// Create the user record
		//
		$strsql = "INSERT INTO $table (uuid, business_id, ";
		foreach($o['fields'] as $fid => $finfo) {
			$strsql .= "$fid, ";
		}
		$strsql .= "date_added, last_updated) VALUES (";
		
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $remote_object['uuid']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', ";
		foreach($o['fields'] as $fid => $finfo) {
			if( isset($finfo['ref']) && $finfo['ref'] != '' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
				$rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $finfo['ref'], array());
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1225', 'msg'=>'Unable to load object ' . $finfo['ref'], 'err'=>$rc['err']));
				}
				$ref_o = $rc['object'];

				//
				// Lookup the object
				//
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
				$rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
					array('remote_uuid'=>$remote_object[$fid]));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1226', 'msg'=>'Unable to find ' . $o['name']));
				}
				$strsql .= "'" . ciniki_core_dbQuote($ciniki, $rc['id']) . "', ";
			} else {
				$strsql .= "'" . ciniki_core_dbQuote($ciniki, $remote_object[$fid]) . "', ";
			}
		}
		$strsql .= "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_object['date_added']) . "'), "
			. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_object['last_updated']) . "') "
			. ")";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
		$rc = ciniki_core_dbInsert($ciniki, $strsql, $o['pmod']);
		if( $rc['stat'] != 'ok' ) { 
			error_log(serialize($remote_object));
			ciniki_core_dbTransactionRollback($ciniki, $o['pmod']);
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1227', 'msg'=>'Unable to add ' . $o['name'], 'err'=>$rc['err']));
		}
		if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
			ciniki_core_dbTransactionRollback($ciniki, $o['pmod']);
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1228', 'msg'=>'Unable to add ' . $o['name']));
		}
		$object_id = $rc['insert_id'];
		$db_updated = 1;
	} else {
		$local_object = $rc['object'];
		$object_id = $rc['object']['id'];

		//
		// Compare basic elements of object
		//
		$rc = ciniki_core_syncObjectUpdateSQL($ciniki, $sync, $business_id, $o, $remote_object, $local_object);
//		$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_customer, $local_customer, array(
//			'cid'=>array(),
//			'type'=>array(),
//			'prefix'=>array(),
//			'first'=>array(),
//			'middle'=>array(),
//			'last'=>array(),
//			'suffix'=>array(),
//			'company'=>array(),
//			'department'=>array(),
//			'title'=>array(),
//			'phone_home'=>array(),
//			'phone_work'=>array(),
//			'phone_cell'=>array(),
//			'phone_fax'=>array(),
//			'notes'=>array(),
//			'birthdate'=>array(),
//			'date_added'=>array('type'=>'uts'),
//			'last_updated'=>array('type'=>'uts'),
//			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'963', 'msg'=>'Unable to update ' . $o['name'], 'err'=>$rc['err']));
		}
		if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
			$strsql = "UPDATE $table SET " . $rc['strsql'] . " "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_object['id']) . "' "
				. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, $o['pmod']);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, $o['pmod']);
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'964', 'msg'=>'Unable to update ' . $o['name'], 'err'=>$rc['err']));
			}
			$db_updated = 1;
		}
	}

	//
	// Update the object history
	//
	if( isset($remote_object['history']) ) {
		if( isset($local_object['history']) ) {
			$rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $business_id, $o, $object_id, 
				$remote_object['history'], $local_object['history']);
//			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
//				'ciniki_customer_history', $customer_id, 'ciniki_customers', $remote_customer['history'], $local_customer['history'], array());
		} else {
			$rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $business_id, $o, $object_id, 
				$remote_object['history'], array());
//			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
//				'ciniki_customer_history', $customer_id, 'ciniki_customers', $remote_customer['history'], array(), array());
		}
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, $o['pmod']);
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'229', 'msg'=>'Unable to save history for ' . $o['name'], 'err'=>$rc['err']));
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, $o['pmod']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Add to syncQueue to sync with other servers.  This allows for cascading syncs.
	//
	if( $db_updated > 0 ) {
		$ciniki['syncqueue'][] = array('push'=>$o['pmod'] . '.' . $o['oname'], 
			'args'=>array('id'=>$object_id, 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok', 'id'=>$object_id);
}
?>
