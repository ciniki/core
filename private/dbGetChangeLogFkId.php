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
// user_id: 		The user making the request
//
//
function ciniki_core_dbGetChangeLogFkId($ciniki, $business_id, $table_name, $table_key, $table_field, $module, 
	$fk_table, $fk_id_field, $fk_value_field
	) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
	//
	$rc = ciniki_core_dbConnect($ciniki, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	// Get the history log from core_change_logs table.
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
	$strsql .= " FROM core_change_logs "
		. "LEFT JOIN " . ciniki_core_dbQuote($ciniki, $fk_table) . " ON (core_change_logs.new_value = " . ciniki_core_dbQuote($ciniki, $fk_table) . "." . ciniki_core_dbQuote($ciniki, $fk_id_field) . " "
			. " AND " . ciniki_core_dbQuote($ciniki, $fk_table) . ".business_id ='" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
		. " WHERE core_change_logs.business_id ='" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. " AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
		. " AND table_key = '" . ciniki_core_dbQuote($ciniki, $table_key) . "' "
		. " AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_field) . "' "
		. " ORDER BY log_date DESC "
		. " ";
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'189', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	if( mysql_num_rows($result) <= 0 ) {
		return array('stat'=>'ok', 'history'=>array(), 'users'=>array());
	}

	$rsp = array('stat'=>'ok', 'history'=>array(), 'users'=>array());
	$users = array();
	$num_history = 0;
	while( $row = mysql_fetch_assoc($result) ) {
		$rsp['history'][$num_history] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 'value'=>$row['value']));
		$rsp['history'][$num_history]['action']['fkidstr_value'] = $row['fkidstr_value'];
		$users[$row['user_id']] = 1;
		$rsp['history'][$num_history]['action']['age'] = ciniki_core_dbParseAge($ciniki, $row['age']);
		$num_history++;
	}

	//
	// Get the users who contributed to the actions
	//
	$rc = ciniki_core_dbConnect($ciniki, 'users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	$strsql = "SELECT id, display_name "
		. "FROM users "
		. "WHERE id IN (" . ciniki_core_dbQuote($ciniki, implode(',', array_keys($users))) . ") ";
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

	$num_users = 0;
	while( $row = mysql_fetch_assoc($result) ) {
		$num_users++;
		$rsp['users'][$row['id']] = array('user'=>$row);
	}

	return $rsp;
}
?>
