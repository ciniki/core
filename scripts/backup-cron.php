<?php
//
// Description
// -----------
// This script should be executed once a day to backup each businesses information
// to the ciniki-backups folder.
// 

//
// Initialize Ciniki by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
	$ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
//require_once($ciniki_root . '/ciniki-mods/cron/private/execCronMethod.php');
//require_once($ciniki_root . '/ciniki-mods/cron/private/getExecutionList.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
	error_log("unable to initialize core");
	exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];

if( isset($argv[1]) && $argv[1] != '' ) {
	$ciniki['config']['ciniki.core']['backup_dir'] = $argv[1];
}

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupBusinessList');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupBusiness');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupBusinessModuleObjects');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupBusinessModuleObject');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupZipAddDir');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');

ini_set('memory_limit', '4192M');
require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');

//
// Get list of cron jobs
//
$rc = ciniki_core_backupBusinessList($ciniki);
if( $rc['stat'] != 'ok' ) {
	error_log("BACKUP-ERR: unable to get business list");
	exit(1);
}

if( !isset($rc['businesses']) ) {
	error_log('No businesses to backup');
	exit(0);
}

$businesses = $rc['businesses'];

foreach($businesses as $bid => $business) {
	$rc = ciniki_core_backupBusiness($ciniki, $business);
	if( $rc['stat'] != 'ok' ) {
		error_log('BACKUP-ERR: ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
	}
}

exit(0);
?>
