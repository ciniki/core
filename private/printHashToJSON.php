<?php
//
// Description
// -----------
// This function will serialize a hash structure to return to the client.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// name: 			The name the top level should be
// indent:			The string for indentation, which should be spaces.  Each recursive call added 4 spaces.
// hash:			The array of array's to turn into xml.
//
//
function moss_core_printHashToJSON($hash) {
	print json_encode($hash);	
}
?>
