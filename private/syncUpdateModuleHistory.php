<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function ciniki_core_syncUpdateModuleHistory(&$ciniki, &$sync, $tnid, $args) {
    //
    // Check the args
    //
    if( (!isset($args['uuid']) || $args['uuid'] == '' ) ) {
//      && (!isset($args['history']) || $args['history'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.351', 'msg'=>'No uuid specified'));
    }
    if( !isset($args['module']) || $args['module'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.352', 'msg'=>'No module specified'));
    }
    $a = preg_split('/\./', $args['module']);
    $pkg = $a[0];
    $mod = $a[1];

//  if( isset($args['uuid']) && $args['uuid'] != '' ) {
    if( !isset($args['history']) || $args['history'] == '' ) {
        //
        // Get the remote history to update
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
        $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>$pkg . '.' . $mod . ".history.get", 'uuid'=>$args['uuid']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.353', 'msg'=>"Unable to get the remote history for " . $args['module'], 'err'=>$rc['err']));
        }
        if( !isset($rc['history']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.354', 'msg'=>$args['module'] . " history not found on remote server"));
        }
        $remote_history = $rc['history'];
    } else {
        $remote_history = $args['history'];
    }

    //
    // Get the local history
    //
    ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sync', 'history_get');
    $get = $pkg . '_' . $mod . '_history_get';
    // BROKEN: This needs fixing!!!
    $rc = ciniki_customers_history_get($ciniki, $sync, $tnid, array('uuid'=>$remote_history['uuid']));
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.core.180' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.355', 'msg'=>'Unable to get history', 'err'=>$rc['err']));
    }
    if( !isset($rc['history'])
        || ($rc['history']['user'] == '' && $remote_history['user'] != '') 
        || ($rc['history']['table_key'] == '' && $remote_history['table_key'] != '') ) {
        //
        // history does not exist, add
        //
        $local_history = array();

        //
        // We need to translate the remote table key to local table key
        //
        if( isset($args['table_key_maps']) && isset($args['table_key_maps'][$remote_history['table_name']]) ) {
            $details = $args['table_key_maps'][$remote_history['table_name']];
            //
            // Lookup the object id reference for table key.  If we can't find the reference
            // then use uuid- and the remote uuid for the object instead.  This indicated it has never 
            // existed in the local system and we only have the history.
            //
//          error_log("SYNC-LOG [$tnid]: " . $details['lookup'] . '(' . $remote_history['table_key'] . ')');
            if( $remote_history['table_key'] != '' && strncmp($remote_history['table_key'], 'uuid-', 5) != 0 ) {
                //
                // The table_key on the remote end was blank, which means most likely the record was deleted
                //
                ciniki_core_loadMethod($ciniki, $details['package'], $details['module'], 'sync', $details['lookup']);
                $lookup = $details['package'] . '_' . $details['module'] . '_' . $details['lookup'];
                $rc = $lookup($ciniki, $sync, $tnid, array('remote_uuid'=> $remote_history['table_key']));
                if( $rc['stat'] != 'ok' ) {
//                  error_log('SYNC-WARN: Unable to locate local table key for (' . $remote_history['table_key'] . ')');
                    $remote_history['table_key'] = 'uuid-' . $remote_history['table_key'];
                } else {
                    $remote_history['table_key'] = $rc['id'];
                }
            }
        } else {
            ciniki_core_syncLog($ciniki, 0, 'No history table_key mapping for ' . $remote_history['table_name'] . '(' . $remote_history['table_key'] . ')', null);
        }

        //
        // Add the history to the history table
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
        if( isset($local_history) && count($local_history) > 0 ) {
            $rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $tnid, $args['module'],
                $args['history_table'], $remote_history['table_key'], $remote_history['table_name'], 
                array($remote_history['uuid']=>$remote_history), array($remote_history['uuid']=>$local_history), $args['new_value_maps']);
        } else {
            $rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $tnid, $args['module'],
                $args['history_table'], $remote_history['table_key'], $remote_history['table_name'], 
                array($remote_history['uuid']=>$remote_history), array(), $args['new_value_maps']);
        }
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.356', 'msg'=>'Unable to update customer history', 'err'=>$rc['err']));
        }
    }
    return array('stat'=>'ok');
}
?>
