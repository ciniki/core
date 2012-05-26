<?php
//
// Description
// -----------
// This function will post a API request to paypal.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_core_paypalPost($ciniki, $method, $args) {
	
	//
	// Make sure paypal variables have been set in config
	//
	if( !isset($ciniki['config']['core']['paypal.url']) || $ciniki['config']['core']['paypal.url'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'684', 'msg'=>'Paypal not configured'));
	}
	if( !isset($ciniki['config']['core']['paypal.username']) || $ciniki['config']['core']['paypal.username'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'685', 'msg'=>'Paypal not configured'));
	}
	if( !isset($ciniki['config']['core']['paypal.password']) || $ciniki['config']['core']['paypal.password'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'686', 'msg'=>'Paypal not configured'));
	}
	if( !isset($ciniki['config']['core']['paypal.signature']) || $ciniki['config']['core']['paypal.signature'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'687', 'msg'=>'Paypal not configured'));
	}

	$reqargs = 'METHOD=' . urldecode($method) . '&version=' . urlencode('89.0') 
		. '&PWD=' . urlencode($ciniki['config']['core']['paypal.password']) 
		. '&USER=' . urlencode($ciniki['config']['core']['paypal.username']) 
		. '&SIGNATURE=' . urlencode($ciniki['config']['core']['paypal.signature']) 
		. '&' . $args;

	error_log("PAYPAL-REQ: $reqargs");

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $ciniki['config']['core']['paypal.url']);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	// Turn off the server and peer verification (TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);

	curl_setopt($ch, CURLOPT_POSTFIELDS, $reqargs);

	$httpResponse = curl_exec($ch);

	if( !$httpResponse) {
		error_log("PAYPAL-POST: $method failed: " . curl_error($ch) . '(' . curl_errno($ch) . ')');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'688', 'msg'=>'Paypal error, please contact support.'));
	}
	error_log("PAYPAL-RSP: $httpResponse");

	$httpResponseAr = explode("&", $httpResponse);

	$rc = array();
	foreach( $httpResponseAr as $i => $value) {
		$tmpAr = explode("=", $value);
		if( sizeof($tmpAr) > 1 ) {
			$rc[$tmpAr[0]] = $tmpAr[1];
		}
	}
	if( strtoupper($rc['ACK']) == 'SUCCESS' || strtoupper($rc['ACK']) == 'SUCCESSWITHWARNING' ) {
		return array('stat'=>'ok', 'response'=>$rc);
	} 

	return array('stat'=>'fail', 'response'=>$rc, 'err'=>array('pkg'=>'ciniki', 'code'=>'689', 'msg'=>'Paypal error, please contact support.'));
}
