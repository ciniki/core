<?php
//
// Description
// -----------
// This method will return the list of locales supported by Ciniki.  This list
// should be expanded in the future to include other countries.
//
// Arguments
// ---------
//
// Returns
// -------
// <locales>
//     <locale id="en_US" name="United States - English" />
//     <locale id="en_CA" name="Canada - English" />
//     <locale id="fr_CA" name="Canada - French" />
// </countries>
//
function ciniki_core_getLocales($ciniki) {
	$locales = array(
		array('locale'=>array('id'=>'en_US', 'name'=>'United States - English')),
		array('locale'=>array('id'=>'en_CA', 'name'=>'Canada - English')),
		array('locale'=>array('id'=>'fr_US', 'name'=>'Canada - French')),
		);

	return array('stat'=>'ok', 'locales'=>$locales);
}
?>
