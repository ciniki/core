<?php
//
// Description
// -----------
// This function will return a permalink from a string passed.  The permalinks
// are used in modules to store url components.  They are typically a reduced
// form of a name or title, that is url compliant.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_core_makePermalink($ciniki, $str) {
	$newstr = preg_replace('/[ \/]/', '-', preg_replace('/[^a-z0-9 \-\/]/', '', strtolower($str)));
	// Remove multiple replaced characters to a single dash, looks better in url
	$newstr = preg_replace('/\-\-+/', '-', $newstr);
	return $newstr;
}
?>
