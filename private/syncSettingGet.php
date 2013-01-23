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
function ciniki_core_syncSettingGet($ciniki, $sync, $business_id, $o, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '' )
		&& (!isset($args['setting']) || $args['setting'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'153', 'msg'=>'No setting specified'));
	}

	if( !isset($args['setting']) && isset($args['uuid']) ) {
		$args['setting'] = $args['uuid'];
	}

	//
	// Prepare the query to fetch the list
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Get the setting information
	//
	$strsql = "SELECT ciniki_customer_settings.detail_key, "
		. "ciniki_customer_settings.detail_value, "
		. "UNIX_TIMESTAMP(ciniki_customer_settings.date_added) AS date_added, "
		. "UNIX_TIMESTAMP(ciniki_customer_settings.last_updated) AS last_updated, "
		. "ciniki_customer_history.id AS history_id, "
		. "ciniki_customer_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_customer_history.session, "
		. "ciniki_customer_history.action, "
		. "ciniki_customer_history.table_field, "
		. "ciniki_customer_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_customer_history.log_date) AS log_date "
		. "FROM ciniki_customer_settings "
		. "LEFT JOIN ciniki_customer_history ON (ciniki_customer_settings.detail_key = ciniki_customer_history.table_key "
			. "AND ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_history.table_name = 'ciniki_customer_settings' "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) "
		. "WHERE ciniki_customer_settings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customer_settings.detail_key = '" . ciniki_core_dbQuote($ciniki, $args['setting']) . "' "
		. "ORDER BY log_date "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'settings', 'fname'=>'detail_key', 
			'fields'=>array('detail_key', 'detail_value', 'date_added', 'last_updated')),
		array('container'=>'history', 'fname'=>'history_uuid', 
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'975', 'msg'=>'Unable to get customer setting', 'err'=>$rc['err']));
	}
	if( !isset($rc['settings'][$args['setting']]) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'152', 'msg'=>'Setting does not exist'));
	}
	$setting = $rc['settings'][$args['setting']];

	return array('stat'=>'ok', 'setting'=>$setting);
}
?>
