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
function ciniki_core_syncRequest(&$ciniki, &$sync, $request) {

	$request_url = $sync['remote_url'] . "?type=" . $sync['type'] . "&uuid=" . $sync['remote_uuid'] . "&from=" . $sync['local_uuid'];

	//
	// Setup the curl request
	//
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_URL, $request_url);

	if( !is_array($request) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'549', 'msg'=>'Invalid request'));
	}

	if( !isset($request['method']) || $request['method'] == '' ) {
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
//	if( !openssl_public_encrypt($post_content, $encrypted_content, $sync['remote_public_key']) ) {
	if( !openssl_seal($post_content, $encrypted_content, $keys, array($sync['remote_public_key'])) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'542', 'msg'=>'Invalid request'));
	}

	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode($keys[0]) . ':::' . base64_encode($encrypted_content));

	//
	// Make the request
	//
	$rsp = curl_exec($ch);
	if( $rsp === false ) {
		if( curl_errno($ch) == 28 ) {
			$rsp = curl_exec($ch);
		}
		if( $rsp === false ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'543', 'msg'=>'Unable to connect to remote system (' . curl_errno($ch) . ':' . curl_error($ch) . ')'));
		}
	}
	curl_close($ch);

	$arsp = preg_split('/:::/', $rsp);
	if( count($arsp) != 2 || !isset($arsp[1]) ) {
		$rc = unserialize($rsp);
		if( $rc !== false ) {
			if( $rc['stat'] == 'ok' ) {
				return $rc;
			}
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'573', 'msg'=>'Error response', 'err'=>$rc['err']));
		}
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'902', 'msg'=>'Invalid response'));
	}

	//
	// Decrypt the response
	//
	if( !openssl_open(base64_decode($arsp[1]), $decrypted_content, base64_decode($arsp[0]), $sync['local_private_key']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'544', 'msg'=>'Invalid response', 'pmsg'=>$rsp));
	}

	$rc = unserialize($decrypted_content);
	if( isset($rc['stat']) ) {
		return $rc;
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'545', 'msg'=>'Unable to understand the response'));
}
?>
