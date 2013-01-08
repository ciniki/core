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
				if( $type == 'full' || !isset($local_list[$uuid]) || $local_list[$uuid] != $last_updated ) {
					//
					// Add to the local database
					//
					$rc = $update($ciniki, $sync, $business_id, array("uuid"=>$uuid));
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'284', 'msg'=>"Unable to add $name", 'err'=>$rc['err']));;
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
				if( $type == 'full' || !isset($remote_list[$uuid]) || $remote_list[$uuid] != $last_updated ) {
					//
					// Update the remote object
					//
					$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"$pkg.$mod.$name.update", "uuid"=>$uuid));
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1015', 'msg'=>"Unable to get $name on remote server"));
						return $rc;
					}
				}
			}
		}
	}
	

//	$method_filename = $ciniki['config']['core']['root_dir'] . preg_replace('/^(.*)\.(.*)$/', '/\1-api/\2/private/syncModule.php', $module);
//	$method_function = preg_replace('/^(.*)\.(.*)$/', '\1_\2_syncModule', $module);

//	if( !file_exists($method_filename) ) {
//		return array('stat'=>'ok');
//	}
//
//	require_once($method_filename);
//	if( !is_callable($method_function) ) {
//		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'253', 'msg'=>'Unable to sync module: ' . $module));
//	}
//
//	$rc = $method_function($ciniki, $sync, $business_id, array('type'=>$type));
//	if( $rc['stat'] != 'ok' ) {
//		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'981', 'msg'=>'Unable to sync module: ' . $module, 'err'=>$rc['err']));
//	}

	return array('stat'=>'ok');
}
?>
