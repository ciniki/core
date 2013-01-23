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
function ciniki_core_syncObjectGet($ciniki, &$sync, $business_id, $o, $args) {
	//
	// Check for custom get function
	//
	if( isset($o['get']) && $o['get'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectFunction');
		return ciniki_core_syncObjectFunction($ciniki, $sync, $business_id, $o['get'], $args);
	}

	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['id']) || $args['id'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1203', 'msg'=>'No customer specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Build the SQL string to get the object details along with history information
	//
	$table = $o['table'];
	$strsql = "SELECT $table.id, $table.uuid AS object_uuid, ";
	foreach($o['fields'] as $fid => $finfo) {
		$strsql .= "$table.$fid, ";
	}
	$strsql .= "UNIX_TIMESTAMP($table.date_added) AS date_added, "
		. "UNIX_TIMESTAMP($table.last_updated) AS last_updated, ";

	$history_table = $o['history_table'];
	$strsql .= "$history_table.id AS history_id, "
		. "$history_table.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "$history_table.session, "
		. "$history_table.action, "
		. "$history_table.table_field, "
		. "$history_table.new_value, "
		. "UNIX_TIMESTAMP($history_table.log_date) AS log_date ";

	$strsql .= "FROM $table "
		. "LEFT JOIN $history_table ON ($table.id = $history_table.table_key "
			. "AND $history_table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND $history_table.table_name = '$table' "
			. ") "
		. "LEFT JOIN ciniki_users ON ($history_table.user_id = ciniki_users.id) ";

	$strsql .= "WHERE $table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		$strsql .= "AND $table.uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' ";
	} elseif( isset($args['id']) && $args['id'] != '' ) {
		$strsql .= "AND $table.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1195', 'msg'=>'No ' . $o['name'] . ' specified'));
	}
	$strsql .= "ORDER BY log_date ";

	$fields = array_keys($o['fields']);
	$fields['uuid'] = 'object_uuid';
	$fields['id'] = 'id';
	$fields['date_added'] = 'date_added';
	$fields['last_updated'] = 'last_updated';

	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, $o['pmod'], array(
		array('container'=>'objects', 'fname'=>'object_uuid', 
			'fields'=>$fields),
		array('container'=>'history', 'fname'=>'history_uuid', 
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1196', 'msg'=>'Error retrieving the ' . $o['name'] . ' information', 'err'=>$rc['err']));
	}

	//
	// Check that one and only one row was returned
	//
	if( !isset($rc['objects']) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1197', 'msg'=>$o['name'] . ' does not exist'));
	}
	if( count($rc['objects']) > 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1198', 'msg'=>$o['name'] . ' does not exist'));
	}
	$object = array_pop($rc['objects']);

	if( !isset($object['history']) ) {
		$object['history'] = array();
	}

	if( !isset($args['translate']) || $args['translate'] == 'yes' ) {
		//
		// Translate the table_key
		//
		foreach($o['fields'] as $fid => $finfo) {
			if( isset($finfo['ref']) && $finfo['ref'] != '' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
				$rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $finfo['ref'], array());
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1199', 'msg'=>'Unable to load object ' . $finfo['ref']));
				}
				$ref_o = $rc['object'];
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
				$rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
					array('local_id'=>$object[$fid]));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1200', 'msg'=>'Unable to find reference for ' . $ref_o['name'] . '(' . $object[$fid] . ')'));
				}
				$object[$fid] = $rc['uuid'];
			}
		}

		//
		// Translate the new_value if required
		//
		foreach($object['history'] as $uuid => $history) {
			if( isset($o['fields'][$history['table_field']]) && isset($o['fields'][$history['table_field']]['ref']) ) {
				$ref = $o['fields'][$history['table_field']]['ref'];
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
				$rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $ref, array());
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1201', 'msg'=>'Unable to load object ' . $ref));
				}
				$ref_o = $rc['object'];
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
				$rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
					array('local_id'=>$history['new_value']));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1202', 'msg'=>'Unable to find reference for ' . $ref_o['name'] . '(' . $history['new_value'] . ')', 'err'=>$rc['err']));
				}
				$object['history'][$uuid]['new_value'] = $rc['uuid'];
			}
		}
	}

	return array('stat'=>'ok', 'object'=>$object);
}
?>
