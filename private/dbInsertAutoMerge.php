<?php
//
// Description
// -----------
// This function is used by the autoMerge scripts to manage the data
// going into the database.  This function will update the record with
// status and import_result which can be later updated in the database.
//
// Info
// ----
// Status: started
//
// Arguments
// ---------
// ciniki:
//
function ciniki_core_dbInsertAutoMerge(&$ciniki, $fields, $record, $prefix, $middle, $suffix, $row) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashToSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

	//
	// Loop through the fields given, and add them if there is
	// and entry in the record with the same name
	//
	foreach($fields as $field) {
		// Check for an entry in the record which has the right field name
		if( isset($record[$field]) && isset($record[$field]['data']) ) {
			if( $record[$field]['data'] != '' ) {
				// Setup for inserting if any
				$prefix .= ciniki_core_dbQuote($ciniki, $field) . ", ";
				$middle .= "'" . ciniki_core_dbQuote($ciniki, $record[$field]['data']) . "', ";
				if( isset($record[$field]['col']) && $record[$field]['col'] > 0 ) {
					$row[$record[$field]['col']]['new_status'] = 2;
					$row[$record[$field]['col']]['new_import_status'] = 10;
				}
			} else {
				if( isset($record[$field]['col']) && $record[$field]['col'] > 0 ) {
					$row[$record[$field]['col']]['new_status'] = 2;
					$row[$record[$field]['col']]['new_import_status'] = 12;
				}
			}
		}
	}

	$strsql = $prefix . $middle . $suffix;

	$new_db_record = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
	//if( $new_db_record['stat'] != 'ok' ) {
		return $new_db_record;
	//}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'84', 'msg'=>'Internal error', 'pmsg'=>'Unable to build SQL insert string'));
}
?>
