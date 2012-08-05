<?php
//
// Description
// -----------
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// ciniki:
// module:			The name of the module for the transaction, which should include the 
//					package in dot notation.  Example: ciniki.artcatalog
//
function ciniki_core_dbGetModuleHistoryFkId($ciniki, $module, $history_table, $business_id, $table_name, $table_key, $table_field, $fk_table, $fk_id_field, $fk_value_field) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
	//
	$rc = ciniki_core_dbConnect($ciniki, 'ciniki.core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	// Get the history log from ciniki_core_change_logs table.
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/dateFormat.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbParseAge.php');

	$datetime_format = ciniki_users_datetimeFormat($ciniki);
	$date_format = ciniki_users_dateFormat($ciniki);
	$strsql = "SELECT user_id, DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as date, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
		. "new_value as value, "
		. $fk_value_field . " AS fkidstr_value "
		. " ";
	$strsql .= " FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
		. "LEFT JOIN " . ciniki_core_dbQuote($ciniki, $fk_table) . " ON ($history_table.new_value = " . ciniki_core_dbQuote($ciniki, $fk_table) . "." . ciniki_core_dbQuote($ciniki, $fk_id_field) . " "
			. " AND " . ciniki_core_dbQuote($ciniki, $fk_table) . ".business_id ='" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
		. " WHERE $history_table.business_id ='" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. " AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
		. " AND table_key = '" . ciniki_core_dbQuote($ciniki, $table_key) . "' "
		. " AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_field) . "' "
		. " ORDER BY log_date DESC "
		. " ";
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'190', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	if( mysql_num_rows($result) <= 0 ) {
		return array('stat'=>'ok', 'history'=>array(), 'users'=>array());
	}

	$rsp = array('stat'=>'ok', 'history'=>array());
	$user_ids = array();
	$num_history = 0;
	while( $row = mysql_fetch_assoc($result) ) {
		$rsp['history'][$num_history] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 'value'=>$row['value']));
		$rsp['history'][$num_history]['action']['fkidstr_value'] = $row['fkidstr_value'];
		if( $row['user_id'] > 0 ) {
			array_push($user_ids, $row['user_id']);
		}
		$rsp['history'][$num_history]['action']['age'] = ciniki_core_dbParseAge($ciniki, $row['age']);
		$num_history++;
	}

	//
	// If there was no history, or user ids, then skip the user lookup and return
	//
	if( $num_history < 1 || count($user_ids) < 1 ) {
		return $rsp;
	}

	//
	// Get the list of users
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/userListByID.php');
	$rc = ciniki_users_userListByID($ciniki, 'users', array_unique($user_ids), 'display_name');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'193', 'msg'=>'Unable to merge user information', 'err'=>$rc['err']));
	}
	$users = $rc['users'];

	//
	// Merge user list information into array
	//
	foreach($rsp['history'] as $k => $v) {
		if( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] > 0 
			&& isset($users[$v['action']['user_id']]) && isset($users[$v['action']['user_id']]['display_name']) ) {
			$rsp['history'][$k]['action']['user_display_name'] = $users[$v['action']['user_id']]['display_name'];
		}
	}

	return $rsp;
}
?>
