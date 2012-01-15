<?php
//
// Description
// -----------
// This function will encrypt and make the request to the remote system,
// then unencrypt the response.
//
// Arguments
// ---------
//
function ciniki_core_syncRequest($ciniki, $sync, $request) {

	$request_url = $sync['remote_url'] . "?type=" . $sync['type'] . "&uuid=" . $sync['remote_uuid'] . "&from=" . $sync['local_uuid'];

	//
	// Setup the curl request
	//
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_URL, $request_url);

	if( !is_array($request) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'542', 'msg'=>'Invalid request'));
	}

	if( !isset($request['action']) || $request['action'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'541', 'msg'=>'Invalid request'));
	}

	$request['ts'] = gmmktime();

	//
	// Serialize the request
	//
	$post_content = serialize($request);

	// 
	// Encrypt the request
	//
	if( !openssl_public_encrypt($post_content, $encrypted_content, $sync['remote_public_key']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'542', 'msg'=>'Invalid request'));
	}

	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $encrypted_content);

	//
	// Make the request
	//
	$rsp = curl_exec($ch);
	if( $rsp === false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'543', 'msg'=>'Unable to connect to remote system'));
	}
	curl_close($ch);

	//
	// Decrypt the response
	//
	if( !openssl_private_decrypt($rsp, $decrypted_content, $sync['local_private_key']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'544', 'msg'=>'Invalid response'));
	}

	$rc = unserialize($decrypted_content);
	if( isset($rc['stat']) ) {
		return $rc;
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'545', 'msg'=>'Unable to understand the response'));
}
?>
