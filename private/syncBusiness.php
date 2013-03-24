<?php
//
// Description
// -----------
// This function will sync the data for a business from the remote
// server to the local server.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync_id:			The ID of the sync to check compatibility with.
// type:			The type of sync.
//
//					incremental - compare last updated of records from last sync
//					partial - compare last updated of all records
//					full - compare every record all fields
//
// module:			If the sync should only do one module.
//
function ciniki_core_syncBusiness($ciniki, $sync, $business_id, $type, $module) {

	//
	// Check the versions of tables and modules enabled are the same between servers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncCheckVersions');
	$rc = ciniki_core_syncCheckVersions($ciniki, $sync, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'567', 'msg'=>'Incompatible versions', 'err'=>$rc['err']));
	}
	$modules = $rc['modules'];
	$remote_modules = $rc['remote_modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusinessModule');

//	$last_sync_time = date('U');
	$strsql = "SELECT UNIX_TIMESTAMP() AS last_sync_time ";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$last_sync_time = $rc['sync']['last_sync_time'];
	error_log("Set last_sync: $business_id, $sync_id, $type, $last_sync_time");

	//
	// Setup logging
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLog');
	if( isset($ciniki['config']['ciniki.core']['sync.log_dir']) ) {
		$ciniki['synclogfile'] = $ciniki['config']['ciniki.core']['sync.log_dir'] . "/sync_" . $sync['sitename'] . '_' . $sync['id'] . ".log";
	}
	$ciniki['synclogprefix'] = '[' . $sync['sitename'] . '-' . $sync['remote_name'] . ']';

	//
	// If a specific module is specified, only sync that module and return.
	// Don't update sync times, as not all modules syncd
	//
	if( $module != '' ) {
		$rc = ciniki_core_syncBusinessModule($ciniki, $sync, $business_id, $module, $type, '');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'886', 'msg'=>'Unable to sync module ' . $module, 'err'=>$rc['err']));
		}
		//
		// Return 
		//
		return array('stat'=>'ok');
	}

	//
	// Sync the core modules first
	//
	$core_modules = array('ciniki.users', 'ciniki.businesses', 'ciniki.images');
	foreach($core_modules as $module) {
		if( $type == 'full' || $type == 'partial' 
			|| ($type == 'incremental'
				&& (isset($remote_modules[$module]['last_change'])
					&& ($remote_modules[$module]['last_change'] >= $sync['last_sync'] 
						|| $modules[$module]['last_change'] >= $sync['last_sync'])
					)
			)) {
			$rc = ciniki_core_syncBusinessModule($ciniki, $sync, $business_id, $module, $type, '');
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'251', 'msg'=>'Unable to sync module ' . $module, 'err'=>$rc['err']));
			}
			//
			// Update the module last_change timestamp if more recent on remote
			//
			$mname = preg_split('/\./', $module);
			$strsql = "UPDATE ciniki_business_modules "
				. "SET last_change = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$module]['last_change']) . "') "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND package = '" . ciniki_core_dbQuote($ciniki, $mname[0]) . "' "
				. "AND module = '" . ciniki_core_dbQuote($ciniki, $mname[1]) . "' "
				. "AND last_change < FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$module]['last_change']) . "') "
				. "";
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, $module);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Go through the priority optional modules, which needs to be sync'd in order
	//
	$priority_modules = array('ciniki.customers');
	foreach($priority_modules as $module) {
		// Check if module is enabled for the business
		// and only run an incmental if the last_change dates for the modules don't match
		if( isset($modules[$module]) 
			&& ($type == 'full' || $type == 'partial' || 
				($type == 'incremental' 
//					&& $modules[$module]['last_change'] != $remote_modules[$module]['last_change']
					&& ($remote_modules[$module]['last_change'] >= $sync['last_sync'] 
						|| $modules[$module]['last_change'] >= $sync['last_sync'])
					)) 
			) {
			$rc = ciniki_core_syncBusinessModule($ciniki, $sync, $business_id, $module, $type, '');
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'276', 'msg'=>'Unable to sync module ' . $module, 'err'=>$rc['err']));
			}
			//
			// Update the module last_change timestamp if more recent on remote
			//
			$mname = preg_split('/\./', $module);
			$strsql = "UPDATE ciniki_business_modules "
				. "SET last_change = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$module]['last_change']) . "') "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND package = '" . ciniki_core_dbQuote($ciniki, $mname[0]) . "' "
				. "AND module = '" . ciniki_core_dbQuote($ciniki, $mname[1]) . "' "
				. "AND last_change < FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$module]['last_change']) . "') "
				. "";
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, $module);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Go through the optional modules configured for the business
	//
	foreach($modules as $name => $module) {
		//
		// Check that it wasn't taken care of in priority modules
		//
		if( !in_array($name, $priority_modules) 
			&& !in_array($name, $core_modules) 
			&& ($type == 'full' || $type == 'partial' || 
				($type == 'incremental' 
				&& ($remote_modules[$name]['last_change'] >= $sync['last_sync'] 
					|| $modules[$name]['last_change'] >= $sync['last_sync'])
				)) 
			) {
			$rc = ciniki_core_syncBusinessModule($ciniki, $sync, $business_id, $name, $type, '');
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'252', 'msg'=>'Unable to sync module ' . $name, 'err'=>$rc['err']));
			}
			//
			// Update the module last_change timestamp if more recent on remote
			//
			$mname = preg_split('/\./', $name);
			$strsql = "UPDATE ciniki_business_modules "
				. "SET last_change = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$name]['last_change']) . "') "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND package = '" . ciniki_core_dbQuote($ciniki, $mname[0]) . "' "
				. "AND module = '" . ciniki_core_dbQuote($ciniki, $mname[1]) . "' "
				. "AND last_change < FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$name]['last_change']) . "') "
				. "";
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, $name);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Updated the last sync time
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateLastTime');
	$rc = ciniki_core_syncUpdateLastTime($ciniki, $business_id, $sync['id'], $type, $last_sync_time);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the remote time
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.core.syncUpdateLastTime',
		'type'=>$type, 'time'=>$last_sync_time));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
