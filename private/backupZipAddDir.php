<?php
//
// Description
// -----------
// This method will backup a tenant to the ciniki-backups folder
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
//
//
function ciniki_core_backupZipAddDir(&$ciniki, &$zip, $dir, $base) {
    $zip->addEmptyDir($base);
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file)) {
            $path_parts = pathinfo($file);
            $rc = ciniki_core_backupZipAddDir($ciniki, $zip, $file, $base . '/' . $path_parts['basename']);
        } else {
            $path_parts = pathinfo($file);
            $zip->addFile($file, $base . '/' . $path_parts['basename']);
        }
    }

    return array('stat'=>'ok');
}
?>
