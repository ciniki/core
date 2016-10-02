<?php
//
// Description
// -----------
// This function will check for an open connection to the database, 
// and if not return a new connection.
//
// Arguments
// ---------
// ciniki:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
//
function ciniki_core_dbConnect(&$ciniki, $module) {
    // 
    // Check for required $ciniki variables
    //
    if( !is_array($ciniki['config']['ciniki.core']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'13', 'msg'=>'Internal Error', 'pmsg'=>'$ciniki variable not defined'));
    }

    //
    // Get the database name for the module specified.  If the
    // module does not have a database specified, then open
    // the default core database
    //
    $database_name = '';
    if( isset($ciniki['config'][$module]['database']) ) {
        $database_name = $ciniki['config'][$module]['database'];
    } elseif( isset($ciniki['config']['ciniki.core']['database']) ) {
        $database_name = $ciniki['config']['ciniki.core']['database'];
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'14', 'msg'=>'Internal Error', 'pmsg'=>'database name not default for requested module'));
    }

    //
    // Check if database connection is already open
    //
    if( isset($ciniki['databases'][$database_name]['connection']) && is_object($ciniki['databases'][$database_name]['connection']) ) {
    //  error_log('dbConnect: ' . $module . ' - cached');
        return array('stat'=>'ok', 'dh'=>$ciniki['databases'][$database_name]['connection']);
    }

    //
    // Check if database has been specified in config file, and setup in the databases array.
    //
    if( !is_array($ciniki['databases'][$database_name]) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'15', 'msg'=>'Internal Error', 'pmsg'=>'database name not specified in config.ini'));
    }

    //
    // Get connection information
    //
    if( !isset($ciniki['config']['ciniki.core']['database.' . $database_name . '.hostname'])
        || !isset($ciniki['config']['ciniki.core']['database.' . $database_name . '.username'])
        || !isset($ciniki['config']['ciniki.core']['database.' . $database_name . '.password'])
        || !isset($ciniki['config']['ciniki.core']['database.' . $database_name . '.database'])
        ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'16', 'msg'=>'Internal configuration error', 'pmsg'=>"database credentials not specified for the module '$module'"));
        
    }

    //
    // Open connection to the database requested,
    // and ensure a new connection is opened (TRUE).
    //
    $ciniki['databases'][$database_name]['connection'] = mysqli_connect(
        $ciniki['config']['ciniki.core']['database.' . $database_name . '.hostname'],
        $ciniki['config']['ciniki.core']['database.' . $database_name . '.username'],
        $ciniki['config']['ciniki.core']['database.' . $database_name . '.password'], 
        $ciniki['config']['ciniki.core']['database.' . $database_name . '.database']);

    if( $ciniki['databases'][$database_name]['connection'] == false ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'18', 'msg'=>'Database error', 'pmsg'=>"Unable to connect to database '$database_name' for '$module'"));
    }

//  if( mysql_select_db($ciniki['config']['ciniki.core']['database.' . $database_name . '.database'], $ciniki['databases'][$database_name]['connection']) == false ) {
//      return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'20', 'msg'=>'Database error', 'pmsg'=>"Unable to connect to database '$database_name' for '$module'"));
//  }

    return array('stat'=>'ok', 'dh'=>$ciniki['databases'][$database_name]['connection']);
}
?>
