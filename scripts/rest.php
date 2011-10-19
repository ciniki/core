<?php
//
// Description
// -----------
// The rest.php file is the entry point for the API through the REST protocol.
//

//
// Initialize Moss by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($ciniki_root . '/ciniki-api/core/private/init.php');
require_once($ciniki_root . '/ciniki-api/core/private/checkSecureConnection.php');
require_once($ciniki_root . '/ciniki-api/core/private/callPublicMethod.php');
require_once($ciniki_root . '/ciniki-api/core/private/printHashToXML.php');
require_once($ciniki_root . '/ciniki-api/core/private/printResponse.php');

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
// Once the REST specific stuff is done, pass the control to
// ciniki.core.callPublicMethod()
//
$rc = ciniki_core_callPublicMethod($ciniki);

//
// Output the result in requested format
//
ciniki_core_printResponse($ciniki, $rc);


exit;

?>
