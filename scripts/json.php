<?php
//
// Description
// -----------
// The json.php file is the entry point for the API through the REST protocol.
//

//
// Initialize Ciniki by including the ciniki_api.php
//
$start_time = microtime(true);
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
ini_set('display_errors', 'off');
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkSecureConnection.php');
require_once($ciniki_root . '/ciniki-mods/core/private/callPublicMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/printHashToJSON.php');
require_once($ciniki_root . '/ciniki-mods/core/private/printResponse.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    header("Content-Type: text/xml; charset=utf-8");
    ciniki_core_printHashToJSON($rc);
    exit;
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];

//
// Ensure the connection is over SSL
//
$rc = ciniki_core_checkSecureConnection($ciniki);
if( $rc['stat'] != 'ok' ) {
    ciniki_core_printResponse($ciniki, $rc);
    exit;
}

//
// Parse arguments
//
require_once($ciniki_root . '/ciniki-mods/core/private/parseRestArguments.php');
$rc = ciniki_core_parseRestArguments($ciniki);
if( $rc['stat'] != 'ok' ) {
    ciniki_core_printResponse($ciniki, $rc);
    exit;
}

//
// Check if maintanence mode
//
if( isset($ciniki['config']['ciniki.core']['maintenance']) && $ciniki['config']['ciniki.core']['maintenance'] == 'on' ) {
    if( isset($ciniki['config']['ciniki.core']['maintenance.message']) && $ciniki['config']['ciniki.core']['maintenance.message'] != '' ) {
        $msg = $ciniki['config']['ciniki.core']['maintenance.message'];
    } else {
        $msg = "We are currently doing maintenance on the system and will be back soon.";
    }
    ciniki_core_printResponse($ciniki, array('stat'=>'fail', 'err'=>array('code'=>'', 'msg'=>$msg)));
    exit;
}

//
// Once the REST specific stuff is done, pass the control to
// ciniki.core.callPublicMethod()
//
$rc = ciniki_core_callPublicMethod($ciniki);
if( isset($ciniki['config']['ciniki.core']['api.log.errors']) 
    && $ciniki['config']['ciniki.core']['api.log.errors'] == 1
    && $rc['stat'] != 'ok' && isset($rc['err'])
    ) {
    error_log(print_r($rc['err'], true));
}

//
// Check if there is a sync queue to process or email queue to process
//
if( (isset($ciniki['syncqueue']) && count($ciniki['syncqueue']) > 0) 
    || (isset($ciniki['fbrefreshqueue']) && count($ciniki['fbrefreshqueue']) > 0) 
    || (isset($ciniki['smsqueue']) && count($ciniki['smsqueue']) > 0) 
    || (isset($ciniki['emailqueue']) && count($ciniki['emailqueue']) > 0) 
    ) {
    if( $rc['stat'] != 'exit' ) {
        ob_start();
        if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) 
            && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            ob_start("ob_gzhandler"); // Inner buffer when output is apache mod-deflate is enabled
            ciniki_core_printResponse($ciniki, $rc);
            ob_end_flush();
        } else {
            ciniki_core_printResponse($ciniki, $rc);
        }
        header("Connection: close");
        $contentlength = ob_get_length();
        header("Content-Length: $contentlength");
        ob_end_flush();
        ob_end_flush();
        flush();
        session_write_close();
        while(ob_get_level()>0) ob_end_clean();
    }

    // Run sms queue
    if( isset($ciniki['smsqueue']) && count($ciniki['smsqueue']) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'smsQueueProcess');
        ciniki_core_smsQueueProcess($ciniki);
    } 
    // Run email queue
    if( isset($ciniki['emailqueue']) && count($ciniki['emailqueue']) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'emailQueueProcess');
        ciniki_core_emailQueueProcess($ciniki);
    } 
    // Run facebook refresh queue
    if( isset($ciniki['fbrefreshqueue']) && count($ciniki['fbrefreshqueue']) > 0 ) {
//          FIXME: Facebook is blocking requests direct to this script
//        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'fbRefreshQueueProcess');
//        ciniki_core_fbRefreshQueueProcess($ciniki);
    } 
    // Run sync queue
    if( isset($ciniki['syncqueue']) && count($ciniki['syncqueue']) > 0 ) {
        if( isset($ciniki['synctenants']) && count($ciniki['synctenants']) > 0 ) {
            foreach($ciniki['synctenants'] as $tnid) {
                ciniki_core_syncQueueProcess($ciniki, $tnid);
            }
        } elseif( isset($ciniki['request']['args']['tnid']) ) {
            ciniki_core_syncQueueProcess($ciniki, $ciniki['request']['args']['tnid']);
        } 
    }
} else {
    //
    // Output the result in requested format
    //
    if( $rc['stat'] != 'exit' ) {
        ciniki_core_printResponse($ciniki, $rc);
    }
}

//
// Capture errors in the database for easy review
//
if( $rc['stat'] == 'fail' ) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbLogError');
    ciniki_core_dbLogError($ciniki, $rc['err']);
}
if( isset($ciniki['config']['ciniki.core']['microtime']) 
    && $ciniki['config']['ciniki.core']['microtime'] == 'yes') {
    $end_time = microtime(true);
    error_log("PROF: microtime $end_time - $start_time = " . ($end_time - $start_time));
}

exit;

?>
