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
function ciniki_core_dbFixTableHistory(&$ciniki, $module, $business_id, $table, $history_table, $fields) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');

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

	if( in_array('uuid', $fields) ) {
		//
		// Remove duplicate uuid entries
		//
		$strsql = "SELECT id, uuid, business_id, table_name, table_key, table_field, new_value "
			. "FROM $history_table "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND table_name = '" . ciniki_core_dbQuote($ciniki, $table) . "' "
			. "AND table_field = 'uuid' "
			. "AND action = 1 "
			. "ORDER BY table_key, id ";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $module, 'entry');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rows = $rc['rows'];
		$prev_rid = -1;
		foreach($rows as $rid => $row) {
			if( $prev_rid > -1 
				&& $rows[$prev_rid]['business_id'] == $row['business_id']
				&& $rows[$prev_rid]['table_name'] == $row['table_name']
				&& $rows[$prev_rid]['table_key'] == $row['table_key']
				&& $rows[$prev_rid]['new_value'] == $row['new_value']
				) {
				$strsql = "DELETE FROM $history_table "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND id = '" . ciniki_core_dbQuote($ciniki, $row['id']) . "' "
					. "";
				$rc = ciniki_core_dbDelete($ciniki, $strsql, $module);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			} else {
				$prev_rid = $rid;
			}
		}

		//
		// Check for deleted entries with no uuid
		//
		$strsql = "SELECT h1.id, h1.uuid, h1.table_name, h1.table_key, h1.table_field, h1.new_value, h2.new_value "
			. "FROM $history_table AS h1 "
			. "LEFT JOIN $history_table AS h2 ON (h1.business_id = h2.business_id "
				. "AND h1.table_name = h2.table_name "
				. "AND h1.table_key = h2.table_key "
				. "AND h2.table_field = 'uuid' ) "
			. "WHERE h1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND h1.table_name = '" . ciniki_core_dbQuote($ciniki, $table) . "' "
			. "AND h1.action = 3 "
			. "AND h2.new_value IS NULL "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $module, 'entry');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rows = $rc['rows'];
		foreach($rows as $rid => $row) {
			$strsql = "DELETE FROM $history_table "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND table_name = '" . ciniki_core_dbQuote($ciniki, $row['table_name']) . "' "
				. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $row['table_key']) . "' "
				. "";
			$rc = ciniki_core_dbDelete($ciniki, $strsql, $module);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>
