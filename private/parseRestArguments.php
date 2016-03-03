<?php
//
// Description
// -----------
// This function will parse the GET and POST arguments for a rest request.
// This may get used for other interfaces if necessary.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// ciniki: 			The standard ciniki data structure, the arguments will be parsed into it.
//
function ciniki_core_parseRestArguments(&$ciniki) {
	
	//
	// Check that request variable has been setup, otherwise this function has
	// been called in the wrong order
	//
	if( !is_array($ciniki) || !is_array($ciniki['request']) || !is_array($ciniki['request']['args']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'12', 'msg'=>'Internal Error', 'pmsg'=>'ciniki_core_parseRestArguments called before ciniki_core_init.'));
	}

	//
	// Parse the arguments for the non-required keys
	//
	$request_keys = array('api_key', 'auth_token', 'method');
	$response_keys = array('format');
	if( isset($_GET) && is_array($_GET) ) {
		foreach($_GET as $arg_key => $arg_value) {
			// Check for the required keys
			$arg_key = rawurldecode($arg_key);
			$arg_value = rawurldecode($arg_value);
			if( in_array($arg_key, $request_keys) ) {
				$ciniki['request'][$arg_key] = $arg_value;
			} elseif( in_array($arg_key, $response_keys) ) {
				$ciniki['response'][$arg_key] = $arg_value;
			} else {
				// _GET is already decoded, doesn't need to be again.
				$ciniki['request']['args'][$arg_key] = $arg_value;
			}
		}
	}

	if( isset($_POST) && is_array($_POST) ) {
		if( isset($_SERVER['CONTENT_TYPE']) && substr($_SERVER['CONTENT_TYPE'], 0, 19) == 'multipart/form-data' ) {
			//
			// Do nothing, this is image upload data
			//
			// file_put_contents('/tmp/up6.txt', file_get_contents("php://input"));
			foreach($_POST as $arg_key => $arg_value) {
				$arg_key = urldecode($arg_key);
				$arg_value = urldecode($arg_value);
				if( in_array($arg_key, $request_keys) ) {
					$ciniki['request'][$arg_key] = $arg_value;
				} elseif( in_array($arg_key, $response_keys) ) {
					$ciniki['response'][$arg_key] = $arg_value;
				} elseif( $arg_key != '' ) {
					$ciniki['request']['args'][$arg_key] = $arg_value;
				}
			}
		
		} else {
			$pairs = explode("&", file_get_contents("php://input"));
			// $vars = array();
			foreach ($pairs as $pair) {
				if( $pair == '' ) { continue; }
				$nv = explode("=", $pair);
				$arg_key = urldecode($nv[0]);
				$arg_value = urldecode($nv[1]);
				if( in_array($arg_key, $request_keys) ) {
					$ciniki['request'][$arg_key] = $arg_value;
				} elseif( in_array($arg_key, $response_keys) ) {
					$ciniki['response'][$arg_key] = $arg_value;
				} elseif( $arg_key != '' ) {
					$ciniki['request']['args'][$arg_key] = $arg_value;
				}
			}
		}
	}

	//
	// check command line arguments, get post information from stdin
	//
	if( isset($_SERVER['argc']) && $_SERVER['argc'] > 1 && $_SERVER['argv'][1] != '' ) {
		$args = preg_split('/\&/', $_SERVER['argv'][1]);
		foreach($args as $keyvalue) {
			if( $arg = preg_split('/=/', $keyvalue) ) {
				// Check for the required keys
				if( in_array($arg[0], $request_keys) ) {
					$ciniki['request'][$arg[0]] = $arg[1];
				} elseif( in_array($arg[0], $response_keys) ) {
					$ciniki['response'][$arg[0]] = $arg[1];
				} else {
					$ciniki['request']['args'][urldecode($arg[0])] = urldecode($arg[1]);
				}
				
			}
		}
	}

	//
	// Check for apache args
	//
	if( is_callable('apache_request_headers') ) {
		$headers = apache_request_headers();
		foreach($headers as $key => $val) {
			if( $key == 'If-Modified-Since' ) {
				$ciniki['request'][$key] = $val;
			}
			elseif( $key == 'Cache-Control' ) {
				$ciniki['request'][$key] = $val;
			}
		}
	}

	return array('stat'=>'ok');

}
?>
