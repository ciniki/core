<?php
//
// Description
// -----------
// This function will initialize the session variable, but will not
// open an existing or start a new session.
//
// Arguments
// ---------
// ciniki:
//
function ciniki_core_sessionInit(&$ciniki) {

    $ciniki['session'] = array();

    //
    // Set default session variables
    //
    $ciniki['session']['api_key'] = '';
    $ciniki['session']['auth_token'] = '';
    $ciniki['session']['change_log_id'] = '';

    //
    // Create a structure to store the user information
    //
    $ciniki['session']['user'] = array('id'=>0, 'perms'=>0);

    return array('stat'=>'ok');
}
?>
