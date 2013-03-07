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
function ciniki_core_syncSettingUpdate(&$ciniki, $sync, $business_id, $o, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '' ) 
		&& (!isset($args['object']) || $args['object'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'131', 'msg'=>'No setting specified'));
	}

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the remote setting to update
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>$o['pmod'] . '.' . $o['oname'] . '.get', 'uuid'=>$args['uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'980', 'msg'=>"Unable to get the remote setting", 'err'=>$rc['err']));
		}
		if( !isset($rc['object']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'285', 'msg'=>"setting not found on remote server"));
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectUpdateHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, $o['pmod']);
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get the local setting
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectGet');
	$rc = ciniki_core_syncObjectGet($ciniki, $sync, $business_id, $o, array('uuid'=>$remote_object['detail_key'], 'translate'=>'no'));
	if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'979', 'msg'=>'Unable to get ' . $o['name'] . ' setting', 'err'=>$rc['err']));
	}
	$db_updated = 0;
	$table = $o['table'];
	if( !isset($rc['object']) ) {
		$local_object = array();
		//
		// Add the setting if it doesn't exist locally
		//
		$strsql = "INSERT INTO $table (business_id, detail_key, detail_value, date_added, last_updated) "
			. "VALUES ('" . ciniki_core_dbQuote($ciniki, $business_id) . "'"
			. ", '" . ciniki_core_dbQuote($ciniki, $remote_object['detail_key']) . "'"
			. "";
			if( isset($o['refs']) && isset($o['refs'][$remote_object['detail_key']]) 
				&& isset($o['refs'][$remote_object['detail_key']]['ref'])
				&& $remote_object['detail_value'] != 0 ) {

				$ref = $o['refs'][$remote_object['detail_key']]['ref'];
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
				$rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $ref, array());
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1225', 'msg'=>'Unable to load object ' . $ref, 'err'=>$rc['err']));
				}
				$ref_o = $rc['object'];

				//
				// Lookup the object
				//
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
				$rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
					array('remote_uuid'=>$remote_object['detail_value']));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1226', 'msg'=>'Unable to find ' . $o['name'], 'err'=>$rc['err']));
				}
				$strsql .= ", '" . ciniki_core_dbQuote($ciniki, $rc['id']) . "' ";
			} else {
				$strsql .= ", '" . ciniki_core_dbQuote($ciniki, $remote_object['detail_value']) . "' ";
			}
//			. ", '" . ciniki_core_dbQuote($ciniki, $remote_object['detail_value']) . "'"
			$strsql .= ", FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_object['date_added']) . "') "
			. ", FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_object['last_updated']) . "') "
			. ")";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, $o['pmod']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, $o['pmod']);
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'976', 'msg'=>'Unable to get ' . $o['name'] . ' setting', 'err'=>$rc['err']));
		}
		$object_id = $remote_object['detail_key'];
		$db_updated = 1;
	} else {
		$local_object = $rc['object'];
		$object_id = $remote_object['detail_key'];
		// 
		// Update the existing setting
		//
//		$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_object, $local_object, array(
//			'detail_value'=>array(),
//			'date_added'=>array('type'=>'uts'),
//			'last_updated'=>array('type'=>'uts'),
//			));
//		if( $rc['stat'] != 'ok' ) {
//			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'977', 'msg'=>'Unable to update ' . $o['name'] . ' setting', 'err'=>$rc['err']));
//		}
		if( $remote_object['detail_value'] != $local_object['detail_value'] ) {
			$strsql = "UPDATE $table SET ";
			if( isset($o['refs']) && isset($o['refs'][$remote_object['detail_key']]) 
				&& isset($o['refs'][$remote_object['detail_key']]['ref'])
				&& $remote_object['detail_value'] != 0 ) {

				$ref = $o['refs'][$remote_object['detail_key']]['ref'];
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
				$rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $ref, array());
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1225', 'msg'=>'Unable to load object ' . $ref, 'err'=>$rc['err']));
				}
				$ref_o = $rc['object'];

				//
				// Lookup the object
				//
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
				$rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
					array('remote_uuid'=>$remote_object['detail_value']));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1226', 'msg'=>'Unable to find ' . $o['name'], 'err'=>$rc['err']));
				}
				$strsql .= "detail_value = '" . ciniki_core_dbQuote($ciniki, $rc['id']) . "' ";
			} else {
				$strsql .= "detail_value = '" . ciniki_core_dbQuote($ciniki, $remote_object['detail_value']) . "' ";
			}
			$strsql .= ", FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_object['last_updated']) . "') "
				. "WHERE detail_key = '" . ciniki_core_dbQuote($ciniki, $local_object['detail_key']) . "' "
				. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, $o['pmod']);
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'978', 'msg'=>'Unable to update ' . $o['name'] . ' setting', 'err'=>$rc['err']));
			}
			$db_updated = 1;
		}
	}

	//
	// Update the setting history
	//
	if( isset($remote_object['history']) ) {
		if( isset($local_object['history']) ) {
			$rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $business_id, $o, $object_id,
				$remote_object['history'], $local_object['history']);
//			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, $o['pmod'],
//				'ciniki_customer_history', $local_object['detail_key'], 'ciniki_customer_settings', $remote_object['history'], $local_object['history'], array());
		} else {
			$rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $business_id, $o, $object_id,
				$remote_object['history'], array());
//			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, $o['pmod'],
//				'ciniki_customer_history', $remote_object['detail_key'], 'ciniki_customer_settings', $remote_object['history'], array(), array());
		}
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'130', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
		}
	}

	// FIXME: Add check for deleted settings

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

	return array('stat'=>'ok');
}
?>
