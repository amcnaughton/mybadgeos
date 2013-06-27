<?php
/**
 * Misc support functions
 *
 * @package BadgeOS
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
  * Sort an array by a key within the array_items
  * Items can be arrays or objects, but must all be the same type
  * source - http://top-frog.com/2009/07/29/sort-arrays-by-key-revisited/
  * 
  * @param $data - the array of items to work on
  * @param $sort_key - an array key or object member to use as the sort key
  * @param $ascending - wether to sort in reverse/descending order
  * @return array - sorted array
  */
function badgeos_array_sort_by_key($data, $sort_key, $ascending = true) {
	$order = $ascending ? '$a,$b' : '$b,$a';

	if (is_object(current($data))) { 
		$callback = create_function($order,'return strnatcasecmp($a->'.$sort_key.',$b->'.$sort_key.');'); 
	}
	else {
		$callback = create_function($order,'return strnatcasecmp($a["'.$sort_key.'"],$b["'.$sort_key.'"]);');
	}
	uasort($data,$callback);

	return $data;
}

function print_filters_for( $hook = '' ) {
    global $wp_filter;
    if( empty( $hook ) || !isset( $wp_filter[$hook] ) )
        return;

    print '<pre>';
    print_r( $wp_filter[$hook] );
    print '</pre>';
}