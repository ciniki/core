<?php
//
// Description
// -----------
// The rest.php file is the entry point for the API through the REST protocol.
//

//
// Initialize Ciniki by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
	$ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
require_once($ciniki_root . '/ciniki-api/core/private/init.php');
require_once($ciniki_root . '/ciniki-api/core/private/checkSecureConnection.php');
require_once($ciniki_root . '/ciniki-api/core/private/callPublicMethod.php');
require_once($ciniki_root . '/ciniki-api/core/private/printHashToXML.php');
require_once($ciniki_root . '/ciniki-api/core/private/printResponse.php');
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-api/core/private/loadMethod.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
	header("Content-Type: text/xml; charset=utf-8");
	print "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
	ciniki_core_printHashToXML('rsp', '', $rc);
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
// Log all connections
//
error_log(print_r($ciniki['request']['args'], true));

//
// Handle transaction types
//
require_once($ciniki_root . '/ciniki-api/businesses/private/processPaypalIPN.php');
$rc = ciniki_businesses_processPaypalIPN($ciniki);
ciniki_core_printResponse($ciniki, $rc);

exit;

?>
