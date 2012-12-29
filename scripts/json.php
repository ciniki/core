<?php
//
// Description
// -----------
// The json.php file is the entry point for the API through the REST protocol.
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
require_once($ciniki_root . '/ciniki-api/core/private/init.php');
require_once($ciniki_root . '/ciniki-api/core/private/checkSecureConnection.php');
require_once($ciniki_root . '/ciniki-api/core/private/callPublicMethod.php');
require_once($ciniki_root . '/ciniki-api/core/private/printHashToJSON.php');
require_once($ciniki_root . '/ciniki-api/core/private/printResponse.php');
require_once($ciniki_root . '/ciniki-api/core/private/syncQueueProcess.php');

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
require_once($ciniki_root . '/ciniki-api/core/private/parseRestArguments.php');
$rc = ciniki_core_parseRestArguments($ciniki);
if( $rc['stat'] != 'ok' ) {
	ciniki_core_printResponse($ciniki, $rc);
	exit;
}

//
// Once the REST specific stuff is done, pass the control to
// ciniki.core.callPublicMethod()
//
$rc = ciniki_core_callPublicMethod($ciniki);

//
// Check if there is a sync queue to process
//
if( isset($ciniki['syncqueue']) && count($ciniki['syncqueue']) > 0 ) {
	ob_start();
	ciniki_core_printResponse($ciniki, $rc);
	$contentlength = ob_get_length();
	header("Content-Length: $contentlength");
	header("Connection: Close");
	ob_end_flush();
	ob_flush();
	flush();
	session_write_close();

	// Run queue
	ciniki_core_syncQueueProcess($ciniki);

} else {
	//
	// Output the result in requested format
	//
	ciniki_core_printResponse($ciniki, $rc);
}

exit;

?>
