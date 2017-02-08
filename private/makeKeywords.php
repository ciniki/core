<?php
//
// Description
// -----------
// This function will create a keyword string with common words removed, and sorted into alphabetical for faster searching
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_core_makeKeywords($ciniki, $str) {

    $common_words = array(
        'a', 'i',
        'an', 'on', 'in',
        'and', 'the', 'for', 'any', 'are', 'but', 'not', 'was', 'our', 
        'all', 'has', 'use', 'too', 'put', 'let', 'its', "it's", 
        'they', "they're", 'there', 'their');


    $str = preg_replace('/[^a-zA-Z0-9]/', ' ', $str);
    $str = preg_replace('/\s\s/', ' ', $str);
    $str = strtolower($str);
    $words = explode(' ', $str);

    //
    // Remove 2 letter words, and common words
    //
    foreach($words as $wid => $word) {
        if( in_array($word, $common_words) ) {
            unset($words[$wid]);
        }
    }

    //
    // Sort the words
    //
    sort($words);

    //
    // Remove duplicates, and join into single string
    //
    $keywords = implode(' ', array_unique($words));

    return $keywords;
}
?>
