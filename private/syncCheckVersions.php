<?php
//
// Description
// -----------
// This function will check the local and remote business information
// for compatibility.  The tables must all be at the same version on
// either side of the sync, and the modules must be enabled and the 
// same version.  
//
// The version information for modules is stores in the _versions.ini file
// and should be generated whenever the code is updated.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync_id:			The ID of the sync to check compatibility with.
//
function ciniki_core_syncCheckVersions($ciniki, $business_id, $sync_id) {

	//
	// Get the sync information required to send the request
	//
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.uuid AS local_uuid, ciniki_business_syncs.flags, local_private_key, "
		. "remote_name, remote_uuid, remote_url, remote_public_key "
		. "FROM ciniki_businesses, ciniki_business_syncs "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_businesses.id = ciniki_business_syncs.business_id "
		. "AND ciniki_business_syncs.id = '" . ciniki_core_dbQuote($ciniki, $sync_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sync']) || !is_array($rc['sync']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'392', 'msg'=>'Invalid sync'));
	}
	$sync = $rc['sync'];
	$sync['type'] = 'business';

	//
	// Make the request for the remote business information
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.core.info'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$remote_modules = $rc['modules'];

	// 
	// Get the local business information
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusinessInfo');
	$rc = ciniki_core_syncBusinessInfo($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['modules']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'296', 'msg'=>'No modules enabled'));
	}

	// 
	// Re-index for fast lookup
	//
	$local_modules = array();
	foreach($rc['modules'] as $mnum => $module) {
		$tables = array();
		if( isset($module['module']['tables']) ) {
			foreach($module['module']['tables'] as $tnum => $table) {
				$tables[$table['table']['name']] = array('name'=>$table['table']['name'], 'version'=>$table['table']['version']);
			}
		}
		$local_modules[$module['module']['package'] . '.' . $module['module']['name']] = 
			array('package'=>$module['module']['package'], 'name'=>$module['module']['name'], 
				'version'=>$module['module']['version'],
				'hash'=>$module['module']['hash'],
				'tables'=>$tables);
	}

	//
	// Compare local and remote business information
	//
	$errors = '';
	$missing_modules = '';
	$incompatible_versions = '';
	$comma = '';
	foreach($remote_modules as $mnum => $module) {
		$name = $module['module']['package'] . '.' . $module['module']['name'];
		if( !isset($local_modules[$name]) ) {
			$errors .= $comma . "missing module $name";
			$missing_modules .= $comma . $name;
			$comma = ', ';
		} elseif( $module['module']['version'] != $local_modules[$name]['version'] ) {
			$errors .= $comma . "module $name incorrect version";
			$incompatible_versions .= $comma . $name;
			$comma = ', ';
		} else {
			foreach($module['module']['tables'] as $tnum => $table) {
				$tname = $table['table']['name'];
				if( !isset($local_modules[$name]['tables'][$tname]) ) {
					$errors .= $comma . "missing table $tname";
					$comma = ', ';
				}
				elseif( $table['table']['version'] != $local_modules[$name]['tables'][$tname]['version'] ) {
					$errors .= $comma . "incorrect table version $tname (" . $local_modules[$name]['tables'][$tname]['version'] . " should be " . $table['table']['version'] . ")";
					$comma = ', ';
				}
			}
		}
	}

	if( $missing_modules != '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'570', 'msg'=>"The following modules must be enabled before syncronization: $missing_modules", 'pmsg'=>"$errors."));
	}

	if( $incompatible_versions != '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'287', 'msg'=>"The following modules must be updated before syncronization: $incompatible_versions", 'pmsg'=>"$errors."));
	}

	if( $errors != '' ) {
		error_log($errors);
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'572', 'msg'=>'System code must be updated before synchronization.', 'pmsg'=>"$errors."));
	}

	return array('stat'=>'ok', 'sync'=>$sync, 'modules'=>$local_modules);
}
?>
