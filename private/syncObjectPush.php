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

	if( isset($ciniki['config']['ciniki.core']['sync.push']) && $ciniki['config']['ciniki.core']['sync.push'] == 'off' ) {
		ciniki_core_syncLog($ciniki, 1, "Push turned off", null);
		return array('stat'=>'ok');
	}

	//
	// Get the local object
	//
	if( isset($args['id']) && $args['id'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectGet');
		$rc = ciniki_core_syncObjectGet($ciniki, $sync, $business_id, $o, $args);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1166', 'msg'=>'Unable to get ' . $o['name']));
		}
		if( !isset($rc['object']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1167', 'msg'=>$o['name'] . ' not found on remote server'));
		}
		$object = $rc['object'];

		//
		// Update the remote object
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>$o['pmod'] . '.' . $o['oname'] . '.update', 'object'=>$object));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1175', 'msg'=>'Unable to sync ' . $o['name']));
		}

		return array('stat'=>'ok');
	}

	elseif( isset($args['delete_uuid']) ) {
		if( !isset($args['history']) || !is_array($args['history']) || count($args['history']) == 0 ) {
			if( isset($args['delete_id']) ) {
				//
				// Grab the history for the latest delete
				//
				$history_table = $o['history_table'];
				$strsql = "SELECT "
					. "$history_table.uuid AS uuid, "
					. "ciniki_users.uuid AS user, "
					. "$history_table.action, "
					. "$history_table.session, "
					. "$history_table.table_field, "
					. "$history_table.new_value, "
					. "UNIX_TIMESTAMP($history_table.log_date) AS log_date "
					. "FROM $history_table "
					. "LEFT JOIN ciniki_users ON ($history_table.user_id = ciniki_users.id) "
					. "WHERE $history_table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND $history_table.table_name = '" . ciniki_core_dbQuote($ciniki, $o['table']) . "' "
					. "AND $history_table.action = 3 "
					. "AND $history_table.table_key = '" . ciniki_core_dbQuote($ciniki, $args['delete_id']) . "' "
					. "AND $history_table.table_field = '*' "
					. "ORDER BY log_date DESC "
					. "LIMIT 1 ";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $o['pmod'], 'history');
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1171', 'msg'=>'Unable to sync ' . $o['name'], 'err'=>$rc['err']));
				}
				if( isset($rc['history']) ) {
					$history = $rc['history'];
				} else {
					$history = array();
				}
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
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>$o['pmod'] . '.' . $o['oname'] . '.delete', 'uuid'=>$args['delete_uuid'], 'history'=>$history));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1174', 'msg'=>'Unable to sync ' . $o['name']));
		}
		return array('stat'=>'ok');
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1173', 'msg'=>'Missing ID argument'));
}
?>
