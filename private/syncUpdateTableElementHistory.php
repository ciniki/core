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

	//
	// Go through each item from the remote server history, and check if it exists or should be added.
	// The history might already exist and generate a dupliate error key, even when it doesn't exist in local_history.
	//
	foreach($remote_history as $uuid => $history) {
		if( !isset($local_history[$uuid]) ) {
			//
			// Check for the user_uuid in the maps for this sync, otherwise query
			//
			if( isset($sync['uuidmaps']['ciniki_users'][$history['user']]) ) {
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
					//
					// Get the remote user
					//
					ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
					$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.userGet', 'uuid'=>$history['user']));
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					if( !isset($rc['user']) ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'920', 'msg'=>'User not found on remote server'));
					}
					$user = $rc['user'];

					//
					// Add to the local database
					//
					ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userUpdate');
					$rc = ciniki_businesses_sync_userUpdate($ciniki, $sync, $business_id, array('user'=>$user));
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'921', 'msg'=>'Unable to add user', 'err'=>$rc['err']));;
					}
					$user_id = $rc['id'];
				} else {
					$user_id = $rc['user']['id'];
				}
			}

			//
			// Check if the table_field is a field that reverences an ID, and needs to be converted from a UUID
			//
			if( isset($maps[$history['table_field']]) ) {
				$map_module = $maps[$history['table_field']]['module'];
				$map_table = $maps[$history['table_field']]['table'];
				if( isset($sync['uuids'][$map_table][$history['new_value']]) ) {
					$history['new_value'] = $sync['uuids'][$map_table][$history['new_value']];
				} else {
					$strsql = "SELECT id "
						. "FROM $map_table "
						. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
						. "AND uuid = '" . ciniki_core_dbQuote($ciniki, $history['new_value']) . "' "
						. "";
					$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $map_module, 'table_id');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					if( !isset($rc['table_id']) ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'280', 'msg'=>'Unable to find reference'));
					} else {
						$history['new_value'] = $rc['table_id']['id'];
					}
				}
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
		}
	}

	return array('stat'=>'ok');
}
?>
