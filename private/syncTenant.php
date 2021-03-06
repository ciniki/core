<?php
//
// Description
// -----------
// This function will sync the data for a tenant from the remote
// server to the local server.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
// sync_id:         The ID of the sync to check compatibility with.
// type:            The type of sync.
//
//                  incremental - compare last updated of records from last sync
//                  partial - compare last updated of all records
//                  full - compare every record all fields
//
// module:          If the sync should only do one module.
//
function ciniki_core_syncTenant($ciniki, $sync, $tnid, $type, $module) {

    //
    // Check the versions of tables and modules enabled are the same between servers
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncCheckVersions');
    $rc = ciniki_core_syncCheckVersions($ciniki, $sync, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.175', 'msg'=>'Incompatible versions', 'err'=>$rc['err']));
    }
    $modules = $rc['modules'];
    $remote_modules = $rc['remote_modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncTenantModule');

//  $last_sync_time = date('U');
    $strsql = "SELECT UNIX_TIMESTAMP() AS last_sync_time ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'sync');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $last_sync_time = $rc['sync']['last_sync_time'];

    //
    // Setup logging
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLog');
    if( isset($ciniki['config']['ciniki.core']['sync.log_dir']) ) {
        $ciniki['synclogfile'] = $ciniki['config']['ciniki.core']['sync.log_dir'] . "/sync_" . $sync['sitename'] . '_' . $sync['id'] . ".log";
    }
    $ciniki['synclogprefix'] = '[' . $sync['sitename'] . '-' . $sync['remote_name'] . ']';

    //
    // If a specific module is specified, only sync that module and return.
    // Don't update sync times, as not all modules syncd
    //
    if( $module != '' ) {
        $rc = ciniki_core_syncTenantModule($ciniki, $sync, $tnid, $module, $type, '');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.176', 'msg'=>'Unable to sync module ' . $module, 'err'=>$rc['err']));
        }
        //
        // Return 
        //
        return array('stat'=>'ok');
    }

    //
    // Sync the core modules first
    //
    $core_modules = array('ciniki.users', 'ciniki.tenants', 'ciniki.images');
    foreach($core_modules as $module) {
        if( $type == 'full' || $type == 'partial' 
            || ($type == 'incremental'
                && (isset($remote_modules[$module]['last_change'])
                    && ($remote_modules[$module]['last_change'] >= $sync['last_sync'] 
                        || $modules[$module]['last_change'] >= $sync['last_sync'])
                    )
            )) {
            $rc = ciniki_core_syncTenantModule($ciniki, $sync, $tnid, $module, $type, '');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.177', 'msg'=>'Unable to sync module ' . $module, 'err'=>$rc['err']));
            }
            //
            // Update the module last_change timestamp if more recent on remote
            //
            $mname = preg_split('/\./', $module);
            $strsql = "UPDATE ciniki_tenant_modules "
                . "SET last_change = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$module]['last_change']) . "') "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND package = '" . ciniki_core_dbQuote($ciniki, $mname[0]) . "' "
                . "AND module = '" . ciniki_core_dbQuote($ciniki, $mname[1]) . "' "
                . "AND last_change < FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$module]['last_change']) . "') "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, $module);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Go through the priority optional modules, which needs to be sync'd in order
    //
    $priority_modules = array('ciniki.customers');
    foreach($priority_modules as $module) {
        // Check if module is enabled for the tenant
        // and only run an incmental if the last_change dates for the modules don't match
        if( isset($modules[$module]) 
            && ($type == 'full' || $type == 'partial' || 
                ($type == 'incremental' 
//                  && $modules[$module]['last_change'] != $remote_modules[$module]['last_change']
                    && ($remote_modules[$module]['last_change'] >= $sync['last_sync'] 
                        || $modules[$module]['last_change'] >= $sync['last_sync'])
                    )) 
            ) {
            $rc = ciniki_core_syncTenantModule($ciniki, $sync, $tnid, $module, $type, '');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.178', 'msg'=>'Unable to sync module ' . $module, 'err'=>$rc['err']));
            }
            //
            // Update the module last_change timestamp if more recent on remote
            //
            $mname = preg_split('/\./', $module);
            $strsql = "UPDATE ciniki_tenant_modules "
                . "SET last_change = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$module]['last_change']) . "') "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND package = '" . ciniki_core_dbQuote($ciniki, $mname[0]) . "' "
                . "AND module = '" . ciniki_core_dbQuote($ciniki, $mname[1]) . "' "
                . "AND last_change < FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$module]['last_change']) . "') "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, $module);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Go through the optional modules configured for the tenant
    //
    foreach($modules as $name => $module) {
        //
        // Check that it wasn't taken care of in priority modules
        //
        if( !in_array($name, $priority_modules) 
            && !in_array($name, $core_modules) 
            && ($type == 'full' || $type == 'partial' || 
                ($type == 'incremental' 
                && ($remote_modules[$name]['last_change'] >= $sync['last_sync'] 
                    || $modules[$name]['last_change'] >= $sync['last_sync'])
                )) 
            ) {
            $rc = ciniki_core_syncTenantModule($ciniki, $sync, $tnid, $name, $type, '');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.179', 'msg'=>'Unable to sync module ' . $name, 'err'=>$rc['err']));
            }
            //
            // Update the module last_change timestamp if more recent on remote
            //
            $mname = preg_split('/\./', $name);
            $strsql = "UPDATE ciniki_tenant_modules "
                . "SET last_change = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$name]['last_change']) . "') "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND package = '" . ciniki_core_dbQuote($ciniki, $mname[0]) . "' "
                . "AND module = '" . ciniki_core_dbQuote($ciniki, $mname[1]) . "' "
                . "AND last_change < FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_modules[$name]['last_change']) . "') "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, $name);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Updated the last sync time
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateLastTime');
    $rc = ciniki_core_syncUpdateLastTime($ciniki, $tnid, $sync['id'], $type, $last_sync_time);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the remote time
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
    $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.core.syncUpdateLastTime',
        'type'=>$type, 'time'=>$last_sync_time));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
