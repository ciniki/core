<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_core_numToRoman(&$ciniki, $tnid, $num, $args) {

    $c = 'IVXLCDM';
    if( isset($args['lowercase']) && $args['lowercase'] == 'yes' ) {
        $c = 'ivxlcdm';
    }
    $result = '';
    for ($x = 5, $y = $result = ''; $num; $y++, $x ^= 7) {
        $o = $num % $x;
        $num = $num / $x ^ 0;
        for (; $o--; $result = $c[$o > 2 ? $y + $num - ($num = -2) + $o = 1 : $y] . $result);
    }

    return array('stat'=>'ok', 'roman'=>$result);
}
?>
