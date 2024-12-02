<?php
//
// Description
// -----------
// This function will parse an number and return that number as an age.
//
// Info
// ----
// Status:          beta
//
// Arguments
// ---------
// ciniki:
// age:         The age in seconds.
//
function ciniki_core_dbParseAge($ciniki, $age) {
    if( !is_numeric($age) ) {
        return 0;
    }
    if( $age < 60 ) {                   return '< 1 min';
    } elseif( $age < 120 ) {            return '1 min';
    } elseif( $age < 3600 ) {           return (int)($age/60) . ' minutes';
    } elseif( $age < 7200 ) {           return '1 hour';
    } elseif( $age < 86400 ) {          return (int)($age/3600) . ' hours';
    } elseif( $age < 172800 ) {         return '1 day';
    } elseif( $age < 2678400 ) {        return (int)($age/86400) . ' days';
    } elseif( $age < 5356800 ) {        return '1 month';
    } elseif( $age < 31536000 ) {       return (int)($age/2678400) . ' months';
    } elseif( $age < 63072000 ) {       return '1 year';
    } else {                            return (int)($age/31536000) . ' years';
    }   
}
?>
