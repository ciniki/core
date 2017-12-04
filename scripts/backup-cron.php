<?php
//
// Description
// -----------
// This script should be executed once a day to backup each tenants information
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
    $ciniki['config']['ciniki.core']['zip_backup_dir'] = $argv[1];
}

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupTenantList');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupTenant');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupTenantModuleObjects');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupTenantModuleObject');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'backupZipAddDir');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');

ini_set('memory_limit', '4192M');
require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');

//
// Get list of cron jobs
//
$rc = ciniki_core_backupTenantList($ciniki);
if( $rc['stat'] != 'ok' ) {
    error_log("BACKUP-ERR: unable to get tenant list");
    exit(1);
}

if( !isset($rc['tenants']) ) {
    error_log('No tenants to backup');
    exit(0);
}

$tenants = $rc['tenants'];

foreach($tenants as $bid => $tenant) {

    error_log("Backing up tenant: " . $tenant['name']);
    $rc = ciniki_core_backupTenant($ciniki, $tenant);
    if( $rc['stat'] != 'ok' ) {
        error_log('BACKUP-ERR: ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
    }
    
    foreach($ciniki['databases'] as $db_name => $db) {
        if( isset($db['connection']) ) {
            mysqli_close($db['connection']);
            unset($ciniki['databases'][$db_name]['connection']);
        }
    }
}

exit(0);
?>
