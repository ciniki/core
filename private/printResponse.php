<?php
//
// Description
// -----------
// This function is a wrapper to format and output the response
// in the specified format.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// ciniki:		The ciniki internal variable.
// hash:		The hash structure to return as a response.
//
function ciniki_core_printResponse($ciniki, $hash) {

	if( !is_array($hash) ) {
		$rsp_hash = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'38', 'msg'=>'Internal configuration error'));
	} else {
		$rsp_hash = $hash;
	}

	//
	// If nothing is specified, or there's an error with the $ciniki data structure,
	// then default to response in XML rest format.
	//
	if( !is_array($ciniki) || !is_array($ciniki['response']) || !isset($ciniki['response']['format']) ) {
		header("Content-Type: text/xml; charset=utf-8");
		print "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/printHashToXML.php');
		ciniki_core_printHashToXML('rsp', '', $hash);	
	} 

	elseif( $ciniki['response']['format'] == 'php_serial' ) {
		header("Content-Type: text/plain; charset=utf-8");
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/printHashToPHP.php');
		ciniki_core_printHashToPHP($hash);
	} 

	elseif( $ciniki['response']['format'] == 'json' ) {
		header("Content-Type: text/plain; charset=utf-8");
		header("Cache-Control: no-cache, must-revalidate");
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/printHashToJSON.php');
		ciniki_core_printHashToJSON($hash);
	}

	//
	// Default to XML rest response format if nothing else is specified.
	//
	else {
		header("Content-Type: text/xml; charset=utf-8");
		print "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/printHashToXML.php');
		ciniki_core_printHashToXML('rsp', '', $hash);	
	}

}
?>
