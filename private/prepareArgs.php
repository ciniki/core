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
// moss:			The moss variable.
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
function moss_core_prepareArgs($moss, $quote_flag, $arg_info) {
	$args = array();
	
	foreach($arg_info as $arg => $options) {
		$msg = 'Missing argument';
		if( $options['errmsg'] ) {
			$msg = $options['errmsg'];
		}

		//
		// Check if the argument exists
		//
		if( isset($moss['request']['args']) && isset($moss['request']['args'][$arg]) ) {
			//
			// Check for a blank argument, and if it's allowed
			//
			if( $moss['request']['args'][$arg] == '' && isset($options['blank']) && $options['blank'] != 'yes' ) {
				return array('stat'=>'fail', 'err'=>array('code'=>'239', 'msg'=>"$msg", 'pmsg'=>"Argument: $arg missing"));
			}

			if( isset($options['type']) && $options['type'] == 'idlist' ) {
				$list = explode(',', $moss['request']['args'][$arg]);
				// Typecast all entries as (int) so they are dealt with as ID's
				$args[$arg] = array();
				foreach($list as $i) {
					$args[$arg][] = (int)$i;
				}
			} elseif( isset($options['type']) && $options['type'] == 'list' ) {
				$list = explode(',', $moss['request']['args'][$arg]);
				$args[$arg] = array();
				foreach($list as $i) {
					$args[$arg][] = $i;
				}
			} elseif( isset($options['type']) && $options['type'] == 'date' && $moss['request']['args'][$arg] != '' ) {
				date_default_timezone_set('America/Toronto');
				if( $moss['request']['args'][$arg] == 'now' || $moss['request']['args'][$arg] == 'today' ) {
					$args[$arg] = strftime("%Y-%m-%d");
				} else {
					$ts = strtotime($moss['request']['args'][$arg]);
					if( $ts === FALSE || $ts < 1 ) {
						return array('stat'=>'fail', 'err'=>array('code'=>'234', 'msg'=>"$msg", 'pmsg'=>"Argument: $arg invalid date format"));
					} else {
						$args[$arg] = strftime("%Y-%m-%d", $ts);
					}
				}
			} elseif( isset($options['type']) && $options['type'] == 'datetime' && $moss['request']['args'][$arg] != '' ) {
				date_default_timezone_set('America/Toronto');
				if( $moss['request']['args'][$arg] == 'now' ) {
					$args[$arg] = strftime("%Y-%m-%d %H:%M");
				} else {
					$ts = strtotime($moss['request']['args'][$arg]);
					if( $ts === FALSE || $ts < 1 ) {
						return array('stat'=>'fail', 'err'=>array('code'=>'235', 'msg'=>"$msg", 'pmsg'=>"Argument: $arg invalid datetime format"));
					} else {
						$args[$arg] = strftime("%Y-%m-%d %H:%M", $ts);
					}
				}
			} else {
				$args[$arg] = $moss['request']['args'][$arg];
			}

			if( isset($options['trimblanks']) && $options['trimblanks'] == 'yes' ) {
				$args[$arg] = trim($args[$arg]);
			}
			
			// Check for the text string null, and convert to specified string
			if( isset($options['null']) && $args[$arg] == 'null' ) {
				$args[$arg] = $options['null'];
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
				return array('stat'=>'fail', 'err'=>array('code'=>'226', 'msg'=>"$msg", 'pmsg'=>"Argument: $arg missing."));
			} 
		
			//
			// The argument is not required, check for a default value to assign
			//
			if( isset($options['default']) ) {
				$args[$arg] = $options['default'];
			}

			// If no default value, then the arg is not added to the list
		}
	}

	return array('stat'=>'ok', 'args'=>$args);
}
?>
