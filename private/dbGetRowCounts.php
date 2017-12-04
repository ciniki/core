<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get the table row counts for.
//
function ciniki_core_dbGetRowCounts(&$ciniki, $tnid) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');

    //
    // Get modules which are enabled for the tenant, and their checksums
    //
    $strsql = "SELECT CONCAT_WS('.', package, module) AS fname, "
        . "package, module AS name, UNIX_TIMESTAMP(last_change) AS last_change "
        . "FROM ciniki_tenant_modules "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (status = 1 OR status = 2) "
        . "ORDER BY fname "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'modules', 'fname'=>'fname',
            'fields'=>array('package', 'name', 'last_change')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    if( !isset($modules['ciniki.tenants']) ) {
        $modules['ciniki.tenants'] = array('package'=>'ciniki', 'name'=>'tenants');
    }
    if( !isset($modules['ciniki.images']) ) {
        $modules['ciniki.images'] = array('package'=>'ciniki', 'name'=>'images');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncModuleObjects');

    //
    // Load the objects and get table counts for each
    //
    foreach($modules as $mid => $module) {
        $modules[$mid]['tables'] = array();
        $rc = ciniki_core_syncModuleObjects($ciniki, $tnid, $mid, 'full');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['objects']) ) {
            continue;
        }
        
        $objects = $rc['objects'];
        $settings = array();
        if( isset($rc['settings']) ) {
            $settings = $rc['settings'];
        }

        $history_table = '';
        // Store the table name to lookup in history
        $tables = array();
        foreach($objects as $oid => $obj) {
            if( $obj['table'] == 'ciniki_tenants' ) {
                continue;
            }
            $strsql = "SELECT COUNT(*) AS count "
                . "FROM " . ciniki_core_dbQuote($ciniki, $obj['table']) . " "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, $mid, 'rowcount');
            if( $rc['stat'] != 'ok' ) {
                $modules[$mid]['tables'][$obj['table']] = array('name'=>$obj['table'], 'rows'=>'Unknown');
            } else {
                $modules[$mid]['tables'][$obj['table']] = array('name'=>$obj['table'], 'rows'=>$rc['rowcount']['count']);
            }
            $history_table = $obj['history_table'];
            $tables[] = $obj['table'];
        }

        if( $history_table != '' ) {
            $strsql = "SELECT COUNT(*) AS count "
                . "FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND table_name IN (" . ciniki_core_dbQuoteList($ciniki, $tables) . ") "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, $mid, 'rowcount');
            if( $rc['stat'] != 'ok' ) {
                $modules[$mid]['tables'][$history_table] = array('name'=>$history_table, 'rows'=>'Unknown');
            } else {
                $modules[$mid]['tables'][$history_table] = array('name'=>$history_table, 'rows'=>$rc['rowcount']['count']);
            }
        }
    }

    return array('stat'=>'ok', 'modules'=>$modules);
}
?>
