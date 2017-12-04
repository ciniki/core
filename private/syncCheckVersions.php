<?php
//
// Description
// -----------
// This function will check the local and remote tenant information
// for compatibility.  The tables must all be at the same version on
// either side of the sync, and the modules must be enabled and the 
// same version.  
//
// The version information for modules is stores in the _versions.ini file
// and should be generated whenever the code is updated.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
// sync_id:         The ID of the sync to check compatibility with.
//
function ciniki_core_syncCheckVersions($ciniki, $sync, $tnid) {

    //
    // Make the request for the remote tenant information
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
    $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.core.info'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $remote_modules = $rc['modules'];

    // 
    // Get the local tenant information
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncTenantInfo');
    $rc = ciniki_core_syncTenantInfo($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['modules']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.200', 'msg'=>'No modules enabled'));
    }

    // 
    // Re-index for fast lookup
    //
    $local_modules = array();
    foreach($rc['modules'] as $mnum => $module) {
        $tables = array();
        if( isset($module['module']['tables']) ) {
            foreach($module['module']['tables'] as $tnum => $table) {
                $tables[$table['table']['name']] = array('name'=>$table['table']['name'], 'version'=>$table['table']['version']);
            }
        }
        $local_modules[$module['module']['package'] . '.' . $module['module']['name']] = 
            array('package'=>$module['module']['package'], 'name'=>$module['module']['name'], 
                'version'=>$module['module']['version'],
                'hash'=>$module['module']['hash'],
                'last_change'=>$module['module']['last_change'],
                'tables'=>$tables);
    }

    //
    // Compare local and remote tenant information
    //
    $errors = '';
    $missing_modules = '';
    $incompatible_versions = '';
    $noupgrade_modules = '';
    $comma = '';
    $r_modules = array();
    foreach($remote_modules as $mnum => $module) {
        $name = $module['module']['package'] . '.' . $module['module']['name'];
        $tables = array();
        if( !isset($local_modules[$name]) ) {
            $errors .= $comma . "missing module $name";
            $missing_modules .= $comma . $name;
            $comma = ', ';
        } elseif( $module['module']['version'] != $local_modules[$name]['version'] ) {
            //
            // If code url specified, then upgrade
            //
//          if( isset($ciniki['config']['ciniki.core']['sync.code.url']) && $ciniki['config']['ciniki.core']['sync.code.url'] != '' ) {
//              ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpgradeModule');
//              $rc = ciniki_core_syncUpgradeModule($ciniki, $module['module']['package'], $module['module']['name']);
//              if( $rc['stat'] != 'ok' || !isset($rc['version']) ) {
//                  $errors .= $comma . "module $name unable to upgrade";
//                  $noupgrade_modules .= $comma . $name;
//                  $comma = ', ';
//              } elseif( $rc['version'] != $local_modules[$name]['version'] ) {
//                  $errors .= $comma . "module $name incorrect version";
//                  $incompatible_versions .= $comma . $name;
//                  $comma = ', ';
//              }
//          } else {
                $errors .= $comma . "module $name incorrect version";
                $incompatible_versions .= $comma . $name;
                $comma = ', ';
//          }
        } else {
            foreach($module['module']['tables'] as $tnum => $table) {
                $tname = $table['table']['name'];
                if( !isset($local_modules[$name]['tables'][$tname]) ) {
                    $errors .= $comma . "missing table $tname";
                    $comma = ', ';
                }
                elseif( $table['table']['version'] != $local_modules[$name]['tables'][$tname]['version'] ) {
                    $errors .= $comma . "incorrect table version $tname (" . $local_modules[$name]['tables'][$tname]['version'] . " should be " . $table['table']['version'] . ")";
                    $comma = ', ';
                }
                $tables[$table['table']['name']] = array('name'=>$table['table']['name'], 'version'=>$table['table']['version']);
            }
        }
        $r_modules[$name] = 
            array('package'=>$module['module']['package'], 'name'=>$module['module']['name'], 
                'version'=>$module['module']['version'],
                'hash'=>$module['module']['hash'],
                'last_change'=>$module['module']['last_change'],
                'tables'=>$tables);
    }

    if( $missing_modules != '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.201', 'msg'=>"The following modules must be enabled before syncronization: $missing_modules", 'pmsg'=>"$errors."));
    }

    if( $incompatible_versions != '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.202', 'msg'=>"The following modules must be updated before syncronization: $incompatible_versions", 'pmsg'=>"$errors."));
    }

    if( $errors != '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.203', 'msg'=>'System code must be updated before synchronization.', 'pmsg'=>"$errors"));
    }

    return array('stat'=>'ok', 'sync'=>$sync, 'modules'=>$local_modules, 'remote_modules'=>$r_modules);
}
?>
