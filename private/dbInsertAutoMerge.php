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
// user_id: 		The user making the request
// 
//
//
function moss_core_dbInsertAutoMerge($moss, $fields, $record, $prefix, $middle, $suffix, $row) {

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbHashToSQL.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbInsert.php');

	//
	// Loop through the fields given, and add them if there is
	// and entry in the record with the same name
	//
	foreach($fields as $field) {
		// Check for an entry in the record which has the right field name
		if( isset($record[$field]) && isset($record[$field]['data']) ) {
			if( $record[$field]['data'] != '' ) {
				// Setup for inserting if any
				$prefix .= moss_core_dbQuote($moss, $field) . ", ";
				$middle .= "'" . moss_core_dbQuote($moss, $record[$field]['data']) . "', ";
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

	$new_db_record = moss_core_dbInsert($moss, $strsql, 'customers');
	//if( $new_db_record['stat'] != 'ok' ) {
		return $new_db_record;
	//}

	return array('stat'=>'fail', 'err'=>array('code'=>'84', 'msg'=>'Internal error', 'pmsg'=>'Unable to build SQL insert string'));
}
?>
