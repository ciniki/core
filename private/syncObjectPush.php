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
function ciniki_core_syncObjectPush(&$ciniki, &$sync, $business_id, $o, $args) {
	//
	// Check for custom push function
	//
	if( isset($o['push']) && $o['push'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectFunction');
		return ciniki_core_syncObjectFunction($ciniki, $sync, $business_id, $o['push'], $args);
	}

	//
	// Get the local object
	//
	if( isset($args['id']) && $args['id'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectGet');
		$rc = ciniki_core_syncObjectGet($ciniki, $sync, $business_id, $o, $args);
//		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customer_get');
//		$rc = ciniki_customers_customer_get($ciniki, $sync, $business_id, array('id'=>$args['id']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'110', 'msg'=>'Unable to get ' . $o['name']));
		}
		if( !isset($rc['object']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'108', 'msg'=>$o['name'] . ' not found on remote server'));
		}
		$object = $rc['object'];

		//
		// Update the remote object
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>$o['pmod'] . '.' . $o['oname'] . '.update', 'object'=>$object));
//		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.customer.update', 'customer'=>$customer));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'111', 'msg'=>'Unable to sync ' . $o['name']));
		}

		return array('stat'=>'ok');
	}

	elseif( isset($args['delete_uuid']) ) {
		if( !isset($args['history']) ) {
			if( isset($args['delete_id']) ) {
				//
				// Grab the history for the latest delete
				//
				$table = $o['history_table'];
				$strsql = "SELECT "
					. "$table.uuid AS uuid, "
					. "ciniki_users.uuid AS user, "
					. "$table.action, "
					. "$table.session, "
					. "$table.table_field, "
					. "$table.new_value, "
					. "UNIX_TIMESTAMP($table.log_date) AS log_date "
					. "FROM $table "
					. "LEFT JOIN ciniki_users ON ($table.user_id = ciniki_users.id) "
					. "WHERE $table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND $table.table_name = 'ciniki_customers' "
					. "AND $table.action = 3 "
					. "AND $table.table_key = '" . ciniki_core_dbQuote($ciniki, $args['delete_id']) . "' "
					. "AND $table.table_field = '*' "
					. "ORDER BY log_date DESC "
					. "LIMIT 1 ";
//				$strsql = "SELECT "
//					. "ciniki_customer_history.uuid AS uuid, "
//					. "ciniki_users.uuid AS user, "
//					. "ciniki_customer_history.action, "
//					. "ciniki_customer_history.session, "
//					. "ciniki_customer_history.table_field, "
//					. "ciniki_customer_history.new_value, "
//					. "UNIX_TIMESTAMP(ciniki_customer_history.log_date) AS log_date "
//					. "FROM ciniki_customer_history "
//					. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) "
//					. "WHERE ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//					. "AND ciniki_customer_history.table_name = 'ciniki_customers' "
//					. "AND ciniki_customer_history.action = 3 "
//					. "AND ciniki_customer_history.table_key = '" . ciniki_core_dbQuote($ciniki, $args['delete_id']) . "' "
//					. "AND ciniki_customer_history.table_field = '*' "
//					. "ORDER BY log_date DESC "
//					. "LIMIT 1 ";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $o['pmod'], 'history');
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1090', 'msg'=>'Unable to sync ' . $o['name'], 'err'=>$rc['err']));
				}
				$history = $rc['history'];
			} else {
				$history = array();
			}
		} else {
			$history = $args['history'];
		}

		//
		// Delete the remote object 
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>$o['pmod'], . '.' . $o['oname'] . '.delete', 'uuid'=>$delete['uuid'], 'history'=>$history));
//		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.customer.delete', 'uuid'=>$args['delete_uuid'], 'history'=>$history));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1164', 'msg'=>'Unable to sync ' . $o['name']));
		}
		return array('stat'=>'ok');
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'107', 'msg'=>'Missing ID argument'));
}
?>
