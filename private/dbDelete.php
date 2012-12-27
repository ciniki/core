<?php
//
// Description
// -----------
// This function will run an delete query against the database. 
// This function is a placeholder and just a passthrough to dbUpdate
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// ciniki:
// strsql:				The SQL string that will delete row(s) from a table.
// module:				The name of the module for the transaction, which should include the 
//						package in dot notation.  Example: ciniki.artcatalog
//
function ciniki_core_dbDelete($ciniki, $strsql, $module) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	return ciniki_core_dbUpdate($ciniki, $strsql, $module);
}
?>
