<?php
//
// Description
// -----------
// This function will sync a modules data between two ciniki installs.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
// sync:            The sync object containing the sync keys and URL's.
// module:          The module to sync.
// type:            The type of sync (full, partial, incremental)
//
function ciniki_core_syncTenantModule(&$ciniki, &$sync, $tnid, $module, $type, $specified_object) {

//  error_log("SYNC-INFO: [$tnid] Syncing $module");
//  ciniki_core_syncLog($ciniki, 2, "Syncing $module");

    //
    // FIXME: The full sync needs to be fixed, switched to partial temporarily
    //
    if( $type == 'full' ) { 
        $type = 'partial';
    }

    //
    // Load the module objects
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncModuleObjects');
    $rc = ciniki_core_syncModuleObjects($ciniki, $tnid, $module, $type);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['objects']) ) {
        return array('stat'=>'ok');
    }

    $objects = $rc['objects'];
    $settings = $rc['settings'];
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectGet');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectHistoryGet');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectHistoryList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectHistoryUpdate');

    //
    // For each object, get the list and compare
    //
    $a = preg_split('/\./', $module);
    $pkg = $a[0];
    $mod = $a[1];
    $history_tables = array();
    foreach($objects as $name => $obj) {
        if( $specified_object != '' && $name != $specified_object ) {
            continue;
        }


        //
        // Setup the object which needs to be passed to the sync functions
        //
        $o = $obj;
        $o['package'] = $pkg;
        $o['module'] = $mod;
        $o['pmod'] = $module;
        $o['oname'] = $name;

//      error_log("SYNC-INFO: [$tnid] Syncing $module.$name");
        ciniki_core_syncLog($ciniki, 2, "Syncing $module.$name", null);
        //
        // Get the remote list of objects
        //
        $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"$pkg.$mod.$name.list", 'type'=>$type, 'since_uts'=>$sync['last_sync']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.186', 'msg'=>"Unable to get the remote list: $pkg.$mod.$name", 'err'=>$rc['err']));
        }

        if( !isset($rc['list']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.187', 'msg'=>'Unable to get remote list'));
        }
        $remote_list = $rc['list'];
        if( isset($rc['deleted']) ) {
            $remote_deleted = $rc['deleted'];
        } else {
            $remote_deleted = array();
        }

        //
        // Get the local list of objects
        //
        $rc = ciniki_core_syncObjectList($ciniki, $sync, $tnid, $o, array('type'=>$type, 'since_uts'=>$sync['last_sync']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.188', 'msg'=>'Unable to get the local list', 'err'=>$rc['err']));
        }
        if( !isset($rc['list']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.189', 'msg'=>'Unable to get local list'));
        }
        $local_list = $rc['list'];
        if( isset($rc['deleted']) ) {
            $local_deleted = $rc['deleted'];
        } else {
            $local_deleted = array();
        }

        //
        // Process any deleted items first
        //
        if( ($sync['flags']&0x01) == 0x01 && isset($remote_deleted) && count($remote_deleted) > 0 ) {
            foreach($remote_deleted as $uuid => $deleted_history) {
                if( isset($local_list[$uuid]) ) {
                    //
                    // Delete from the local server
                    //
                    $rc = ciniki_core_syncObjectDelete($ciniki, $sync, $tnid, $o, array('uuid'=>$uuid, 'history'=>$deleted_history));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.190', 'msg'=>"Unable to delete $name on local server", 'err'=>$rc['err']));;
                    }
                    unset($local_list[$uuid]);
                }
            }
        }
        if( ($sync['flags']&0x01) == 0x01 && isset($local_deleted) && count($local_deleted) > 0 ) {
            foreach($local_deleted as $uuid => $deleted_history) {
                if( isset($remote_list[$uuid]) ) {
                    //
                    // Push the delete to the remote server
                    //
                    $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"$pkg.$mod.$name.delete", 'uuid'=>$uuid, 'history'=>$deleted_history));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.191', 'msg'=>"Unable to delete $name($uuid) on remote server", 'err'=>$rc['err']));
                    }
                    unset($remote_list[$uuid]);
                }
            }
        }

        //
        // Process the updates/additions for the pull side
        //
        if( ($sync['flags']&0x02) == 0x02 ) {
            foreach($remote_list as $uuid => $last_updated) {
                //
                // A full sync will compare every customer, 
                // a partial or incremental will only check records where the last_updated differs
                // Check if uuid does not exist, and has not been deleted
                //
                if( ($type == 'full' || !isset($local_list[$uuid]) || $local_list[$uuid] != $last_updated)
                    && !isset($local_deleted[$uuid]) ) {
                    //
                    // Add to the local database
                    //
                    $rc = ciniki_core_syncObjectUpdate($ciniki, $sync, $tnid, $o, array('uuid'=>$uuid));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.192', 'msg'=>"Unable to update $name($uuid) on local server", 'err'=>$rc['err']));;
                    }
                } 
            }
        }

        //
        // For the push side
        //
        if( ($sync['flags']&0x01) == 0x01 ) {
            foreach($local_list as $uuid => $last_updated) {
                //
                // Check if uuid does not exist, and has not been deleted
                //
                if( ($type == 'full' || !isset($remote_list[$uuid]) || $remote_list[$uuid] != $last_updated) 
                    && !isset($remote_deleted[$uuid]) ) {
                    $rc = ciniki_core_syncObjectGet($ciniki, $sync, $tnid, $o, array('uuid'=>$uuid));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.193', 'msg'=>"Unable to get $name($uuid) on local server", 'err'=>$rc['err']));
                    }
                    //
                    // Update the remote object
                    //
                    $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"$pkg.$mod.$name.update", 'uuid'=>$uuid, "object"=>$rc['object']));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.194', 'msg'=>"Unable to update $name($uuid) on remote server", 'err'=>$rc['err']));
                    }
                }
            }
        }

        //
        // Process the history for this object.
        //
