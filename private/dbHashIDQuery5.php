<?php
//
// Description
// -----------
// This function is used then the results need to be nested inside 
// outer containers.  When an SQL statement should produce a nested
// XML structure, this function can produce 3 deep.
// 
// FIXME: add documentation
//
// Arguments
// ---------
// ciniki:          
// strsql:          The SQL string to query the database.
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
// container_name:  The name of the xml/hash tag to return the data under, 
//                  when there is only one row returned.
// col_name:        The column to be used as the row ID within the result.
//
function ciniki_core_dbHashIDQuery5(&$ciniki, $strsql, $module, $col_x, $col_y, $col_z) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.59', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_rows'] = 0;
    $rsp['num_cols'] = 0;

    //
    // Build array of rows
    //
    $rsp[$col_x['container']] = array();
    $num_col_x = -1;
    $num_col_y = -1;
    $num_col_z = -1;
    $prev_col_x_value = "";
    $prev_col_y_value = "";
    $prev_col_z_value = "";
    while( $row = mysqli_fetch_assoc($result) ) {
        //
        // If we have a new value for column X, then start a new container
        //
        if( $row[$col_x['fname']] != $prev_col_x_value ) {
            $num_col_x++;
            $num_col_y=-1;
            $prev_col_y_value = "";
            $num_col_z=0;
            $rsp[$col_x['container']][$num_col_x] = array($col_x['name']=>array('id'=>$row[$col_x['fname']], $col_y['container']=>array()));
            foreach($col_x['fields'] as $field) {
                $rsp[$col_x['container']][$num_col_x][$col_x['name']][$field] = $row[$field];
            }
        } 
        if( $row[$col_y['fname']] != $prev_col_y_value ) {
            $num_col_y++;
            $num_col_z=0;
            $rsp[$col_x['container']][$num_col_x][$col_x['name']][$col_y['container']][$num_col_y] = array($col_y['name']=>array('id'=>$row[$col_y['fname']], $col_z['container']=>array()));
            foreach($col_y['fields'] as $field) {
                $rsp[$col_x['container']][$num_col_x][$col_x['name']][$col_y['container']][$num_col_y][$col_y['name']][$field] = $row[$field];
            }
        }
        if( !is_null($row[$col_z['fname']]) ) {
            $rsp[$col_x['container']][$num_col_x][$col_x['name']][$col_y['container']][$num_col_y][$col_y['name']][$col_z['container']][$num_col_z] = array($col_z['name']=>array());
            $rsp[$col_x['container']][$num_col_x][$col_x['name']][$col_y['container']][$num_col_y][$col_y['name']][$col_z['container']][$num_col_z][$col_z['name']]['id'] = $row[$col_z['fname']];

            foreach($col_z['fields'] as $field) {
                $rsp[$col_x['container']][$num_col_x][$col_x['name']][$col_y['container']][$num_col_y][$col_y['name']][$col_z['container']][$num_col_z][$col_z['name']][$field] = $row[$field];
            }
            $num_col_z++;
        }
        $prev_col_y_value = $row[$col_y['fname']];
        $prev_col_x_value = $row[$col_x['fname']];
    }

    $rsp['num_rows'] = $num_col_x;

    mysqli_free_result($result);

    return $rsp;
}
?>
