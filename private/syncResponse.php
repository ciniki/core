<?php
//
// Description
// -----------
// This function will encrypt and return a response to the remote sync.
//
//
// Arguments
// ---------
//
function ciniki_core_syncResponse($ciniki, $hash) {
	if( !is_array($hash) ) {
		$rsp_hash = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'68', 'msg'=>'Internal configuration error'));
	} else {
		$rsp_hash = $hash;
	}

	if( !isset($ciniki['sync']['remote_public_key']) 
		|| $ciniki['sync']['remote_public_key'] == '' ) {
		$rsp_hash = array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'71', 'msg'=>'Internal configuration error'));
	}

	//
	// Serialize the response
	//
	$unencrypted_response = serialize($rsp_hash);

	//
	// Encrypt the response, using the remote public key
	//
	if( !openssl_public_encrypt($unencrypted_response, $encrypted_response, $ciniki['sync']['remote_public_key']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'999', 'msg'=>'Invalid request'));
	}

	header("Content-Type: text/plain; charset=utf-8");
	print $encrypted_response;

	return array('stat'=>'ok');
}
?>
