<?php
//
// Description
// -----------
// This function will get the history for an element that was in a linking table.  This was developed
// for getting the history from ciniki_tax_type_rates.
//
// Arguments
// ---------
// ciniki:
// module:			The name of the module for the transaction, which should include the 
//					package in dot notation.  Example: ciniki.artcatalog
//
//
function ciniki_core_dbGetModuleHistoryLinkedToggle(&$ciniki, $module, $history_table, $business_id, 
	$table_name, $table_fielda, $table_fielda_value, $table_fieldb, $table_fieldb_value) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
	//
	$rc = ciniki_core_dbConnect($ciniki, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	// Get the history log from ciniki_core_change_logs table.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');

	//
	// Get the list of table keys for fielda, or one side of the linking
	//
	$strsql = "SELECT DISTINCT table_key "
		. " FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
		. " WHERE business_id ='" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. " AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
		. " AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_fielda) . "' "
		. " AND new_value = '" . ciniki_core_dbQuote($ciniki, $table_fielda_value) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
	$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.taxes', 'table_keys', 'table_key');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['table_keys']) || count($rc['table_keys']) < 1 ) {
		return array('stat'=>'ok', 'history'=>array());		
	}
	$fielda_keys = $rc['table_keys'];

	//
	// Get the list of table keys for fielda, or one side of the linking
	//
	$strsql = "SELECT DISTINCT table_key "
		. " FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
		. " WHERE business_id ='" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. " AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
		. " AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_fieldb) . "' "
		. " AND new_value = '" . ciniki_core_dbQuote($ciniki, $table_fieldb_value) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
	$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.taxes', 'table_keys', 'table_key');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['table_keys']) || count($rc['table_keys']) < 1 ) {
		return array('stat'=>'ok', 'history'=>array());		
	}
	$fieldb_keys = $rc['table_keys'];

	$table_keys = array_intersect($fielda_keys, $fieldb_keys);

	//
	// Get all the entries, and return on or off
	//
	$date_format = ciniki_users_datetimeFormat($ciniki);
	$strsql = "SELECT user_id, DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as date, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
		. "action, "
		. "table_key, "
		. "IF(table_field='*', 'no', 'yes') AS value "
		. " FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
		. " WHERE business_id ='" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. " AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
		. " AND table_key IN (" . ciniki_core_dbQuoteList($ciniki, $table_keys) . ") "
		. " AND (table_field = '" . ciniki_core_dbQuote($ciniki, $table_fieldb) . "' OR table_field = '*') "
		. " ORDER BY log_date DESC "
		. "";
	$result = mysqli_query($dh, $strsql);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'134', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	if( mysqli_num_rows($result) <= 0 ) {
		return array('stat'=>'ok', 'history'=>array());
	}

	$rsp = array('stat'=>'ok', 'history'=>array());
	$user_ids = array();
	$num_history = 0;
	while( $row = mysqli_fetch_assoc($result) ) {
		$rsp['history'][$num_history] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 'value'=>$row['value']));
//		if( is_array($table_keys) ) {
			$rsp['history'][$num_history]['action']['key'] = $row['table_key'];
//		}
		if( $row['user_id'] != 0 ) {
			array_push($user_ids, $row['user_id']);
		}
		$rsp['history'][$num_history]['action']['age'] = ciniki_core_dbParseAge($ciniki, $row['age']);
		$num_history++;
	}

	mysqli_free_result($result);

	//
	// If there was no history, or user ids, then skip the user lookup and return
	//
	if( $num_history < 1 || count($user_ids) < 1 ) {
		return $rsp;
	}

	//
	// Get the list of users
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'userListByID');
	$rc = ciniki_users_userListByID($ciniki, 'users', array_unique($user_ids), 'display_name');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'135', 'msg'=>'Unable to merge user information', 'err'=>$rc['err']));
	}
	$users = $rc['users'];

	//
	// Merge user list information into array
	//
	foreach($rsp['history'] as $k => $v) {
		if( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] != 0 
			&& isset($users[$v['action']['user_id']]) && isset($users[$v['action']['user_id']]['display_name']) ) {
			$rsp['history'][$k]['action']['user_display_name'] = $users[$v['action']['user_id']]['display_name'];
		} 
		if( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] == 0 ) {
			$rsp['history'][$k]['action']['user_display_name'] = 'unknown';
		}
	}

	return $rsp;
}
?>
