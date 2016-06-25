<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
//
function ciniki_core_geocodeAddressLookupLatLong(&$ciniki, $address) {
    
    $prepAddr = str_replace(' ','+',$address);
    $url = 'https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false';
//  FIXME: Key doesn't work
//  if( isset($ciniki['config']['ciniki.web']['google.maps.php.key']) 
//      && $ciniki['config']['ciniki.web']['google.maps.php.key'] != ''
//      ) {
//      $url .= '&key=' . $ciniki['config']['ciniki.web']['google.maps.php.key'];   
//  }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $geocode = curl_exec($ch);
    curl_close($ch);

    $output = json_decode($geocode);
    if( $output->status != 'OK' ) {
//      error_log(print_r($output, true));
        error_log("ERR: google map lookup failed for '$address':" . $output->status);
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1925', 'msg'=>'We were unable to determine where this address is, please try again.'));
    } else {
        $latitude = $output->results[0]->geometry->location->lat;
        $longitude = $output->results[0]->geometry->location->lng;
    }

    return array('stat'=>'ok', 'latitude'=>$latitude, 'longitude'=>$longitude);
}
?>
