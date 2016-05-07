<?php
//
// Description
// -----------
// This function will recursively remove files and directories.  It is to be used
// when clearing caches.
//
// Arguments
// ---------
// ciniki: 			The standard ciniki data structure, the arguments will be parsed into it.
//
function ciniki_core_recursiveRmdir($ciniki, $dir, $skip=array()) {

	$fp = opendir($dir);
	if( $fp ) {
		while( $f = readdir($fp)) {
			$file = $dir . '/' . $f;
			if( $f == '.' || $f == '..' ) { 
                continue; 
            }
            if( in_array($f, $skip) ) { 
                continue; 
            }
			elseif( is_dir($file) && !is_link($file) ) {
				$rc = ciniki_core_recursiveRmdir($ciniki, $file);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( !rmdir($file) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'646', 'msg'=>'Unable to remove directory: ' . $file));
				}
			}
			else {
				if( !unlink($file) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'647', 'msg'=>'Unable to remove file: ' . $file));
				}
			}
		}
		closedir($fp);
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'648', 'msg'=>'Unable to open directory: ' . $dir));
	}

	return array('stat'=>'ok');
}
?>
