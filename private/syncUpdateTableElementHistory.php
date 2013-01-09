<?php
//
// Description
// -----------
// This function will update the history elements.
//
// Arguments
// ---------
//
function ciniki_core_syncUpdateTableElementHistory(&$ciniki, &$sync, $business_id, $module, $history_table, $table_key, $table_name, $remote_history, $local_history, $maps) {
	//
	// All transaction locking should be taken care of by a calling function
	//

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');

	//
	// Go through each item from the remote server history, and check if it exists or should be added.
	// The history might already exist and generate a dupliate error key, even when it doesn't exist in local_history.
	//
	foreach($remote_history as $uuid => $history) {
		if( !isset($local_history[$uuid]) 
			|| ($local_history[$uuid]['user'] == '' && $remote_history[$uuid]['user'] != '') 
//			|| ($local_history[$uuid]['table_key'] == '' && $table_key != '') 
			) {
			//
			// Check for the user_uuid in the maps for this sync, otherwise query
			//
			if( !isset($local_history[$uuid]) || $local_history[$uuid]['user'] == '' ) {
				//FIXME: Convert to user_lookup
				
				if( $remote_history[$uuid]['user'] == '' ) {
					//
					// If the history is screwed up, then user may be blank
					//
					$user_id = 0;
				}
				elseif( isset($sync['uuidmaps']['ciniki_users'][$history['user']]) ) {
					$user_id = $sync['uuidmaps']['ciniki_users'][$history['user']];
				} else {
					$strsql = "SELECT id "
						. "FROM ciniki_users "
						. "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $history['user']) . "' "
						. "";
					$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					if( !isset($rc['user']) ) {
	//					//
	//					// Get the remote user
	//					//
	//					ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	//					$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.user.get', 'uuid'=>$history['user']));
	//					if( $rc['stat'] != 'ok' ) {
	//						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'982', 'msg'=>'Unable to get remote user: ' . $history['user'], 'err'=>$rc['err']));
	//					}
	//					if( !isset($rc['user']) ) {
	//						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'920', 'msg'=>'User not found on remote server'));
	//					}
	//					$user = $rc['user'];

						//
						// Add to the local database
						//
						ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'user_update');
						$rc = ciniki_businesses_user_update($ciniki, $sync, $business_id, array('uuid'=>$history['user']));
						if( $rc['stat'] != 'ok' ) {
							return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'921', 'msg'=>'Unable to add user', 'err'=>$rc['err']));;
						}
						$user_id = $rc['id'];
					} else {
						$user_id = $rc['user']['id'];
					}
				}
			}
			//
			// Add the history
			//
			if( !isset($local_history[$uuid]) ) {
				//
				// Check if the table_field is a field that reverences an ID, and needs to be converted from a UUID
				//
				if( isset($maps[$history['table_field']]) ) {
					$details = $maps[$history['table_field']];
					//
					// Lookup the object
					//
					ciniki_core_loadMethod($ciniki, $details['package'], $details['module'], 'sync', $details['lookup']);
					$lookup = $details['package'] . '_' . $details['module'] . '_' . $details['lookup'];
					$rc = $lookup($ciniki, $sync, $business_id, array('remote_uuid'=> $history['new_value']));
					if( $rc['stat'] != 'ok' ) {
						error_log('SYNC-ERR: Unable to locate local new value for ' . $history['table_name'] . '(' . $history['new_value'] . ')');
						$history['table_key'] = '';
					} else {
						$history['new_value'] = $rc['id'];
					}

//					$map_module = $maps[$history['table_field']]['module'];
//					$map_table = $maps[$history['table_field']]['table'];
//					if( isset($sync['uuids'][$map_table][$history['new_value']]) ) {
//						$history['new_value'] = $sync['uuids'][$map_table][$history['new_value']];
//					} else {
//						$strsql = "SELECT id "
//							. "FROM $map_table "
//							. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//							. "AND uuid = '" . ciniki_core_dbQuote($ciniki, $history['new_value']) . "' "
//							. "";
//						$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $map_module, 'table_id');
//						if( $rc['stat'] != 'ok' ) {
//							return $rc;
//						}
//						if( !isset($rc['table_id']) ) {
//							return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'280', 'msg'=>'Unable to find reference'));
//						} else {
//							$history['new_value'] = $rc['table_id']['id'];
//						}
//					}
				}

				$strsql = "INSERT INTO $history_table (uuid, business_id, user_id, "	
					. "session, action, table_name, table_key, table_field, "
					. "new_value, log_date) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $user_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $history['session']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $history['action']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $table_name) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $table_key) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $history['table_field']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $history['new_value']) . "', "
					. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $history['log_date']) . "') "
					. ")";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, $module);
				if( $rc['stat'] != 'ok' && $rc['err']['code'] != '73' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'279', 'msg'=>'Unable to add history'));
				}
			} elseif( $user_id > 0 ) {
				//
				// Update history user_id
				//
				$strsql = "UPDATE $history_table SET user_id = '" . ciniki_core_dbQuote($ciniki, $user_id) . "' ";
//				if( $local_history['table_key'] == '' && $table_key != '' ) {
//					$strsql .= ", table_key = '" . ciniki_core_dbQuote($ciniki, $table_key) . "' "
//						. "";
//				}
				$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND uuid = '" . ciniki_core_dbQuote($ciniki, $uuid) . "' "
					. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, $module);
				if( $rc['stat'] != 'ok' && $rc['err']['code'] != '73' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1017', 'msg'=>'Unable to update history'));
				}
//			} elseif( $local_history['table_key'] == '' && $table_key != '' ) {
//				//
//				// Update history user_id
//				//
//				$strsql = "UPDATE $history_table SET table_key = '" . ciniki_core_dbQuote($ciniki, $table_key) . "' "
//					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//					. "AND uuid = '" . ciniki_core_dbQuote($ciniki, $uuid) . "' "
//					. "AND table_key = ''"
//					. "";
//				$rc = ciniki_core_dbUpdate($ciniki, $strsql, $module);
//				if( $rc['stat'] != 'ok' && $rc['err']['code'] != '73' ) {
//					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'934', 'msg'=>'Unable to update history'));
//				}
			}

		}
	}

	return array('stat'=>'ok');
}
?>
