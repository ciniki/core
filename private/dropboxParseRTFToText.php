<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function ciniki_core_dropboxParseRTFToText($ciniki, $business_id, $client, $path) {

	//
	// Get the file contents
	//
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSLVERSION, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $client->getAccessToken()));
	if( $path[0] != '/' ) { $path = '/' . $path; }
	curl_setopt($ch, CURLOPT_URL, "https://api-content.dropbox.com/1/files/auto$path");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
	$file_contents = curl_exec($ch);
	if( $file_contents === false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2282', 'msg'=>'Unable to get file'));
	}
	curl_close($ch);

	//
	// FIXME: Parse the RTF content
	//

	return array('stat'=>'ok', 'content'=>$file_contents);
}
?>
