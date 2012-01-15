<?php
//
// Description
// -----------
// This function will execute a remote API call
//
function ciniki_core_cinikiAPIPost($ciniki, $api, $method, $remote_args, $content) {
	$request_url = $api['url'] . "?method=" . urlencode($method) . "&api_key=" . urlencode($api['key']);
	if( $api['token'] != '' ) {
		$request_url .= "&auth_token=" . $api['token'];
	}
	if( is_array($remote_args) ) {
		foreach($remote_args as $arg_name => $arg_value) {
			$request_url .= "&" . urlencode($arg_name) . "=" . urlencode($arg_value);
		}
	}
	$request_url .= "&format=php";

	//
	// Setup the curl request
	//
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_URL, $request_url);

	//
	// Check for content
	//
	if( $content != null ) {
		curl_setopt($ch, CURLOPT_POST, false);
		if( is_array($content) ) {
			$post_content = "";
			foreach($content as $arg_name => $arg_value) {
				$post_content .= urlencode($arg_name) . "=" . urlencode($arg_value) . "&";
			}
		}
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_content);
	}

	//
	// Make the request
	//
	$rsp = curl_exec($ch);
	if( $rsp === false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'540', 'msg'=>'Unable to connect to remote system'));
	}
	curl_close($ch);

	$rc = unserialize($rsp);
	if( isset($rc['stat']) ) {
		return $rc;
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'539', 'msg'=>'Unable to understand the response'));
}	
?>
