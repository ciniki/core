<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function ciniki_core_dropboxOpenWebloc($ciniki, $business_id, $client, $path) {

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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.90', 'msg'=>'Unable to get file', 'pmsg'=>curl_error($ch)));
        }
    }
    curl_close($ch);

    $url = '';

    //
    // Check for binary plist
    //
    if(substr($file_contents,0,8) == 'bplist00') {
        // Valid characters ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&'()*+,;=
        if( preg_match('/(http[a-zA-Z0-9\-\._\~:\/\?\#\[\]\@\!\$\&\'\(\)\*\+,;=]*)/', $file_contents, $matches) ) {
            $url = $matches[1];
        }
        
    } else {

        //
        // Parse contents
        //
        if( preg_match('/<string>(.*)<\/string>/', $file_contents, $matches) ) {
            $url = $matches[1];
        }
    }

    return array('stat'=>'ok', 'url'=>$url, 'contents'=>$file_contents);
}
?>
