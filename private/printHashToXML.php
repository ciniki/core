<?php
//
// Description
// -----------
// This function will convert a hash, or array of arrays into
// an xml structure.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// name: 			The name the top level should be
// indent:			The string for indentation, which should be spaces.  Each recursive call added 4 spaces.
// hash:			The array of array's to turn into xml.
//
//
function ciniki_core_printHashToXML($name, $indent, $hash) {

	$subitems = false;
	$subxml = '';

	print "$indent<$name";
	foreach($hash as $hash_key => $hash_item) {
		if( $hash_key == 'xml' ) {
			$subxml .= $hash_item;
		} elseif( is_numeric($hash_item) ) {
			print " $hash_key=\"$hash_item\"";
		} elseif( is_string($hash_item) && strlen($hash_item) > 5 && $hash_item[0] == '<' ) {
			$subxml .= $hash_item;
		} elseif( is_string($hash_item) && (strpos($hash_item, "\n") > 0 || strlen($hash_item) >= 100) ) {
			$subitems = true;
		} elseif( is_string($hash_item) ) {
			print " $hash_key=\"$hash_item\"";
		} elseif( is_array($hash_item) && array_key_exists('0', $hash_item) ) {
			$subitems = true;
		} elseif( is_array($hash_item) ) {
			$subitems = true;
		}
	}
	
	if( $subitems == true || $subxml != '' ) {
		print ">\n";
		if( $subitems == true ) {
			foreach($hash as $hash_key => $hash_item) {
				if( is_array($hash_item) && array_key_exists('0', $hash_item) ) {
					print $indent . "    <$hash_key";
					// First get any items which should be part of the hash_key
					foreach($hash_item as $subkey => $subitem) {
						if( (is_string($subitem) && strpos($subitem, "\n") === FALSE && strlen($subitem) < 100) || is_numeric($subitem) ) {
							print " $subkey='$subitem'";
						}
					}
					print ">\n";
					//  Then look for any subitems
					foreach($hash_item as $subkey => $subitem) {
						if( is_string($subitem) && (strlen($subitem) >= 100 || strpos($subitem, "\n") > 0) ) {
							print $indent . "		<$subkey>$subitem</$subkey>\n";
						}
						if( is_array($subitem) ) {
							foreach($subitem as $sskey => $ssitem) {
								ciniki_core_printHashToXML($sskey, $indent . "        ", $ssitem);
							}
						}
					}
					print $indent . "    </$hash_key>\n";
				} elseif( is_array($hash_item) ) {
					ciniki_core_printHashToXML($hash_key, $indent . "    ", $hash_item);
					$subitems = true;
				} elseif( is_string($hash_item) && (strlen($hash_item) >= 100 || strpos($hash_item, "\n") > 0) ) {
					print $indent . "    <$hash_key>$hash_item</$hash_key>\n";
				}
			}
		}
		if( $subxml != '' ) {
			print $subxml;
		}
		print "$indent</$name>\n";
	} else {
		print " />\n";
	}

}
?>
