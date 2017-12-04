<?php
//
// Description
// -----------
// This function will return a list of tags available for a tenant module.
//
// Arguments
// ---------
// ciniki:
// module:              The package.module the tag is located in.
// tnid:         The ID of the tenant to get the available tags for.
// main_table:          The main table containing the tenant items.
// main_key_name:       The key field name in the main table.  This is used to link
//                      the main table with the tags table 'table'.
// table:               The database table that stores the tags.
// key_name:            The key field in the tags table that links back to the main table.
// type:                The type of the tag.  If passed as 0, then return all available tags an their type.
//
//                      0 - return all tags.
//                      1 - List
//                      2 - Category **future**
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_core_tagsList($ciniki, $module, $tnid, $table, $type) {

    // Required functions
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    $strsql = "SELECT DISTINCT CONCAT_WS('-', tag_type, tag_name) AS fname, tag_type, tag_name "
        . "FROM $table "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( $type > 0 ) {
        $strsql .= "AND $table.tag_type = '" . ciniki_core_dbQuote($ciniki, $type) . "' ";
    }
    $strsql .= "ORDER BY tag_name ";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, $module, array(
        array('container'=>'tags', 'fname'=>'fname', 'name'=>'tag',
            'fields'=>array('type'=>'tag_type', 'name'=>'tag_name')),
        ));

    return $rc;
}
?>
