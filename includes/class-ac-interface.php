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

		if ( isset( $_REQUEST['ac_customize'] ) ) {

			add_action( 'admin_init', array( $this, 'get_adminmenu' ) );
			add_action( 'admin_init', array( $this, 'setup_data' ), 100 );
			add_filter( 'admin_body_class', array( $this, 'body_class' ) );
			add_action( 'admin_footer', array( $this, 'interface_toolbar_HTML' ) );

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
		AC()->add_script_data( 'custom_menu', $this->customize_adminmenu->custom_menu );
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
		foreach ( $menu as $menu_position => $menu_item ) {

			$ac_submenu = false;
			if ( isset( $submenu[ $menu_item[2] ] ) ) {

				$ac_submenu = array();

				foreach ( $submenu[ $menu_item[2] ] as $submenu_position => $submenu_item ) {
					$ac_submenu[] = array(
						'position'      => $submenu_position,
//						'submenu_title' => isset( $submenu_item[0] ) ? $submenu_item[0] : false,
//						'capability'    => isset( $submenu_item[1] ) ? $submenu_item[1] : false,
						'slug'          => isset( $submenu_item[2] ) ? $submenu_item[2] : false,
						'remove'        => false,
					);
				}
			}

			$ac_menu[] = array(
				'position'   => $menu_position,
				'submenu'    => $ac_submenu,
//				'menu_title' => isset( $menu_item[0] ) ? $menu_item[0] : false,
//				'capability' => isset( $menu_item[1] ) ? $menu_item[1] : false,
				'slug'       => isset( $menu_item[2] ) ? $menu_item[2] : false,
//				'page_title' => isset( $menu_item[3] ) ? $menu_item[3] : false,
//				'classes'    => isset( $menu_item[4] ) ? $menu_item[4] : false,
//				'hook_name'  => isset( $menu_item[5] ) ? $menu_item[5] : false,
//				'icon'       => isset( $menu_item[6] ) ? $menu_item[6] : false,
				'remove'     => false,
			);
		}

		AC()->add_script_data( 'current_menu', $ac_menu );
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
}