//      error_log("SYNC-INFO: [$tnid] Syncing $module.$name history");
        ciniki_core_syncLog($ciniki, 2, "Syncing $module.$name history", null);

        //
        // Get the local history
        //
        $rc = ciniki_core_syncObjectHistoryList($ciniki, $sync, $tnid, $o, 
            array('type'=>$type, 'since_uts'=>$sync['last_sync']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.195', 'msg'=>"Unable to get history list for $history_table", 'err'=>$rc['err']));
        }
        if( !isset($rc['list']) ) {
            $local_list = array();
        } else {
            $local_list = $rc['list'];
        }
        
        //
        // Get the remote list of history
        //
        $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"$pkg.$mod.$name.history.list", 
            'type'=>$type, 'since_uts'=>$sync['last_sync']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.196', 'msg'=>"Unable to get the remote list: $pkg.$mod.$name", 'err'=>$rc['err']));
        }
        if( !isset($rc['list']) ) {
            $remote_list = array();
        } else {
            $remote_list = $rc['list'];
        }

        //
        // Update the history for the pull side
        //
        if( ($sync['flags']&0x02) == 0x02 ) {
            foreach($remote_list as $uuid => $last_updated) {
                //
                // A full sync will compare every customer, 
                // a partial or incremental will only check records where the last_updated differs
                // Check if uuid does not exist, and has not been deleted
                //
                if( ($type == 'full' || !isset($local_list[$uuid]) || $local_list[$uuid] != $last_updated) ) {
                    //
                    // Add to the local database
                    //
                    $rc = ciniki_core_syncObjectHistoryUpdate($ciniki, $sync, $tnid, $o, array('uuid'=>$uuid));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.197', 'msg'=>"Unable to update $name($uuid) on local server", 'err'=>$rc['err']));;
                    }
                } 
            }
        }

        //
        // Update the history for the push side
        //
        if( ($sync['flags']&0x01) == 0x01 ) {
            foreach($local_list as $uuid => $last_updated) {
                //
                // Check if uuid does not exist, and has not been deleted
                //
                if( ($type == 'full' || !isset($remote_list[$uuid]) || $remote_list[$uuid] != $last_updated) ) {
                    $rc = ciniki_core_syncObjectHistoryGet($ciniki, $sync, $tnid, $o, array('uuid'=>$uuid));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.198', 'msg'=>"Unable to get $name($uuid) on local server", 'err'=>$rc['err']));
                    }
                    //
                    // Update the remote object
                    //
                    $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"$pkg.$mod.$name.history.update", 'uuid'=>$uuid, "object"=>$rc['object']));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.199', 'msg'=>"Unable to update $name($uuid) on remote server", 'err'=>$rc['err']));
                    }
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
