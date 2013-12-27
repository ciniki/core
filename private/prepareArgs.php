<?php
//
// Description
// -----------
// This function will find the required arguments for
// a method, and return an array with the required arguments and their values.
// 
// If the argument is not sent, it will not be added to the return array, unless
// a default has been specified.
//
// Options:
// _ required (yes|no) - Is the field required to be present in the request args?
// _ blank (yes|no) - Can the field be blank and still accepted?
// _ default - If a non-required field is not specified in request args, what should the default value be, if any?
// _ errmsg - The error msg to return if required field is missing, or blank
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// ciniki:			The ciniki variable.
// quote_flag:		Should the 
// arg_info:		The array of arguments to be parsed.  The array should be in 
//					the form of the following.
//
//					array(
//						'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'),
//						'source'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>''),
//						...
//					);
// 
// Returns
// -------
//
function ciniki_core_prepareArgs(&$ciniki, $quote_flag, $arg_info) {
	$args = array();

	//
	// Check if business_id is specified, and if so, load the business settings
	//
	if( isset($ciniki['request']['args']['business_id']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
		$rc = ciniki_businesses_intlSettings($ciniki, $ciniki['request']['args']['business_id']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$intl_timezone = $rc['settings']['intl-default-timezone'];
		date_default_timezone_set($intl_timezone);
		$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
		$intl_currency = $rc['settings']['intl-default-currency'];
	}

	foreach($arg_info as $arg => $options) {
		$msg = 'Missing argument';
		$invalid_msg = $msg;
		if( isset($options['name']) ) {
			$msg = "You must specify a " . $options['name'] . "";
			$invalid_msg = "Invalid " . $options['name'] . " format";
		} elseif( $options['errmsg'] ) {
			$msg = $options['errmsg'];
			$invalid_msg = $msg;
		}

		//
		// Check if the argument exists
		//
		if( isset($ciniki['request']['args']) && isset($ciniki['request']['args'][$arg]) ) {
			//
			// Check for a blank argument, and if it's allowed
			//
			if( $ciniki['request']['args'][$arg] == '' && isset($options['blank']) && $options['blank'] != 'yes' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'239', 'msg'=>"$msg", 'pmsg'=>"Argument: $arg missing"));
			}

			if( isset($options['type']) && $options['type'] == 'idlist' ) {
				$args[$arg] = array();
				if( $ciniki['request']['args'][$arg] != '' ) {
					$list = explode(',', $ciniki['request']['args'][$arg]);
					// Typecast all entries as (int) so they are dealt with as ID's
					foreach($list as $i) {
						$args[$arg][] = (int)$i;
					}
				}
			} elseif( isset($options['type']) && $options['type'] == 'list' ) {
				if( isset($options['delimiter']) && $options['delimiter'] != '' ) {
					$list = explode($options['delimiter'], $ciniki['request']['args'][$arg]);
				} else {
					$list = explode(',', $ciniki['request']['args'][$arg]);
				}
				$args[$arg] = array();
				foreach($list as $i) {
					$args[$arg][] = $i;
				}
			} elseif( isset($options['type']) && $options['type'] == 'objectlist' ) {
				$list = explode(',', $ciniki['request']['args'][$arg]);
				$args[$arg] = array();
				foreach($list as $i) {
					list($object, $object_id) = explode(':', $i);
					$args[$arg][] = array('object'=>$object, 'id'=>$object_id);
				}
			} elseif( isset($options['type']) && $options['type'] == 'date' && $ciniki['request']['args'][$arg] != '' ) {
				if( $ciniki['request']['args'][$arg] == 'now' || $ciniki['request']['args'][$arg] == 'today' ) {
					$args[$arg] = strftime("%Y-%m-%d");
				} elseif( $ciniki['request']['args'][$arg] == 'tomorrow' ) {
					$args[$arg] = strftime("%Y-%m-%d", time()+86400);
				} else {
					$ts = strtotime($ciniki['request']['args'][$arg]);
					if( $ts === FALSE || $ts < 1 ) {	
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'234', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid date format"));
						
					} else {
						$args[$arg] = strftime("%Y-%m-%d", $ts);
					}
				}
			} elseif( isset($options['type']) && $options['type'] == 'time' && $ciniki['request']['args'][$arg] != '' ) {
				if( $ciniki['request']['args'][$arg] == 'now' || $ciniki['request']['args'][$arg] == 'today' ) {
					$args[$arg] = strftime("%H:%M:%S");
				} else {
					$ts = strtotime($ciniki['request']['args'][$arg]);
					if( $ts === FALSE || $ts < 1 ) {	
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'120', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid date format"));
						
					} else {
						$args[$arg] = strftime("%H:%M:%S", $ts);
					}
				}
			} 
			elseif( isset($options['type']) && $options['type'] == 'datetime' && $ciniki['request']['args'][$arg] != '' ) {
				if( $ciniki['request']['args'][$arg] == 'now' ) {
					$args[$arg] = strftime("%Y-%m-%d %H:%M");
				} else {
					$ts = strtotime($ciniki['request']['args'][$arg]);
					if( $ts === FALSE || $ts < 1 ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'235', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid datetime format"));
					} else {
						$args[$arg] = strftime("%Y-%m-%d %H:%M", $ts);
					}
				}
			} 
			elseif( isset($options['type']) && $options['type'] == 'datetimetoutc' && $ciniki['request']['args'][$arg] != '' ) {
//				ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'timezoneOffset');
//				// UTC timezone offset in seconds
//				$utc_offset = ciniki_businesses_timezoneOffset($ciniki, 'seconds');
//				date_default_timezone_set('America/Toronto');
				if( $ciniki['request']['args'][$arg] == 'now' ) {
					$date = new DateTime('now', new DateTimeZone('UTC'));
					$args[$arg] = $date->format('Y-m-d H:i:s');
//					$args[$arg] = strftime("%Y-%m-%d %H:%M:%S");
				} else {
					$ts = strtotime($ciniki['request']['args'][$arg]);
					if( $ts === FALSE || $ts < 1 ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'331', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid datetime format"));
					} else {
						$date = new DateTime("@".$ts, new DateTimeZone($intl_timezone));
						$args[$arg] = $date->format('Y-m-d H:i:s');
//						$args[$arg] = strftime("%Y-%m-%d %H:%M:%S", $ts - $utc_offset);
					}
				}
			} 
			elseif( isset($options['type']) && $options['type'] == 'currency' && $ciniki['request']['args'][$arg] != '' ) {
				if( ($intl_currency == 'CAD' || $intl_currency == 'USD') && $ciniki['request']['args'][$arg][0] != '$' ) {
					$args[$arg] = '$' . $ciniki['request']['args'][$arg];
				} else {
					$args[$arg] = $ciniki['request']['args'][$arg];
				}
				$amt = numfmt_parse_currency($intl_currency_fmt, $args[$arg], $intl_currency);
				if( $amt === FALSE ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1425', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid currency format"));
				}
				$args[$arg] = $amt;
			}
			elseif( isset($options['type']) && $options['type'] == 'int' && preg_match('/^\d+$/',$ciniki['request']['args'][$arg]) ) {
				$args[$arg] = (int)$ciniki['request']['args'][$arg];
			} 
			elseif( isset($options['type']) && $options['type'] == 'float' && preg_match('/^\d+(\.\d+)?$/',$ciniki['request']['args'][$arg]) ) {
				$args[$arg] = (float)$ciniki['request']['args'][$arg];
			} 
			else {
				$args[$arg] = $ciniki['request']['args'][$arg];
			}

			if( isset($options['trimblanks']) && $options['trimblanks'] == 'yes' ) {
				$args[$arg] = trim($args[$arg]);
			}
			
			// Check for the text string null, and convert to specified string
			if( isset($options['null']) && $args[$arg] == 'null' ) {
				$args[$arg] = $options['null'];
			}

			// Check if there is a list of valid options to accept
			if( isset($options['validlist']) && !in_array($args[$arg], $options['validlist']) ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'225', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg not an acceptable input"));
			}
		} 
		
		//
		// If the argument does not exist, check what should be done
		//
		else {
			//
			// Return an error if this argument is required
			//
			if( $options['required'] == 'yes' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'226', 'msg'=>"$msg", 'pmsg'=>"Argument: $arg missing."));
			} 
		
			//
			// The argument is not required, check for a default value to assign
			//
			if( isset($options['default']) ) {
				if( isset($options['type']) && $options['type'] == 'date' 
					&& ($options['default'] == 'now' || $options['default'] == 'today') ) {
					$date = new DateTime('now', new DateTimeZone($intl_timezone));
					$args[$arg] = $date->format('Y-m-d');
				} elseif( isset($options['type']) && $options['type'] == 'datetime' 
					&& ($options['default'] == 'now' || $options['default'] == 'today') ) {
					$date = new DateTime('now', new DateTimeZone($intl_timezone));
					$args[$arg] = $date->format('Y-m-d H:i');
				} elseif( isset($options['type']) && $options['type'] == 'datetimetoutc' 
					&& ($options['default'] == 'now' || $options['default'] == 'today') ) {
					$date = new DateTime('now', new DateTimeZone('UTC'));
					$args[$arg] = $date->format('Y-m-d H:i:s');
				} else {
					$args[$arg] = $options['default'];
				}
			}

			// If no default value, then the arg is not added to the list
		}
	}

	return array('stat'=>'ok', 'args'=>$args);
}
?>
