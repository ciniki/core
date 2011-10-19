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
// moss:		The moss internal variable.
// hash:		The hash structure to return as a response.
//
function moss_core_printResponse($moss, $hash) {

	if( !is_array($hash) ) {
		$rsp_hash = array('stat'=>'fail', 'err'=>array('code'=>'38', 'msg'=>'Internal configuration error'));
	} else {
		$rsp_hash = $hash;
	}

	//
	// If nothing is specified, or there's an error with the $moss data structure,
	// then default to response in XML rest format.
	//
	if( !is_array($moss) || !is_array($moss['response']) || !isset($moss['response']['format']) ) {
		header("Content-Type: text/xml; charset=utf-8");
		print "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
		require_once($moss['config']['core']['modules_dir'] . '/core/private/printHashToXML.php');
		moss_core_printHashToXML('rsp', '', $hash);	
	} 

	elseif( $moss['response']['format'] == 'php_serial' ) {
		header("Content-Type: text/plain; charset=utf-8");
		require_once($moss['config']['core']['modules_dir'] . '/core/private/printHashToPHP.php');
		moss_core_printHashToPHP($hash);
	} 

	elseif( $moss['response']['format'] == 'json' ) {
		header("Content-Type: text/plain; charset=utf-8");
		header("Cache-Control: no-cache, must-revalidate");
		require_once($moss['config']['core']['modules_dir'] . '/core/private/printHashToJSON.php');
		moss_core_printHashToJSON($hash);
	}

	//
	// Default to XML rest response format if nothing else is specified.
	//
	else {
		header("Content-Type: text/xml; charset=utf-8");
		print "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
		require_once($moss['config']['core']['modules_dir'] . '/core/private/printHashToXML.php');
		moss_core_printHashToXML('rsp', '', $hash);	
	}

}
?>
