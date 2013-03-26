<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function ciniki_core_syncObjectUpdateSQL($ciniki, $sync, $business_id, $o, $remote_object, $local_object) {
	//
	// Go through all the fields and build the SQL string
	//
	$strsql = '';
	$comma = '';
	$fields = $o['fields'];
	$fields['date_added'] = array('type'=>'uts');
	$fields['last_updated'] = array('type'=>'uts');
	foreach($fields as $field => $finfo) {
		//
		// Translate remote ID's to load ID's before compare
		//
		if( isset($finfo['oref']) && $finfo['oref'] != '' && $remote_object[$field] != '0' 
			&& isset($remote_object[$finfo['oref']]) && $remote_object[$finfo['oref']] != ''
			) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
			$rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $remote_object[$finfo['oref']], array());
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1142', 'msg'=>'Unable to load referenced object ' . $remote_object[$finfo['oref']], 'err'=>$rc['err']));
			}
			$ref_o = $rc['object'];

			//
			// Lookup the object
			//
			if( !isset($ref_o['type']) && $ref_o['type'] != 'settings' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
				$rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
					array('remote_uuid'=>$remote_object[$field]));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1141', 'msg'=>'Unable to find referenced ' . $o['name'], 'err'=>$rc['err']));
				}
				$remote_object[$field] = $rc['id'];
			}
		}
		elseif( isset($finfo['ref']) && $finfo['ref'] != '' && $remote_object[$field] != '0' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
			$rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $finfo['ref'], array());
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1193', 'msg'=>'Unable to load object ' . $finfo['ref'], 'err'=>$rc['err']));
			}
			$ref_o = $rc['object'];

			//
			// Lookup the object
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
			$rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
				array('remote_uuid'=>$remote_object[$field]));
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1194', 'msg'=>'Unable to find ' . $o['name'], 'err'=>$rc['err']));
			}
			$remote_object[$field] = $rc['id'];
		}

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
				ciniki_core_syncLog($ciniki, 0, "$field is different but unable to tell which is newer. (" . $remote_object[$field] . ":" . $local_object[$field] . ')', null);
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
