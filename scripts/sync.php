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
require_once($ciniki_root . '/ciniki-api/core/private/syncInit.php');
require_once($ciniki_root . '/ciniki-api/core/private/checkSecureConnection.php');
require_once($ciniki_root . '/ciniki-api/core/private/printHashToPHP.php');
require_once($ciniki_root . '/ciniki-api/core/private/syncResponse.php');
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-api/core/private/loadMethod.php');

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

file_put_contents('/Users/andrew/tmp.sync', print_r($ciniki, true));

//
// Find out the command being requested
//
if( $ciniki['request']['action'] == 'ping' ) {
	$response = array('stat'=>'ok');
} elseif( $ciniki['request']['action'] == 'info' ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusinessInfo');
	$response = ciniki_core_syncBusinessInfo($ciniki, $ciniki['sync']['business_id']);
} elseif( $ciniki['request']['action'] == 'list' ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncList');
	$response = ciniki_core_syncList($ciniki);
} elseif( $ciniki['request']['action'] == 'get' ) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncGet');
	$response = ciniki_core_syncGet($ciniki);
} else {
	$response = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'547', 'msg'=>'Invalid action'));
}

//
// Output the result in requested format
//
$rc = ciniki_core_syncResponse($ciniki, $response);
if( $rc['stat'] != 'ok' ) {
	print serialize($rc);
}

exit;

?>
