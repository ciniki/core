<?php
//
// Description
// -----------
// This function will start a new session, destroying the old
// one if it exists.
//
// Arguments
// ---------
// ciniki:
//
function ciniki_core_sessionEnd($ciniki) {

    //
    // Remove the session from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');

    if( isset($ciniki['session']['auth_token']) && $ciniki['session']['auth_token'] != '' ) {
        $strsql = "DELETE FROM ciniki_core_session_data "
            . "WHERE auth_token = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['auth_token']) . "' ";
        $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.core');
        if( $rc['stat'] == 'ok' && $rc['num_affected_rows'] == 1 ) {
            // FIXME: Add code to track number of active sessions in users table, limit to X sessions.
        }
    }

    elseif( isset($ciniki['request']['auth_token']) && $ciniki['request']['auth_token'] != '' ) {
        $strsql = "DELETE FROM ciniki_core_session_data "
            . "WHERE auth_token = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['auth_token']) . "' ";
        $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.core');
        if( $rc['stat'] == 'ok' && $rc['num_affected_rows'] == 1 ) {
            // FIXME: Add code to track number of active sessions in users table, limit to X sessions.
        }
    }

    //
    // Take the opportunity to clear old sessions, don't care about return code
    // FIXME: This maybe should be moved to a cronjob
    //
    $strsql = "DELETE FROM ciniki_core_session_data WHERE UTC_TIMESTAMP()-TIMESTAMP(last_saved) > timeout";
    ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.core');

    return array('stat'=>'ok');
}
?>
