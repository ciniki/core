<?php
//
// Description
// -----------
// This function will check for the add/update history entries in the history
// table and if missing add them.
//
// Arguments
// ---------
//
function ciniki_core_dbFixTableHistory($ciniki, $module, $business_id, $table, $history_table, $fields) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

	//
	// Go through each field and check if the history is missing
	//
	foreach($fields as $field) {
		//
		// Get the entries which are missing the history for field
		//
		$strsql = "SELECT $table.id, $table.$field AS field_value, "
			. "UNIX_TIMESTAMP($table.date_added) AS date_added, "
			. "UNIX_TIMESTAMP($table.last_updated) AS last_updated "
			. "FROM $table "
			. "LEFT JOIN $history_table ON ($table.id = $history_table.table_key "
				. "AND $history_table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND $history_table.table_name = '" . ciniki_core_dbQuote($ciniki, $table) . "' "
				. "AND ($history_table.action = 1 OR $history_table.action = 2) "
				. "AND $history_table.table_field = '" . ciniki_core_dbQuote($ciniki, $field) . "' "
				. ") "
			. "WHERE $table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND $table.$field <> '' "
			. "AND $table.$field <> '0000-00-00' "
			. "AND $table.$field <> '0000-00-00 00:00:00' "
			. "AND $history_table.uuid IS NULL "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $module, 'history');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Add the missing history
		//
		$elements = $rc['rows'];
		foreach($elements AS $rid => $row) {
			$strsql = "INSERT INTO $history_table (uuid, business_id, user_id, session, action, "
				. "table_name, table_key, table_field, new_value, log_date) VALUES ("
				. "UUID(), "
				. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
				. "'1', '" . ciniki_core_dbQuote($ciniki, $table) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['field_value']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $row['date_added']) . "') "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, $module);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>
