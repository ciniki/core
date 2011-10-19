<?php
//
// Description
// -----------
// This processTemplateTags function will make the substitutions
// in the template for the listed tags.
//
// Info
// ----
// status:			alpha
//
// Required Methods
// ----------------
//
// Arguments
// ---------
// substitutions:	An associative array containing the keys and their values.
//					This typically will be the result row from an sql query.
//
// template:		The template text to search and replace within.
//
function ciniki_core_processTemplateTags($ciniki, $args) {

	if( !isset($args['template']) || $args['template'] == '') {
		return '';
	}

	if( !is_array($args['substitutions']) || $args['substitutions'] == '') {
		return $args['template'];
	}

	//
	// Join all the keys of the substitutions, to form a regex
	// which will search for the keys in the template
	//

	$search_str = '/\{\$(' . join('|', array_keys($args['substitutions'])) . ')\}/i';

	//
	// Search the template and when a key is found, then lookup value in args
	//
	//print "Searching: $search_str\n";
	$do_sub = create_function('$matches', '
			$fargs = func_get_args();
			return $fargs[0][1];
//			print "Matches:"; print_r($matches);
//			return "n" . $args["substitutions"][$matches[1]];
//			if( isset($args["substitutions"][$matches[0]]) ) {
//				return $args["substitutions"][$matches[0]];
//			} else {
//				return "???";
//			}
			');

	return preg_replace_callback($search_str, $do_sub, $args['template']);

//		create_function('$matches', '
//			return "n" . $args["substitutions"][$matches[1]];
//			if( isset($args["substitutions"][$matches[0]]) ) {
//				return $args["substitutions"][$matches[0]];
//			} else {
//				return "???";
//			}
//			'), 
//		$args['template']);
}
?>
