<?php
//
// Description
// -----------
// This f
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
function moss_core_dbGetChangeLog($moss, $business_id, $table_name, $table_key, $table_field, $module) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
	//
	$rc = moss_core_dbConnect($moss, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	// Get the history log from core_change_logs table.
	//
	require_once($moss['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuoteList.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbParseAge.php');

	$date_format = moss_users_datetimeFormat($moss);
	$strsql = "SELECT user_id, DATE_FORMAT(log_date, '" . moss_core_dbQuote($moss, $date_format) . "') as date, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
		. "table_key, "
		. "new_value as value "
		. " FROM core_change_logs "
		. " WHERE business_id ='" . moss_core_dbQuote($moss, $business_id) . "' "
		. " AND table_name = '" . moss_core_dbQuote($moss, $table_name) . "' ";
	if( is_array($table_key) ) {
		$strsql .= " AND table_key IN (" . moss_core_dbQuoteList($moss, $table_key) . ") ";
	} else {
		$strsql .= " AND table_key = '" . moss_core_dbQuote($moss, $table_key) . "' ";
	}
	$strsql .= " AND table_field = '" . moss_core_dbQuote($moss, $table_field) . "' "
		. " ORDER BY log_date DESC "
		. "";
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'133', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
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
		if( is_array($table_key) ) {
			$rsp['history'][$num_history]['action']['key'] = $row['table_key'];
		}
		$users[$row['user_id']] = 1;
		$rsp['history'][$num_history]['action']['age'] = moss_core_dbParseAge($moss, $row['age']);
		$num_history++;
	}

	//
	// Get the users who contributed to the actions
	//
	$rc = moss_core_dbConnect($moss, 'users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	$strsql = "SELECT id, display_name "
		. "FROM users "
		. "WHERE id IN (" . moss_core_dbQuote($moss, implode(',', array_keys($users))) . ") ";
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'135', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
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
