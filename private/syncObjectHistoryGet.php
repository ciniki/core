<?php
//
// Description
// -----------
// This function will update the history elements.
//
// Arguments
// ---------
//
function ciniki_core_syncObjectHistoryGet(&$ciniki, &$sync, $business_id, $o, $args) {
	//
	// Check for custom history get function
	//
	if( isset($o['history_get']) && $o['history_get'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectFunction');
		return ciniki_core_syncObjectFunction($ciniki, $sync, $business_id, $o['history_get'], $args);
	}

	if( !isset($args['uuid']) || $args['uuid'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1184', 'msg'=>'No uuid specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Get the history information
	//
	$history_table = $o['history_table'];
	$strsql = "SELECT $history_table.id AS history_id, "
		. "$history_table.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "$history_table.session, "
		. "$history_table.action, "
		. "$history_table.table_name, "
		. "$history_table.table_key, "
		. "$history_table.table_field, "
		. "$history_table.new_value, "
		. "UNIX_TIMESTAMP($history_table.log_date) AS log_date "
		. "FROM $history_table "
		. "LEFT JOIN ciniki_users ON ($history_table.user_id = ciniki_users.id) "
		. "WHERE $history_table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND $history_table.uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
		. "ORDER BY log_date "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, $o['pmod'], array(
		array('container'=>'history', 'fname'=>'history_uuid', 
			'fields'=>array('uuid'=>'history_uuid', 'user'=>'user_uuid', 'session', 
				'action', 'table_name', 'table_key', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1185', 'msg'=>"Unable to get " . $o['pmod'] . " history", 'err'=>$rc['err']));
	}
	if( !isset($rc['history'][$args['uuid']]) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1186', 'msg'=>$o['pmod'] . " history does not exist: " . $args['uuid']));
	}
	$history = $rc['history'][$args['uuid']];

	//
	// Translate the table_key (eg: ciniki_customer_relationships.id) into a uuid
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
	$rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $o, array('local_id'=>$history['table_key']));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1187', 'msg'=>$o['pmod'] . " history table key does not exist: " . $history['table_key']));
	}
	$history['table_key'] = $rc['uuid'];

	//
	// Translate the new_value into a uuid if required
	//
	if( isset($o['fields'][$history['table_field']]) && isset($o['fields'][$history['table_field']]['ref']) ) {
		$ref = $o['fields'][$history['table_field']]['ref'];
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
		$rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $ref, array());
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1188', 'msg'=>'Unable to load object ' . $ref));
		}
		$ref_o = $rc['object'];
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
		$rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
			array('local_id'=>$history['new_value']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1189', 'msg'=>'Unable to find reference for ' . $ref_o['name'] . '(' . $history['new_value'] . ')', 'err'=>$rc['err']));
		}
		$history['new_value'] = $rc['uuid'];
	}

	return array('stat'=>'ok', 'object'=>$history);
}
?>
