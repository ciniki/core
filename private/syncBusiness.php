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
//
function ciniki_core_syncBusiness($ciniki, $business_id, $sync_id, $type) {

	//
	// Check the versions of tables and modules enabled are the same between servers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncCheckVersions');
	$rc = ciniki_core_syncCheckVersions($ciniki, $business_id, $sync_id);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'567', 'msg'=>'Incompatible versions', 'err'=>$rc['err']));
	}
	$sync = $rc['sync'];
	$modules = $rc['modules'];
	$remote_modules = $rc['remote_modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusinessModule');

//	$last_sync_time = date('U');
	$strsql = "SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) AS last_sync_time ";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$last_sync_time = $rc['sync']['last_sync_time'];

	//
	// Sync the core modules first
	//
//	$core_modules = array('ciniki.users', 'ciniki.images');
	$core_modules = array('ciniki.users', 'ciniki.businesses');
//	$core_modules = array();
	foreach($core_modules as $module) {
//		continue;
		// FIXME: Put in check for incremental, will need to add core modules to list when 
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
					&& ($remote_modules[$module]['last_change'] >= $sync['last_sync'] || $modules[$module]['last_change'] >= $sync['last_sync'])
					)) ) {
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
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Go through the optional modules configured for the business
	//
	foreach($modules as $name => $module) {

		// FIXME: Remove
		continue;


		//
		// Check that it wasn't taken care of in priority modules
		//
		if( !in_array($name, $priority_modules) 
			&& !in_array($name, $core_modules) 
			&& ($type == 'full' || $type == 'partial' || 
				($type == 'incremental' 
				&& ($remote_modules[$module]['last_change'] >= $sync['last_sync'] || $modules[$module]['last_change'] >= $sync['last_sync'])
				)) ) {
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
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Updated the last sync time
	//
	$strsql = "UPDATE ciniki_business_syncs SET last_sync = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') ";
	if( $type == 'partial' || $type == 'full' ) {
		$strsql .= ", last_partial = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') ";
	} 
	// The full sync, updates the partial and incremental dates as well
	if( $type == 'full' ) {
		$strsql .= ", last_full = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') ";
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $sync_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
