<?php
//
// Description
// -----------
// This function will execute a remote API call
//
function ciniki_core_cinikiAPIGet($ciniki, $api, $method, $remote_args) {
	$request_url = $api['url'] . "?method=" . urlencode($method) . "&api_key=" . urlencode($api['key']);
	foreach($remote_args as $arg_name => $arg_value) {
		$request_url .= "&" . urlencode($arg_name) . "=" . urlencode($arg_value);
	}

	//
	// Setup the curl request
	//
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $url);

	//
	// Make the request
	//
	$rsp = curl_exec($ch);
	curl_close($ch);

	return unserialize($rsp);
}	
?>
