<?php
/**
 * The interface for customizing the dashboard.
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes
 */

defined( 'ABSPATH' ) || die();

/**
 * Class AC_Interface
 *
 * The interface for customizing the dashboard.
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes
 */
class AC_Interface {

	/**
	 * The Customize Admin Menu module.
	 *
	 * @since 0.1.0
	 *
	 * @var AC_Customize_AdminMenu
	 */
	private $customize_adminmenu;

	/**
	 * The currently being modified role.
	 *
	 * @since 0.1.0
	 *
	 * @var string|bool
	 */
	private $current_role = false;

	/**
	 * The currently being modified role capabilities.
	 *
	 * @since 0.1.0
	 *
	 * @var array
	 */
	private $current_role_caps;

	/**
	 * AC_Interface constructor.
	 *
	 * @since 0.1.0
	 */
	function __construct() {

		$this->customize_adminmenu = new AC_Customize_AdminMenu();

		add_action( 'init', array( $this, 'setup_role' ), 1 );
		add_action( 'init', array( $this, 'get_customizations' ) );
		add_action( 'wp_ajax_ac-save-interface', array( $this, 'ajax_save_interface' ) );
		add_action( 'wp_ajax_ac-reset-interface', array( $this, 'ajax_reset_interface' ) );

		if ( isset( $_REQUEST['ac_customize'] ) ) {

			add_action( 'admin_init', array( $this, 'get_adminmenu' ) );
			add_action( 'admin_init', array( $this, 'sync_custom_menu' ), 50 );
			add_action( 'admin_init', array( $this, 'setup_data' ), 100 );
//			add_action( 'admin_menu', function () {
//
//				global $menu, $submenu;
//
//				foreach ( $menu as $menu_item_i => $menu_item ) {
//
//					$menu[ $menu_item_i ][0] .= "<input type=\"hidden\" name=\"ac_menu[$menu_item[2]]\" \>";
//				}
//			});
			add_filter( 'admin_body_class', array( $this, 'body_class' ) );
			add_action( 'admin_footer', array( $this, 'interface_toolbar_HTML' ) );
			add_action( 'adminmenu', array( $this, 'interface_adminmenu_trash_HTML' ) );

			if ( isset( $_REQUEST['ac_current_role'] ) ) {
				add_action( 'user_has_cap', array( $this, 'modify_role_capabilities' ), 10, 4 );
			}
		} else {

			add_action( 'adminmenu', array( $this, 'launch_interface_HTML' ) );
		}
	}

