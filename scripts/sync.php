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

// file_put_contents('/Users/andrew/tmp.sync', print_r($ciniki, true));

//
// Find out the command being requested
//
if( $ciniki['request']['method'] == 'ciniki.core.ping' ) {
	$response = array('stat'=>'ok');
} elseif( $ciniki['request']['method'] == 'ciniki.core.info' ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusinessInfo');
	$response = ciniki_core_syncBusinessInfo($ciniki, $ciniki['sync']['business_id']);
} elseif( $ciniki['request']['method'] == 'ciniki.core.delete' ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncDelete');
	$response = ciniki_core_syncDelete($ciniki, $ciniki['sync']['business_id'], $ciniki['sync']['id']);
} elseif( preg_match('/(.*)\.(.*)\.(.*)\.(list|get|update|delete)$/', $ciniki['request']['method'], $matches) ) {
	//
	// Parse the method, and the function name.  
	//
	$filename = '/' . $matches[1] . '-api/' . $matches[2] . '/sync/' . $matches[3] . '_' . $matches[4] . '.php';
	$method_function = $matches[1] . '_' . $matches[2] . '_' . $matches[3] . '_' . $matches[4];
//	$filename = preg_replace('/^(.*)\.(.*)\.(.*)\.(.*)$/', '/\1-api/\2/sync/\3_\4.php', $ciniki['request']['method']);
//	$method_function = preg_replace('/^(.*)\.(.*)\.(.*)\.(.*)$/', '\1_\2_\3\_\4', $ciniki['request']['method']);
	if( file_exists($ciniki['config']['ciniki.core']['root_dir'] . $filename) ) {
		require_once($ciniki['config']['ciniki.core']['root_dir'] . $filename);
		if( is_callable($method_function) ) {
			$response = $method_function($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $ciniki['request']);
		} else {
			$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'995', 'msg'=>'Method does not exist: ' . $ciniki['request']['method']));
		}
	} else {
		error_log($filename);
		$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'996', 'msg'=>'Method does not exist: ' . $ciniki['request']['method']));
	}
//} elseif( preg_match('/.*\..*\.(.*List|.*Get|.*Update|.*Add)$/', $ciniki['request']['method']) ) {
//	//
//	// Parse the method, and the function name.  
//	//
//	$filename = preg_replace('/^(.*)\.(.*)\.(.*)$/', '/\1-api/\2/sync/\3.php', $ciniki['request']['method']);
//	$method_function = preg_replace('/^(.*)\.(.*)\.(.*)$/', '\1_\2_sync_\3', $ciniki['request']['method']);
//	if( file_exists($ciniki['config']['ciniki.core']['root_dir'] . $filename) ) {
//		require_once($ciniki['config']['ciniki.core']['root_dir'] . $filename);
//		if( is_callable($method_function) ) {
//			$response = $method_function($ciniki, $ciniki['sync'], $ciniki['sync']['business_id'], $ciniki['request']);
//		} else {
//			$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'50', 'msg'=>'Method does not exist'));
//		}
//	} else {
//		$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'56', 'msg'=>'Method does not exist'));
//	}
} else {
	$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'547', 'msg'=>'Invalid method'));
}

//
// Check if there is a sync queue to process
//
if( isset($ciniki['syncqueue']) && count($ciniki['syncqueue']) > 0 ) {
	ob_start();
	$rc = ciniki_core_syncResponse($ciniki, $response);
	if( $rc['stat'] != 'ok' ) {
		print serialize($rc);
	}
	$contentlength = ob_get_length();
	header("Content-Length: $contentlength");
	header("Connection: Close");
	ob_end_flush();
	ob_flush();
	flush();
	session_write_close();

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
