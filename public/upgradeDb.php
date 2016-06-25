<?php
//
// Description
// -----------
// This function will check for and upgrade any tables which are out of date.
//
// *alert* When the database is split between database installs, this file will need to be modified.
//
// Info
// ----
// Status:      beta
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
//  <tables>
//      <table_name name='users' />
//  </tables>
//
function ciniki_core_upgradeDb($ciniki) {
    //
    // Check access restrictions to monitorChangeLogs
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.upgradeDb');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpgradeTables');
    return ciniki_core_dbUpgradeTables($ciniki);
}
?>
