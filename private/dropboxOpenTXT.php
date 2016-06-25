<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function ciniki_core_dropboxOpenTXT($ciniki, $business_id, $client, $path) {

    //
    // Get the file contents
    //
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $client->getAccessToken()));
    if( $path[0] != '/' ) { $path = '/' . $path; }
    curl_setopt($ch, CURLOPT_URL, "https://api-content.dropbox.com/1/files/auto" . curl_escape($ch, $path));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
    $file_contents = curl_exec($ch);
    if( $file_contents === false ) {
        //
        // Try again after failure
        //
        $file_contents = curl_exec($ch);
        if( $file_contents === false ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2292', 'msg'=>'Unable to get file', 'pmsg'=>curl_error($ch)));
        }
    }
    curl_close($ch);

    return array('stat'=>'ok', 'content'=>$file_contents);
}
?>
