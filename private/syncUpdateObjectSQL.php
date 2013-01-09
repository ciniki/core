<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_object, $local_object, $fields) {
	//
	// Go through all the fields and build the SQL string
	//
	$strsql = '';
	$comma = '';
	foreach($fields as $field => $finfo) {
		//
		// Check if the fields are different, and if so, figure out which one is newer
		//
		if( $remote_object[$field] != $local_object[$field] ) {
			if( $field == 'date_added' ) {
				continue;
			}
			if( $field == 'last_updated' ) {
				if( $remote_object[$field] > $local_object[$field] ) {
					$strsql .= $comma . " $field = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_object[$field]) . "') ";
					$comma = ',';
					// Skip the rest, save the processing trying to lookup value
				}
				continue;
			}
			//
			// Check the history for each field, and see which side is newer, remote or local
			//
			$remote_uts = 0;
			$local_uts = 0;
			$remote_new_value = '';
			$local_new_value = '';
			if( isset($remote_object['history']) ) {
				foreach($remote_object['history'] as $history_uuid => $history) {
					if( $history['table_field'] == $field && $history['log_date'] > $remote_uts ) {
						$remote_uts = $history['log_date'];
						$remote_new_value = $history['new_value'];
					}
				}
			}
			if( isset($local_object['history']) ) {
				foreach($local_object['history'] as $history_uuid => $history) {
					if( $history['table_field'] == $field && $history['log_date'] > $local_uts ) {
						$local_uts = $history['log_date'];
						$local_new_value = $history['new_value'];
					}
				}
			}
			
			//
			// Check if the field should be updated locally
			//
			$new_value = null;
			if( $remote_uts > $local_uts ) {
				// Find the first occurance of field in history for both local and remote, compare log_date
				$new_value = $remote_object[$field];
			}
			elseif( $remote_uts > 0 && $remote_uts == $local_uts ) {
				//
				// If the dates are all the same, see if it matches one of the history values, and use that value.
				//
				if( $remote_new_value == $local_object[$field] || $remote_new_value == $remote_object[$field] ) {
					// Use the remote value if it is what is the latest value in the history
					$new_value = $remote_new_value;
				} elseif( $local_new_value == $local_object[$field] || $local_new_value == $remote_object[$field] ) {
					// Use the local value if it is what is the latest value in the history
					$new_value = $local_new_value;
				}
			}
			elseif( isset($remote_object['last_update']) && isset($local_object['last_update'])
				&& $remote_object['last_update'] > $local_object['remote_object'] ) {
				// 
				// If the object was updated later on the remote, use it's field value
				//
				$new_value = $remote_object[$field];
			} 
			elseif( isset($remote_object['last_update']) && isset($local_object['last_update'])
				&& $remote_object['last_update'] < $local_object['remote_object'] ) {
				continue; // Skip the field if local is newer
			}
			elseif( $local_uts > $remote_uts ) {
				// Skip the field if local is newer
				continue;
			} else {
//				error_log($remote_uts . ':' . $remote_new_value . ' -- ' . $local_uts . ':' . $local_new_value);
//				error_log(print_r($remote_object, true));
//				error_log(print_r($local_object, true));
				error_log("SYNC-ERR: $field is different but unable to tell which is newer.  sync_id: " . $sync['id'] . " $field (" . $remote_object[$field] . ":" . $local_object[$field] . ')');
				error_log(print_r($remote_object['history'], true));
				error_log(print_r($local_object['history'], true));
			}
			if( $new_value != null ) {
				if( isset($finfo['type']) && $finfo['type'] == 'uts' ) {
					$strsql .= $comma . " $field = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $new_value) . "') ";
				} else {
					$strsql .= $comma . " $field = '" . ciniki_core_dbQuote($ciniki, $new_value) . "' ";
				}
				$comma = ',';
			}
		}
	}

	return array('stat'=>'ok', 'strsql'=>$strsql);
}
?>
