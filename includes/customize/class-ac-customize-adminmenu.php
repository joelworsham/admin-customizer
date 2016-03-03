<?php
/**
 * Customizes the Admin Menu.
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes/customize
 */

defined( 'ABSPATH' ) || die();

/**
 * Class AC_Customize_AdminMenu
 *
 * Customizes the Admin Menu.
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes/customize
 */
class AC_Customize_AdminMenu {

	/**
	 * The current admin menu.
	 *
	 * @since 0.1.0
	 *
	 * @var array|bool
	 */
	public $current_menu = false;

	/**
	 * The custom admin menu for the current role, if set.
	 *
	 * @since 0.1.0
	 *
	 * @var array|bool
	 */
	public $custom_menu = false;

	/**
	 * AC_Customize_AdminMenu constructor.
	 *
	 * @since 0.1.0
	 */
	function __construct() {

		add_filter( 'custom_menu_order', array( $this, 'maybe_allow_custom_menu_order' ) );
		add_filter( 'menu_order', array( $this, 'custom_menu_order' ) );
		add_action( 'admin_init', array( $this, 'custom_submenu_order' ) );

		if ( ! isset( $_REQUEST['ac_customize'] ) ) {
			add_action( 'admin_init', array( $this, 'remove_menu_items' ), 100 );
		}
	}

	/**
	 * Allow custom menu ordering if a custom menu is set.
	 *
	 * @since 0.1.0
	 * @access private
	 *
	 * @return bool
	 */
	function maybe_allow_custom_menu_order() {

		return $this->custom_menu !== false;
	}

	/**
	 * Customizes the menu order.
	 *
	 * @since 0.1.0
	 * @access private
	 *
	 * @param array $custom_menu_order
	 *
	 * @return array
	 */
	function custom_menu_order( $custom_menu_order ) {

		if ( ! $this->custom_menu ) {
			return $custom_menu_order;
		}

		// Simply use our custom menu as the sorting mechanism
		$custom_menu_order = wp_list_pluck( $this->custom_menu, 'slug', 'position' );

		return $custom_menu_order;
	}

	/**
	 * Sorts the submenus.
	 *
	 * Takes advantage of the WP native function "sort_menu()" in order to sort the submenus just as WP does the menu.
	 *
	 * @since 0.1.0
	 * @access private
	 * @see sort_menu()
	 */
	function custom_submenu_order() {

		global $submenu, $menu_order, $default_menu_order;

		if ( ! $this->custom_menu ) {
			return;
		}

		$custom_menu_order = wp_list_pluck( $this->custom_menu, 'slug' );

		// Now for the submenus
		foreach ( $custom_menu_order as $menu_slug ) {
			if ( isset( $submenu[ $menu_slug ] ) ) {

				$custom_submenu_order = $this->custom_menu[ array_search( $menu_slug, $custom_menu_order ) ]['submenu'];

				$menu_order         = array_flip( wp_list_pluck( $custom_submenu_order, 'slug', 'position' ) );
				$default_menu_order = array_flip( wp_list_pluck( $submenu[ $menu_slug ], 2 ) );

				usort( $submenu[ $menu_slug ], 'sort_menu' );
			}
		}

		unset( $menu_order, $default_menu_order );
	}

	/**
	 * Removes menu items that have been removed via the Interface.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function remove_menu_items() {

		global $menu, $submenu;

		if ( ! $this->custom_menu ) {
			return;
		}

		foreach ( $this->custom_menu as $menu_item_i => $menu_item ) {

			if ( $menu_item['remove'] == 'true' ) {
				unset( $menu[ $menu_item['position'] ] );
			}

			// Submenu
			if ( is_array( $menu_item['submenu'] ) ) {
				foreach ( $menu_item['submenu'] as $submenu_item_i => $submenu_item ) {
					if ( $submenu_item['remove'] == 'true' && isset( $menu[ $menu_item['position'] ] ) ) {
						unset( $submenu[ $menu[ $menu_item['position'] ][2] ][ $submenu_item['position'] ] );
					}
				}
			}
		}
	}
}