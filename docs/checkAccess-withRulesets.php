<?php
//
// Description
// -----------
//
// This function comes from the old subscriptions module and is an example
// of a module using rulesets to restrict certain employee types to the module.
//
// This function will validate the user making the request has the 
// proper permissions to access or change the data.  This function
// must be called by all public API functions to ensure security.
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant the request is for.
// method:              The requested method.
// subscription_id:     The ID of the subscription the request is for.  Only checked if 
//                      subscription_id is specified and greater than zero.
// 
// Returns
// -------
//
function ciniki_subscriptions_checkAccess($ciniki, $tnid, $method, $subscription_id) {
    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'ciniki', 'subscriptions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.1', 'msg'=>'No permissions granted'));
    }
    $modules = $rc['modules'];

    //
    // Load the rulesets for this module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'getRulesets');
    $rulesets = ciniki_subscriptions_getRuleSets($ciniki);

    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok', 'modules'=>$rc['modules']);
    }

    //
    // Check to see if the ruleset is valid
    //
    if( !isset($rulesets[$rc['ruleset']]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.2', 'msg'=>'Access denied.'));
    }
    $ruleset = $rc['ruleset'];

    // 
    // Get the rules for the specified method
    //
    $rules = array();
    if( isset($rulesets[$ruleset]['methods']) && isset($rulesets[$ruleset]['methods'][$method]) ) {
        $rules = $rulesets[$ruleset]['methods'][$method];
    } elseif( isset($rulesets[$ruleset]['default']) ) {
        $rules = $rulesets[$ruleset]['default'];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.3', 'msg'=>'Access denied.'));
    }


    //
    // Check the subscription_id is attached to the tenant
    //
    if( $subscription_id > 0 ) {
        $strsql = "SELECT id, tnid FROM ciniki_subscriptions "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $subscription_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscription');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        //
        // If nothing was returned, deny
        // if tnid is not the same, deny (extra check)
        // if subscription id is not the same, deny (extra check)
        //
        if( !isset($rc['subscription']) 
            || $rc['subscription']['tnid'] != $tnid 
            || $rc['subscription']['id'] != $subscription_id ) {
            // Access denied!
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.4', 'msg'=>'Access denied'));
        }
    }

    //
    // Apply the rules.  Any matching rule will allow access.
    //

    //
    // If tenant_group specified, check the session user in the tenant_users table.
    //
    if( isset($rules['permission_groups']) && $rules['permission_groups'] > 0 ) {
        //
        // If the user is attached to the tenant AND in the one of the accepted permissions group, they will be granted access
        //
        $strsql = "SELECT tnid, user_id FROM ciniki_tenant_users "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND status = 10 "
            . "AND CONCAT_WS('.', package, permission_group) IN ('" . implode("','", $rules['permission_groups']) . "') "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.5', 'msg'=>'Access denied.', 'err'=>$rc['err']));
        }
        
        //
        // If the user has permission, return ok
        //
        if( isset($rc['rows']) && isset($rc['rows'][0]) 
            && $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
            return array('stat'=>'ok', 'modules'=>$rc['modules']);
        }
    }

    //
    // Default, return fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.6', 'msg'=>'Access denied'));
}
?>
