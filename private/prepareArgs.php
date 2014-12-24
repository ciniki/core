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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'parseArgs');
	return ciniki_core_parseArgs($ciniki, 
		(isset($ciniki['request']['args']['business_id'])?$ciniki['request']['args']['business_id']:0), 
		(isset($ciniki['request']['args'])?$ciniki['request']['args']:array()), 
		$arg_info);
}
?>
