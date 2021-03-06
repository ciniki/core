<?php
//
// Description
// -----------
// This function is the same as dbRspQuery but will add an addition output of the users
// if finds via the user_id specifed in the query.
//
// Info
// ----
// status:              beta
//
// Arguments
// ---------
// ciniki:              
// strsql:              The SQL string to query the database with.
// module:              The name of the module for the transaction, which should include the 
//                      package in dot notation.  Example: ciniki.artcatalog
// container_name:      The container name to attach the data when only one row returned.
// row_name:            The row name to attached each row to.
// no_row_error:        The error code and msg to return when no rows were returned from the query.
//
function ciniki_core_dbRspQueryPlusUsers(&$ciniki, $strsql, $module, $container_name, $row_name, $no_row_error) {
    //
    // Check connection to database, and open if necessary
    //
    $rc = ciniki_core_dbConnect($ciniki, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Prepare and Execute Query
    //
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.77', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    if( mysqli_num_rows($result) <= 0 ) {
        return $no_row_error;
    }

    //
    // FIXME: If hash, then return all rows together as a hash
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_rows'] = 0;

    //
    // Build array of rows
    //
    $rsp[$container_name] = array();
    $users = array();
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');
    while( $row = mysqli_fetch_assoc($result) ) {
        $rsp[$container_name][$rsp['num_rows']] = array($row_name=>$row);
        // $users[$row['user_id']] = 1;
        array_push($users, $row['user_id']);
        if( isset($row['age']) ) {
            $rsp[$container_name][$rsp['num_rows']][$row_name]['age'] = ciniki_core_dbParseAge($ciniki, $row['age']);
        }
        $rsp['num_rows']++;
    }

    mysqli_free_result($result);

    //
    // FIXME: Get the list of users
    //
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'userDisplayNames');
//  $rc = ciniki_users_userDisplayNames($ciniki, 'users', $users);
//  if( $rc['stat'] != 'ok' ) {
//      return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.78', 'msg'=>'Unable to link users', 'err'=>$rc['err']));
//  }
//  if( !isset($rc['users']) ) {
//      return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.79', 'msg'=>'Unable to link users', 'err'=>$rc['err']));
//  }
//  $rsp['users'] = $rc['users'];
//
//  return $rsp;
//

    //
    // Get the users who contributed to the actions
    //
    $rc = ciniki_core_dbConnect($ciniki, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    $strsql = "SELECT id, display_name "
        . "FROM ciniki_users "
        . "WHERE id IN (" . ciniki_core_dbQuote($ciniki, implode(',', array_keys($users))) . ") ";
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.80', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    if( mysqli_num_rows($result) <= 0 ) {
        return array('stat'=>'ok', 'history'=>array(), 'users'=>array());
    }

    $num_users = 0;
    while( $row = mysqli_fetch_assoc($result) ) {
        $num_users++;
        $rsp['users'][$row['id']] = array('user'=>$row);
    }

    mysqli_free_result($result);

    return $rsp;
}
?>
