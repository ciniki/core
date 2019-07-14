<?php
//
// Description
// -----------
// This script exports the ciniki_core_api_logs to the log directory
//

//
// This script should run as www-data and will create the setup for an apache ssl domain
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
ciniki_core_loadMethod($ciniki, 'ciniki', 'cron', 'private', 'logMsg');

if( isset($ciniki['config']['ciniki.core']['logging.api.dir']) ) {
    $log_dir = $ciniki['config']['ciniki.core']['logging.api.dir'] . '/ciniki.core';
} else {
    $log_dir = $ciniki['config']['ciniki.core']['log_dir'] . '/ciniki.core';
}
if( !file_exists($log_dir) ) {
    mkdir($log_dir);
}

//
// Pull the list of history for each item
//
$dt = new DateTime('now');
for($year = 2010; $year <= $dt->format('Y'); $year++) {
    $strsql = "SELECT id, "
        . "user_id, tnid, session_key, method, action, ip_address, "
        . "DATE_FORMAT(log_date, '%d/%m/%Y:%H:%i:%s') AS logdate, "
        . "DATE_FORMAT(log_date, '%Y-%m') AS logfile "
        . "FROM ciniki_core_api_logs "
        . "WHERE YEAR(log_date) = '" . ciniki_core_dbQuote($ciniki, $year) . "' "
        . "ORDER BY log_date "
        . "";
    error_log('pull list for ' . $year);
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.402', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $logs = isset($rc['rows']) ? $rc['rows'] : array();

    error_log('processing ' . $year);
    foreach($logs as $row) {
        $msg = '[' . $row['logdate'] . ' +0000]';
        if( isset($row['ip_address']) && $row['ip_address'] != '' ) {
            $msg .= " " . ciniki_core_dbQuote($ciniki, $row['ip_address']);
        } else {
            $msg .= " -";
        }
        $msg .= " " . $row['tnid'];
        $msg .= " (" . $row['user_id'] . ")";
        $msg .= " " . ($row['session_key'] != '' ? $row['session_key'] : '-');
        $msg .= " " . ($row['method'] != '' ? $row['method'] : '-');
        $msg .= " " . ($row['action'] != '' ? $row['action'] : '-');

        file_put_contents($log_dir . '/api.' . $row['logfile'] . '.log',
            $msg . "\n", 
            FILE_APPEND);
    }
}


exit(0);
?>
