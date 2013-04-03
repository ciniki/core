<?php
//
// Description
// -----------
// This method retrieves the history elements for a module field, where the field type is tags.  The users display_name is 
// attached to each record as user_display_name.
//
// Arguments
// ---------
// ciniki:
// module:			The name of the module for the transaction, which should include the 
//					package in dot notation.  Example: ciniki.artcatalog
//
//
function ciniki_core_dbGetModuleHistoryTags($ciniki, $module, $history_table, $business_id, $table_name, $table_key, $table_field, $table_id_field, $tag_type) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
	//
//	$rc = ciniki_core_dbConnect($ciniki, $module);
//	if( $rc['stat'] != 'ok' ) {
//		return $rc;
//	}
//
//	$dh = $rc['dh'];

	//
	// Get the history log from ciniki_core_change_logs table.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');

//	select * from ciniki_artcatalog_history where business_id = 15 and table_name = 'ciniki_artcatalog_tags' and table_key in (select table_key from ciniki_artcatalog_history where table_field = 'artcatalog_id' and new_value = '8') order by log_date;

	$date_format = ciniki_users_datetimeFormat($ciniki);
	$strsql = "SELECT user_id, DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as date, "
		. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
		. "action, "
		. "table_key, "
		. "table_field, "
		. "ciniki_users.display_name AS user_display_name, "
		. "new_value as value "
		. "FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
		. "LEFT JOIN ciniki_users ON ($history_table.user_id = ciniki_users.id) "
		. "WHERE business_id ='" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
		. "AND (table_field = 'tag_name' OR table_field = '*') "
		. "AND table_key IN ("
			. "SELECT DISTINCT table_key "
			. "FROM $history_table "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND table_field = 'tag_type' "
			. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $tag_type) . "' "
			. "AND table_key IN ("
				. "SELECT DISTINCT table_key "
				. "FROM $history_table "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_id_field) . "' "
				. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $table_key) . "' "
				. ") "
			. ") "
		. "ORDER BY $history_table.log_date ASC "
		. "";
	error_log($strsql);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $module, 'row');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	// Build array of tag_id's to use based on type
	$tags = array();
	$history = array();
	foreach($rc['rows'] as $row) {
		if( $row['table_field'] == 'tag_name' ) {
			$tags[$row['table_key']] = $row['value'];
			$history[] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 
				'action'=>'1', 'value'=>$row['value'], 
				'age'=>ciniki_core_dbParseAge($ciniki, $row['age']), 
				'user_display_name'=>$row['user_display_name']));
		} elseif( $row['table_field'] == '*' && isset($tags[$row['table_key']]) ) {
			$history[] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 
				'action'=>'3', 'value'=>$tags[$row['table_key']], 
				'age'=>ciniki_core_dbParseAge($ciniki, $row['age']), 
				'user_display_name'=>$row['user_display_name']));
		}
	}

	return array('stat'=>'ok', 'history'=>array_reverse($history));
}
?>
