<?php
//
// Description
// -----------
// This function will sync a modules data between two ciniki installs.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync:			The sync object containing the sync keys and URL's.
// module:			The module to sync.
// type:			The type of sync (full, partial, incremental)
//
function ciniki_core_syncBusinessModule(&$ciniki, &$sync, $business_id, $module, $type, $specified_object) {
	//
	// Load the objects for this module
	//
	$method_filename = $ciniki['config']['core']['root_dir'] . preg_replace('/^(.*)\.(.*)$/', '/\1-api/\2/sync/objects.php', $module);
	$method_function = preg_replace('/^(.*)\.(.*)$/', '\1_\2_sync_objects', $module);
	if( !file_exists($method_filename) ) {
		// 
		// No sync or objects defined for this module, skip
		//
		return array('stat'=>'ok');
	}

	require_once($method_filename);
	if( !is_callable($method_function) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'253', 'msg'=>'Unable to sync module: ' . $module));
	}

	$rc = $method_function($ciniki, $sync, $business_id, array('type'=>$type));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'981', 'msg'=>'Unable to sync module: ' . $module, 'err'=>$rc['err']));
	}
	if( !isset($rc['objects']) ) {
		//
		// If no objects specified, then nothing to sync from this module
		//
		return array('stat'=>'ok');
	}
	$objects = $rc['objects'];
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');

	//
	// For each object, get the list and compare
	//
	$a = preg_split('/\./', $module);
	$pkg = $a[0];
	$mod = $a[1];
	foreach($objects as $name => $obj) {
		if( $specified_object != '' && $name != $specified_object ) {
			continue;
		}
		//
		// FIXME: Put check for override sync file object.sync.php
		//

		//
		// Load required sync methods
		//
		ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sync', $name . '_list');
		ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sync', $name . '_get');
		ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sync', $name . '_update');
		$list = $pkg . '_' . $mod . '_' . $name . '_list';
		$get = $pkg . '_' . $mod . '_' . $name . '_get';
		$update = $pkg . '_' . $mod . '_' . $name . '_update';
		$delete = $pkg . '_' . $mod . '_' . $name . '_delete';
		$push = $pkg . '_' . $mod . '_' . $name . '_push';

		//
		// Get the remote list of objects
		//
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"$pkg.$mod.$name.list", 'type'=>$type, 'since_uts'=>$sync['last_sync']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'925', 'msg'=>"Unable to get the remote list: $pkg.$mod.$name", 'err'=>$rc['err']));
		}

		if( !isset($rc['list']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'274', 'msg'=>'Unable to get remote list'));
		}
		$remote_list = $rc['list'];
		if( isset($rc['deleted']) ) {
			$remote_deleted = $rc['deleted'];
		} else {
			$remote_deleted = array();
		}

		//
		// Get the local list of objects
		//
		$rc = $list($ciniki, $sync, $business_id, array('type'=>$type, 'since_uts'=>$sync['last_sync']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'913', 'msg'=>'Unable to get the local list', 'err'=>$rc['err']));
		}
		if( !isset($rc['list']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'275', 'msg'=>'Unable to get local list'));
		}
		$local_list = $rc['list'];
		if( isset($rc['deleted']) ) {
			$local_deleted = $rc['deleted'];
		} else {
			$local_deleted = array();
		}

		//
		// Process any deleted items first
		//
//		error_log("Remote deleted: " . print_r(array_keys($remote_deleted), true));
//		error_log("Local list: " . print_r(array_keys($local_list), true));
		if( ($sync['flags']&0x01) == 0x01 && isset($remote_deleted) && count($remote_deleted) > 0 ) {
			foreach($remote_deleted as $uuid => $deleted_history) {
				if( isset($local_list[$uuid]) ) {
					//
					// Delete from the local server
					//
					error_log("delete local: $specified_object($uuid)");
					ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sync', $name . '_delete');
					$rc = $delete($ciniki, $sync, $business_id, array('uuid'=>$uuid, 'history'=>$deleted_history));
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1018', 'msg'=>"Unable to delete $name on local server", 'err'=>$rc['err']));;
					}
					unset($local_list[$uuid]);
				}
			}
		}
		if( ($sync['flags']&0x01) == 0x01 && isset($local_deleted) && count($local_deleted) > 0 ) {
			foreach($local_deleted as $uuid => $deleted_history) {
				if( isset($remote_list[$uuid]) ) {
					//
					// Push the delete to the remote server
					//
					error_log("delete remote: $specified_object($uuid)");
					$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"$pkg.$mod.$name.delete", 'uuid'=>$uuid, 'history'=>$deleted_history));
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1019', 'msg'=>"Unable to delete $name($uuid) on remote server", 'err'=>$rc['err']));
					}
					unset($remote_list[$uuid]);
				}
			}
		}


		//
		// For the pull side
		//
		if( ($sync['flags']&0x02) == 0x02 ) {
			foreach($remote_list as $uuid => $last_updated) {
				//
				// A full sync will compare every customer, 
				// a partial or incremental will only check records where the last_updated differs
				// Check if uuid does not exist, and has not been deleted
				//
				if( ($type == 'full' || !isset($local_list[$uuid]) || $local_list[$uuid] != $last_updated)
					&& !isset($local_deleted[$uuid]) ) {
					//
					// Add to the local database
					//
					$rc = $update($ciniki, $sync, $business_id, array('uuid'=>$uuid));
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'284', 'msg'=>"Unable to update $name on local server", 'err'=>$rc['err']));;
					}
				} 
			}
		}

		//
		// For the push side
		//
		if( ($sync['flags']&0x01) == 0x01 ) {
			foreach($local_list as $uuid => $last_updated) {
				//
				// Check if uuid does not exist, and has not been deleted
				//
				if( ($type == 'full' || !isset($remote_list[$uuid]) || $remote_list[$uuid] != $last_updated) 
					&& !isset($remote_deleted[$uuid]) ) {
					$rc = $get($ciniki, $sync, $business_id, array('uuid'=>$uuid));
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1037', 'msg'=>"Unable to get $name($uuid) on local server", 'err'=>$rc['err']));
					}
					//
					// Update the remote object
					//
					$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"$pkg.$mod.$name.update", 'uuid'=>$uuid, "$name"=>$rc[$name]));
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1015', 'msg'=>"Unable to update $name($uuid) on remote server", 'err'=>$rc['err']));
					}
				}
			}
		}
	}
	
	return array('stat'=>'ok');
}
?>
