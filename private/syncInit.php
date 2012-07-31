<?php
//
// Description
// -----------
// This function will initialize the sync, and pull the request from the POST content
//
// Arguments
// ---------
// config_file:			The path to the config file must be passed.
//
function ciniki_core_syncInit($ciniki_root) {

	//
	// Initialize the ciniki structure, and setup the return value
	// to include the stat.
	//
	$ciniki = array();

	//
	// Load the config
	//
	require_once($ciniki_root . '/ciniki-api/core/private/loadCinikiConfig.php');
	if( ciniki_core_loadCinikiConfig($ciniki, $ciniki_root) == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'40', 'msg'=>'Internal configuration error'));
	}

	//
	// Initialize Database
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInit.php');
	$rc = ciniki_core_dbInit($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// The synctype (type), business UUID (uuid) must be specifed in the URL
	//
	if( !isset($_GET) || !is_array($_GET)  ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'47', 'msg'=>'Internal configuration error'));
	}

	//
	// Check the request, make sure it's valid
	// We only allow the sync type of business right now.
	// The remote end must pass their business uuid, so we know which sync connection to use.
	//
	if( !isset($_GET['type']) || $_GET['type'] != 'business' 
		|| !isset($_GET['uuid']) || $_GET['uuid'] == '' 
		|| !isset($_GET['from']) || $_GET['from'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'48', 'msg'=>'Internal configuration error'));
	}
	$ciniki['sync'] = array('type'=>$_GET['type'], 'local_uuid'=>$_GET['uuid'], 'remote_uuid'=>$_GET['from']);

	//
	// Get the local_private_key to decode the request
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.uuid, ciniki_business_syncs.flags, "
		. "local_private_key, remote_public_key "
		. "FROM ciniki_businesses, ciniki_business_syncs "
		. "WHERE ciniki_businesses.uuid = '" . ciniki_core_dbQuote($ciniki, $ciniki['sync']['local_uuid']) . "' "
		. "AND ciniki_businesses.id = ciniki_business_syncs.business_id "
		. "AND ciniki_business_syncs.status = 10 "		// Make sure it is an active sync
		. "AND ciniki_business_syncs.remote_uuid = '" . ciniki_core_dbQuote($ciniki, $ciniki['sync']['remote_uuid']) . "' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sync']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'51', 'msg'=>'Internal configuration error'));
	}
	$local_private_key = $rc['sync']['local_private_key'];
	$ciniki['sync']['remote_public_key'] = $rc['sync']['remote_public_key'];
	$ciniki['sync']['business_id'] = $rc['sync']['id'];

	//
	// unserialize the POST content
	//
	if( isset($_POST) && is_array($_POST) ) {
		$encrypted_content = file_get_contents("php://input");

		// unencrypt
		if( !openssl_private_decrypt($encrypted_content, $decrypted_content, $local_private_key) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'52', 'msg'=>'Internal configuration error'));
		}
		
		// unserialize
		$request = unserialize($decrypted_content);
		if( !is_array($request) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'53', 'msg'=>'Internal configuration error'));
		}
		$ciniki['request'] = $request;

		//
		// Check the ts to make sure it's within 1 minute of UTC
		// This makes sure that the request is current, and not a cut and paste, listening in the middle.  If
		// the timestamp decrypts and is accurate, it is assumed the request is valid.
		//
		if( !isset($ciniki['request']['ts']) 
			|| $ciniki['request']['ts'] <= 0 
			|| abs(gmmktime() - $ciniki['request']['ts']) > 60 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'54', 'msg'=>'Internal configuration error'));
		}
		if( !isset($ciniki['request']['action']) 
			|| $ciniki['request']['action'] == ''
			) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'55', 'msg'=>'No action specified'));
		}
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'49', 'msg'=>'Invalid request'));
	}

	return array('stat'=>'ok', 'ciniki'=>$ciniki);
}
?>
