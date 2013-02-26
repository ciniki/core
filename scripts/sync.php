<?php
//
// Description
// -----------
// This script is the entry point for the syncronization subsystem, which 
// allows business information to be syncronized between installations.
// This can provide both migrations and backup services.
//
// The sync system doesn't require the same api-key, but instead uses a sync key (future).
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
require_once($ciniki_root . '/ciniki-api/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-api/core/private/syncInit.php');
require_once($ciniki_root . '/ciniki-api/core/private/checkSecureConnection.php');
require_once($ciniki_root . '/ciniki-api/core/private/printHashToPHP.php');
require_once($ciniki_root . '/ciniki-api/core/private/syncResponse.php');

//
// The syncInit function will initialize the ciniki structure, and check
// the security for the request to the business
//
$rc = ciniki_core_syncInit($ciniki_root);
if( $rc['stat'] != 'ok' ) {
	header("Content-Type: text/plain; charset=utf-8");
	ciniki_core_printHashToPHP($rc);
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
	ciniki_core_printHashToPHP($ciniki, $rc);
	exit;
}

//
// Setup logging
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLog');
//if( isset($ciniki['config']['ciniki.core']['sync.log_dir']) ) {
//	$ciniki['synclogfile'] = $ciniki['config']['ciniki.core']['sync.log_dir'] . "/sync-$sync_id.log";
//}
//$ciniki['synclogprefix'] = "[$business_id-$sync_id]";

//
// Find out the command being requested
//

//
// The ping command will simply return ok.  It means the 
// secure handshake is ok
//
if( $ciniki['request']['method'] == 'ciniki.core.ping' ) {
	$response = array('stat'=>'ok');
} 

//
// The info command will return the business info for the local business.  This
// is used to check versions between the systems.
//
elseif( $ciniki['request']['method'] == 'ciniki.core.info' ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusinessInfo');
	$response = ciniki_core_syncBusinessInfo($ciniki, $ciniki['sync']['business_id']);
} 

//
// The tables command will return the list of tables and the current number
// of rows for the business.  The tables are organized by module
//
elseif( $ciniki['request']['method'] == 'ciniki.core.rowCounts' ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetRowCounts');
	$response = ciniki_core_dbGetRowCounts($ciniki, $ciniki['sync']['business_id']);
} 

//
// If the sync is to be removed, this will remove it from the local business
//
elseif( $ciniki['request']['method'] == 'ciniki.core.delete' ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncDelete');
	$response = ciniki_core_syncDelete($ciniki, $ciniki['sync']['business_id'], $ciniki['sync']['id']);
} 

//
// Check if a history command has been sent
//
elseif( preg_match('/(.*)\.(.*)\.(.*)\.history\.(list|get|update)$/', $ciniki['request']['method'], $matches) ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
	$rc = ciniki_core_syncObjectLoad($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $ciniki['request']['method'], array());
	if( $rc['stat'] != 'ok' ) {
		$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1213', 'msg'=>'Object does not exist'));
	} else {
		$o = $rc['object'];
		if( $matches[4] == 'list' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectHistoryList');
			$response = ciniki_core_syncObjectHistoryList($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $o, $ciniki['request']);
		} elseif( $matches[4] == 'get' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectHistoryGet');
			$response = ciniki_core_syncObjectHistoryGet($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $o, $ciniki['request']);
		} elseif( $matches[4] == 'update' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectHistoryUpdate');
			$response = ciniki_core_syncObjectHistoryUpdate($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $o, $ciniki['request']);
		} else {
			$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1214', 'msg'=>'Object does not exist'));
		}
	}
} 

//
// An object command has been sent
//
elseif( preg_match('/(.*)\.(.*)\.(.*)\.(list|get|update|delete)$/', $ciniki['request']['method'], $matches) ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
	$rc = ciniki_core_syncObjectLoad($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $ciniki['request']['method'], array());
	if( $rc['stat'] != 'ok' ) {
		$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1216', 'msg'=>'Object does not exist', 'err'=>$rc['err']));
	} else {
		$o = $rc['object'];
		if( $matches[4] == 'list' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectList');
			$response = ciniki_core_syncObjectList($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $o, $ciniki['request']);
		} elseif( $matches[4] == 'get' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectGet');
			$response = ciniki_core_syncObjectGet($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $o, $ciniki['request']);
		} elseif( $matches[4] == 'update' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectUpdate');
			$response = ciniki_core_syncObjectUpdate($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $o, $ciniki['request']);
		} elseif( $matches[4] == 'delete' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectDelete');
			$response = ciniki_core_syncObjectDelete($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $o, $ciniki['request']);
		} else {
			$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1217', 'msg'=>'Object does not exist'));
		}
	}
} 

//
// When none of the commands are recognized, return an error
//
else {
	$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'547', 'msg'=>'Invalid method'));
}

//
// Check if there is a sync queue to process
//
if( isset($ciniki['syncqueue']) && count($ciniki['syncqueue']) > 0 ) {
	ob_start();
	if( !ob_start("ob_gzhandler")) {
		ob_start();		// Inner buffer when output is apache mod-deflate is enabled
	}
	$rc = ciniki_core_syncResponse($ciniki, $response);
	if( $rc['stat'] != 'ok' ) {
		print serialize($rc);
	}
	ob_end_flush();
	header("Connection: close");
	ob_end_flush();
	$contentlength = ob_get_length();
	header("Content-Length: $contentlength");
	ob_end_flush();
	flush();
	session_write_close();
	while(ob_get_level()>0) ob_end_clean();

	// Run queue
	if( isset($ciniki['sync']['business_id']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncQueueProcess');
		ciniki_core_syncQueueProcess($ciniki, $ciniki['sync']['business_id']);
	}

} else {
	//
	// Output the result in requested format
	//
	$rc = ciniki_core_syncResponse($ciniki, $response);
	if( $rc['stat'] != 'ok' ) {
		print serialize($rc);
	}
}

exit;

?>
