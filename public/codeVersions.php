<?php
//
// Description
// -----------
// This method will return the package and modules version information.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <rsp stat="ok">
// <package name="ciniki" version="121119.1924" author="Andrew Rivett" hash="8584f9cff28711ab8652e0fa0dd2237ed5d94d86" />
// <modules>
//      <module name="api-core" version="121119.1847" author="Andrew Rivett" hash="ae437e62212699dad831037eb453e1e13ecf4f2c" />
// </modules>
// </rsp>
//
function ciniki_core_codeVersions($ciniki) {
    //
    // Check access restrictions to checkAPIKey
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.codeVersions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'getCodeVersions');
    $rc = ciniki_core_getCodeVersions($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return $rc;
}
?>