	/**
	 * Outputs the interface's HTML.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function interface_toolbar_HTML() {

		$roles = get_editable_roles();

		$current_user_role = $this->current_role;

		include_once __DIR__ . '/views/html-interface-toolbar.php';
	}

	/**
	 * Outputs the interface adminmenu trash HTML.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function interface_adminmenu_trash_HTML() {

		include_once __DIR__ . '/views/html-interface-adminmenu-trash.php';
	}

	/**
	 * Outputs the interface's launch button HTML.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function launch_interface_HTML() {

		include_once __DIR__ . '/views/html-interface-launch.php';
	}

	/**
	 * Setups up params and JS data.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function setup_data() {

		AC()->add_script_data( 'launch_interface' );
		AC()->add_script_data( 'current_role', $this->current_role );
		AC()->add_script_data( 'nonce', wp_create_nonce( 'ac-nonce' ) );

		if ($this->customize_adminmenu->custom_menu) {
			AC()->add_script_data( 'current_menu', $this->customize_adminmenu->custom_menu );
		} else {
			AC()->add_script_data( 'current_menu', $this->customize_adminmenu->current_menu );
		}
	}

	/**
	 * Adds the customize class.
	 *
	 * @since 0.1.0
	 * @access private
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	function body_class( $class ) {

		$class .= ' ac-customize';

		return $class;
	}

	/**
	 * Sets the role (capabilities) for the adminmenu output.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function modify_role_capabilities( $user_caps ) {

		if ( $this->current_role_caps ) {
			return $this->current_role_caps;
		}

		return $user_caps;
	}

	/**
	 * Setup the current role and capabilities for the interface.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function setup_role() {

		if ( ! $this->current_role ) {

			if ( isset( $_REQUEST['ac_current_role'] ) ) {

				$this->current_role = $_REQUEST['ac_current_role'];
			} else {

				$current_user       = wp_get_current_user();
				$this->current_role = array_shift( $current_user->roles );
			}

			if ( $role = get_role( $this->current_role ) ) {
				$this->current_role_caps = $role->capabilities;
			}
		}
	}

	/**
	 * Gets any set role customizations.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function get_customizations() {

		$menu_customizations = false;

		if ( $customizations = get_option( "ac_customize_$this->current_role" ) ) {

			if ( isset( $customizations['menu'] ) && ! empty( $customizations['menu'] ) ) {
				$menu_customizations = $customizations['menu'];
			}
		}

		/**
		 * Allows filtering the custom menu.
		 *
		 * @since 0.1.0
		 */
		$this->customize_adminmenu->custom_menu = apply_filters( 'ac_custom_menu', $menu_customizations );
	}

	/**
	 * Gets the adminmenu for use in the Interface.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function get_adminmenu() {

		global $menu, $submenu;

		$ac_menu = array();

		// Organize the menu / submenu into a new array
		$menu_item_i = 0;
		foreach ( $menu as $menu_position => $menu_item ) {

			$ac_submenu = false;
			if ( isset( $submenu[ $menu_item[2] ] ) ) {

				$ac_submenu = array();

				$submenu_item_i = 0;
				foreach ( $submenu[ $menu_item[2] ] as $submenu_position => $submenu_item ) {

					$ac_submenu[] = array(
						'position' => $submenu_item_i,
						'slug'     => isset( $submenu_item[2] ) ? $submenu_item[2] : false,
						'remove'   => false,
						// Not in use
						// 'submenu_title' => isset( $submenu_item[0] ) ? $submenu_item[0] : false,
						// 'capability'    => isset( $submenu_item[1] ) ? $submenu_item[1] : false,
					);

					$submenu_item_i ++;
				}
			}

			$ac_menu[] = array(
				'position' => $menu_item_i,
				'submenu'  => $ac_submenu,
				'remove'   => false,
				'slug'     => isset( $menu_item[2] ) ? $menu_item[2] : false,
				// Not in use
				// 'icon'       => isset( $menu_item[6] ) ? $menu_item[6] : false,
				// 'hook_name'  => isset( $menu_item[5] ) ? $menu_item[5] : false,
				// 'classes'    => isset( $menu_item[4] ) ? $menu_item[4] : false,
				// 'page_title' => isset( $menu_item[3] ) ? $menu_item[3] : false,
				// 'capability' => isset( $menu_item[1] ) ? $menu_item[1] : false,
//				 'menu_title' => isset( $menu_item[0] ) ? $menu_item[0] : false,
			);

			$menu_item_i ++;
		}

		// Set the menu
		$this->customize_adminmenu->current_menu = $ac_menu;
	}

	/**
	 * Syncs the custom menu with the current menu for use in the interface data.
	 *
	 * This is done to account for menu items being removed or positions changed due to plugin and / or theme changes.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function sync_custom_menu() {

		if ( ! $this->customize_adminmenu->custom_menu ) {
			return;
		}

		$current_menu_map = wp_list_pluck( $this->customize_adminmenu->current_menu, 'slug' );

		$new_custom_menu = array();

		foreach ( $this->customize_adminmenu->custom_menu as $menu_item_i => $menu_item ) {

			if ( isset( $current_menu_map[ $menu_item_i ] ) &&
			     $current_menu_map[ $menu_item_i ] === $menu_item['slug']
			) {

				// Exact match, put into new custom menu
				$new_custom_menu[ $menu_item_i ] = $menu_item;

			} elseif ( ( $key = array_search( $menu_item['slug'], $current_menu_map, true ) ) !== false ) {

				// Still in menu, but moved. Move accordingly.
				$new_custom_menu[ $key ] = $menu_item;
			}

			// Not in menu, just don't add to new custom menu
		}

		ksort( $new_custom_menu );
		$this->customize_adminmenu->custom_menu = $new_custom_menu;
	}

	/**
	 * Saves the supplied menu via AJAX.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function ajax_save_interface() {

		if ( ! isset( $_REQUEST['menu'] ) ||
		     ! isset( $_REQUEST['role'] )
		) {

			wp_send_json( array(
				'status'    => 'fail',
				'error_msg' => 'Could not get menu or role',
			) );
		}

		if ( ! check_ajax_referer( 'ac-nonce', 'ac_nonce' ) ) {

			wp_send_json( array(
				'status'    => 'fail',
				'error_msg' => 'Could not verify security',
			) );
		}

		$menu = $_REQUEST['menu'];
		$role = $_REQUEST['role'];

		// Make sure all strings of 'true' and 'false' are bool
		$menu = ac_string_to_bool( $menu );

		// Order menu by the "position" field
		usort( $menu, 'ac_sort_by_position' );
		foreach ( $menu as &$menu_item ) {
			if ( $menu_item['submenu'] ) {
				usort( $menu_item['submenu'], 'ac_sort_by_position' );
			}
		}
		unset( $menu_item );

		$old_value = get_option( "ac_customize_$role" );
		$new_value = array(
			'menu' => $menu,
		);

		if ( $old_value === $new_value ) {

			wp_send_json( array(
				'status'    => 'fail',
				'error_msg' => 'Option already saved.',
			) );
		} else {

			if ( update_option( "ac_customize_$role", array( 'menu' => $menu ) ) ) {

				wp_send_json( array(
					'status' => 'success',
				) );
			} else {

				wp_send_json( array(
					'status'    => 'fail',
					'error_msg' => 'Could not save to database',
				) );
			}
		}
	}

	/**
	 * Reset the interface for the current role.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function ajax_reset_interface() {

		if ( ! isset( $_REQUEST['role'] ) ) {

			wp_send_json( array(
				'status'    => 'fail',
				'error_msg' => 'Could not get role',
			) );
		}

		if ( ! check_ajax_referer( 'ac-nonce', 'ac_nonce' ) ) {

			wp_send_json( array(
				'status'    => 'fail',
				'error_msg' => 'Could not verify security',
			) );
		}

		$role = $_REQUEST['role'];

		$old_value = get_option( "ac_customize_$role" );

		if ( $old_value ) {

			if ( delete_option( "ac_customize_$role" ) ) {

				wp_send_json( array(
					'status' => 'success',
				) );
			} else {

				wp_send_json( array(
					'status'    => 'fail',
					'error_msg' => 'Could not delete from database',
				) );
			}
		} else {

			wp_send_json( array(
				'status'    => 'fail',
				'error_msg' => 'Option does not exist.',
			) );
		}
	}
}