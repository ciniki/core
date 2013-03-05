<?php
//
// Description
// -----------
// This script should be executed from cron every 5 minutes to run
// an incremental sync on all businesses.
// 

//
// Initialize Moss by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
	$ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-api/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-api/core/private/init.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
	error_log("unable to initialize core");
	exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusiness');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLock');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUnlock');

if( isset($argv[1]) && $argv[1] != '' 
	&& isset($argv[2]) && $argv[2] != '' 
	&& isset($argv[3]) && $argv[3] != '' ) {
	if( $argv[3] != 'incremental' && $argv[3] != 'partial' && $argv[3] != 'full' ) {
		error_log('Unrecognized sync type');
		exit(1);
	}
	$business_id = $argv[1];
	$sync_id = $argv[2];
	$type = $argv[3];
	//
	// Setup logging
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLog');
	if( isset($ciniki['config']['ciniki.core']['sync.log_dir']) ) {
		$ciniki['synclogfile'] = $ciniki['config']['ciniki.core']['sync.log_dir'] . "/sync-$sync_id.log";
	}
	$ciniki['synclogprefix'] = "[$business_id-$sync_id]";

	$rc = ciniki_core_syncLock($ciniki, $business_id, $sync_id);
	if( $rc['stat'] == 'lockexists' ) {
		return array('stat'=>'ok');
	}
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	ciniki_core_syncLog($ciniki, 1, "Syncing $type", null);
	$rc = ciniki_core_syncBusiness($ciniki, $business_id, $sync_id, $type, '');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_syncLog($ciniki, 0, "Unable to sync business (" . serialize($rc['err']) . ")", $rc['err']);
		ciniki_core_syncUnlock($ciniki, $business_id, $sync_id);
		exit(2);
	}
	ciniki_core_syncUnlock($ciniki, $business_id, $sync_id);
	ciniki_core_syncLog($ciniki, 1, "Sync done", null);
} else {
	error_log("SYNC-ERR: Unrecognized args");
}

exit(0);
?>
