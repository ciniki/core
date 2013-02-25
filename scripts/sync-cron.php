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
//require_once($ciniki_root . '/ciniki-api/cron/private/execCronMethod.php');
//require_once($ciniki_root . '/ciniki-api/cron/private/getExecutionList.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
	error_log("unable to initialize core");
	exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncCronList');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusiness');

//
// Get list of cron jobs
//
$rc = ciniki_core_syncCronList($ciniki);
if( $rc['stat'] != 'ok' ) {
	error_log("SYNC-ERR: unable to get cron list");
	exit(1);
}

if( !isset($rc['syncs']) ) {
	exit(0);
}

$syncs = $rc['syncs'];
$cur_time = $rc['cur_time'];
$cur_date = date_create('@' . $cur_time);
$cur_hour = date_format($cur_date, 'G');
$sync_full_hour = 3;
if( isset($ciniki['config']['ciniki.core']['sync.full.hour']) ) {
	$sync_full_hour = $ciniki['config']['ciniki.core']['sync.full.hour'];
}
$sync_partial_hour = 3;
if( isset($ciniki['config']['ciniki.core']['sync.partial.hour']) ) {
	$sync_partial_hour = $ciniki['config']['ciniki.core']['sync.partial.hour'];
}
foreach($rc['syncs'] as $sid => $sync) {
	//
	// For a copy of the script to handle each sync
	// Sleep for 2 seconds between each fork
	//
	switch ($pid = pcntl_fork()) {
		case -1:
			die('Fork failed');
			break;
		case 0:
			// if time since last full > 150 hours, and time is currently 3 am, run full
			if( $cur_hour == $sync_full_hour && ($cur_time - $sync['last_full']) > 540000 ) {
				error_log("SYNC-INFO: [" . $sync['business_id'] . '-' . $sync['id'] . "] Syncing full");
				$rc = ciniki_core_syncBusiness($ciniki, $sync['business_id'], $sync['id'], 'partial', '');
				if( $rc['stat'] != 'ok' ) {
					error_log("SYNC-ERR: [" . $sync['business_id'] . '-' . $sync['id'] . "] Unable to sync business (" . serialize($rc['err']) . ")");
					break;
				}
				error_log("SYNC-INFO: [" . $sync['business_id'] . '-' . $sync['id'] . "] Sync done");
			} 
			// if time since last partial > 23 hours, and time is currently 3 am, run parital
			elseif( $cur_hour == $sync_partial_hour && ($cur_time - $sync['last_full']) > 82800 ) {
				error_log("SYNC-INFO: [" . $sync['business_id'] . '-' . $sync['id'] . "] Syncing partial");
				$rc = ciniki_core_syncBusiness($ciniki, $sync['business_id'], $sync['id'], 'partial', '');
				if( $rc['stat'] != 'ok' ) {
					error_log("SYNC-ERR: [" . $sync['business_id'] . '-' . $sync['id'] . "] Unable to sync business (" . serialize($rc['err']) . ")");
					break;
				}
				error_log("SYNC-INFO: [" . $sync['business_id'] . '-' . $sync['id'] . "] Sync done");
			}
			// Default to a incremental sync
			else {
				error_log("SYNC-INFO: [" . $sync['business_id'] . '-' . $sync['id'] . "] Syncing incremental");
				$rc = ciniki_core_syncBusiness($ciniki, $sync['business_id'], $sync['id'], 'incremental', '');
				if( $rc['stat'] != 'ok' ) {
					error_log("SYNC-ERR: [" . $sync['business_id'] . '-' . $sync['id'] . "] Unable to sync business (" . serialize($rc['err']) . ")");
					break;
				}
				error_log("SYNC-INFO: [" . $sync['business_id'] . '-' . $sync['id'] . "] Syncing done");
			}
			break;
		default:
			sleep(5);	
			break;
	}
}

exit(0);
?>
