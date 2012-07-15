<?php
//
// Description
// -----------
// This function will parse a user agent string into the components.
//
// Arguments
// ---------
// ciniki:
// user_agent:			The user agent string passed from the browser.
// 
// Returns
// -------
//
function ciniki_core_userAgentParse($ciniki, $user_agent) {
	//
	// Setup the default device
	//
	$device = array(
		'user_agent'=>$user_agent,
		'type_status'=>0x02, 'size'=>0x0f, 'flags'=>0, 
		'engine'=>'generic', 'engine_version'=>'',
		'os'=>'', 'os_version'=>'',
		'browser'=>'generic', 'browser_version'=>'',
		'device'=>'generic', 'device_version'=>'', 'device_manufacturer'=>'',
		);

	//
	// Code found on http://www.texsoft.it/index.php?m=sw.php.useragent
	// AND http://www.dotvoid.com/2007/01/parsing-the-user-agent-string-using-php/
	//
	$found = array();
	$pattern = "([^\/\s]*)" . "(\/([^\s]*))?";
	$pattern .= "(\s*\[[a-zA-Z][a-zA-Z]\])?";
	$pattern .= "(\s*\((((?>[^()]+)|\(?R\))*)\))?" . "\s*";

	$agent = $user_agent;
	while( strlen($agent) > 0 ) {
		if( ($l = preg_match('/' . $pattern . '/', $agent, $a)) ) {
			// print "<pre>" . print_r($a, true) . "</pre>";
			array_push($found, array("product" => $a[1], "version" => isset($a[3])?$a[3]:'', "comment" => isset($a[6])?$a[6]:'' ));
			$agent = substr($agent, strlen($a[0]));
		} else {
			$agent = "";		// abort parsing, no match
		}
	}
	
	// print "<pre>" . print_r($found, true) . "</pre>";

	if( isset($found[0]['comment']) ) {
		$device_info = $found[0]['comment'];
		if( preg_match('/MSIE ([^\;]*)(\;.*(Trident\/([0-9\.]*)))?/', $device_info, $matches) ) {
			$device['browser'] = 'IE';
			$device['browser_version'] = $matches[1];
			if( isset($matches[4]) ) {
				$device['engine'] = 'trident';
				$device['engine_version'] = $matches[4];
			}
			$device['size'] = 0x07; 							// Default to a large version, assuming desktop
		}
		if( preg_match('/Windows ([^;\)]+)/', $device_info, $matches) ) {
			$device['os'] = 'Windows';
			$device['os_version'] = $matches[1];
			$device['size'] = 0x07; 							// Default to a large version, assuming desktop
		}
		if( preg_match('/X11\;.*Linux ([^\;]+)(\;|\))/', $device_info, $matches) ) {
			$device['os'] = 'Linux';
			$device['os_version'] = $matches[1];
			$device['size'] = 0x07; 							// Default to a large version, assuming desktop
		}
		if( preg_match('/Macintosh\;.*(PPC|Intel).*X\s+(1[_\.0-9]+)/', $device_info, $matches) ) {
			$device['os'] = 'Macintosh';
			$device['os_version'] = $matches[2];
			$device['size'] = 0x07; 							// Default to a large version, assuming desktop
			$device['device'] = 'generic';
			$device['device_version'] = '';
			$device['device_manufacturer'] = 'Apple';
		}
		if( preg_match('/iPhone OS ([0-9_]*)/', $device_info, $matches) ) {
			$device['os'] = 'iOS';
			$device['os_version'] = $matches[1];
			$device['device'] = 'iphone';
			$device['device_version'] = '';
			$device['device_manufacturer'] = 'Apple';
			$device['size'] = 0x04; 							// Default to a large version, assuming desktop
		}
		if( preg_match('/iPad;.*OS ([0-9]*)/', $device_info, $matches) ) {
			$device['os'] = 'iOS';
			$device['os_version'] = $matches[1];
			$device['device'] = 'ipad';
			$device['device_version'] = '';
			$device['device_manufacturer'] = 'Apple';
			$device['size'] = 0x06; 							// Default to a large version, assuming desktop
		}
		if( preg_match('/Android\s+([^\;]+)/', $device_info, $matches) ) {
			$device['os'] = 'Android';
			$device['os_version'] = $matches[1];
			$device['size'] = 0x04;
		}
		if( preg_match('/Blackberry\s+([^\;]+)/i', $device_info, $matches) ) {
			$device['os'] = 'Blackberry';
			$device['device'] = 'blackberry';
			$device['device_version'] = $matches[1];
			$device['device_manufacturer'] = 'RIM';
			$device['size'] = 0x04;
		}
	}

	// Directly catch these
	foreach($found as $product) {
		switch($product['product']) {
			case 'Firefox':
			case 'Netscape':
			case 'Safari':
			case 'Camino':
			case 'Mosaic':
			case 'Galeon':
			case 'Opera':
			case 'Epiphany':
				$device['browser'] = $product['product'];
				$device['browser_version'] = $product['version'];
				break;
			case 'Chrome':
				$device['browser'] = $product['product'];
				$device['browser_version'] = $product['version'];
				break 2;
		}
	}

	foreach($found as $product) {
		switch($product['product']) {
			case 'Trident':
				$device['engine'] = 'trident';
				$device['engine_version'] = $product['version'];
				break;
			case 'Gecko':
				$device['engine'] = 'gecko';
				$device['engine_version'] = $product['version'];
				break;
			case 'AppleWebKit':
				$device['engine'] = 'webkit';
				$device['engine_version'] = $product['version'];
				break;
			case 'Presto':
				$device['engine'] = 'presto';
				$device['engine_version'] = $product['version'];
				break;
			case 'Mobile':
				break;
			case 'Version':
				if( $device['browser'] == 'Opera' ) {
					$device['browser_version'] = $product['version'];
				}
				break;
		}
	}

	// print "<pre>" . print_r($device, true) . "</pre>";

	return array('stat'=>'ok', 'device'=>$device);
}
?>
