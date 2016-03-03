<?php
/**
 * Basic, global functions.
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes
 */

defined( 'ABSPATH' ) || die();

/**
 * Gets the main class object.
 *
 * Used to instantiate the plugin class for the first time and then used subsequent times to return the existing object.
 *
 * @since 0.1.0
 *
 * @return AC
 */
function AC() {
	return AC::instance();
}

/**
 * Recursively runs through the array, turning all values from strings to bool for 'true' and 'false'.
 *
 * @since 0.1.0
 *
 * @param array $input
 *
 * @return array
 */
function ac_string_to_bool( $input ) {

	foreach ( $input as $key => $value ) {

		if ( is_array( $value ) ) {
			$input[ $key ] = ac_string_to_bool( $value );
		}

		if ( $value === 'true' ) {
			$input[ $key ] = true;
		} elseif ( $value === 'false' ) {
			$input[ $key ] = false;
		}
	}

	return $input;
}

/**
 * Sorts an array by the "position" value (used in usort() or uasort()).
 *
 * @since 0.1.0
 *
 * @param $a
 * @param $b
 *
 * @return mixed
 */
function ac_sort_by_position( $a, $b ) {
	return $a['position'] - $b['position'];
}