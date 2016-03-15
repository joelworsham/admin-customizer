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
	 * The Customize Dash Widgets module.
	 *
	 * @since 0.1.0
	 *
	 * @var AC_Customize_DashWidgets
	 */
	private $customize_dashwidgets;

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
	 * All widgets created (for getting instances).
	 *
	 * @since 0.1.0
	 *
	 * @var int
	 */
	public static $widgets;

	/**
	 * AC_Interface constructor.
	 *
	 * @since 0.1.0
	 */
	function __construct() {

		$this->customize_adminmenu   = new AC_Customize_AdminMenu();
		$this->customize_dashwidgets = new AC_Customize_DashWidgets();

		$this->default_widgets();

		add_action( 'init', array( $this, 'setup_role' ), 1 );
		add_action( 'init', array( $this, 'get_customizations' ) );
		add_action( 'wp_ajax_ac-save-interface', array( $this, 'ajax_save_interface' ) );
		add_action( 'wp_ajax_ac-reset-interface', array( $this, 'ajax_reset_interface' ) );
		add_action( 'wp_ajax_ac-get-widget-html', array( $this, 'ajax_get_widget_HTML' ) );

		if ( isset( $_REQUEST['ac_customize'] ) ) {

			add_action( 'admin_notices', array( $this, 'interface_admin_notices' ) );
			add_action( 'admin_head', array( $this, 'manage_help_tabs' ) );
			add_filter( 'screen_options_show_screen', '__return_false' );
			add_action( 'admin_init', array( $this, 'get_adminmenu' ) );
			add_action( 'admin_init', array( $this, 'sync_custom_menu' ), 50 );
			add_action( 'wp_dashboard_setup', array( $this, 'get_dash_widgets' ), 998 ); // Before interface widgets
			add_action( 'admin_enqueue_scripts', array( $this, 'setup_data' ), 9 ); // Right before they're enqueued
			add_action( 'admin_init', array( $this, 'translations' ) );
			add_filter( 'admin_body_class', array( $this, 'body_class' ) );
			add_action( 'adminmenu', array( $this, 'interface_adminmenu_trash_HTML' ) );
			add_action( 'admin_footer', array( $this, 'interface_widgets_toolbar_HTML' ), 1000 );
			add_action( 'admin_footer', array( $this, 'add_edit_HTML' ) );
			add_action( 'admin_footer', array( $this, 'interface_toolbar_HTML' ) );

			if ( isset( $_REQUEST['ac_current_role'] ) ) {
				add_filter( 'user_has_cap', array( $this, 'modify_role_capabilities' ), 10, 4 );
			}
		} else {

			add_action( 'adminmenu', array( $this, 'launch_interface_HTML' ) );
		}
	}

	/**
	 * Adds default AC widgets.
	 *
	 * @since 0.1.0
	 */
	private function default_widgets() {

		new AC_Widget_Text();
	}

	/**
	 * Add interface translations to JS.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function translations() {

		AC()->add_script_data( 'interfaceL10n', array(
			'adminmenuTrashEmpty'      => __( 'Drag items here', 'AC' ),
			'widgetsTrashPostfixAC'    => __( '(pending deletion)', 'AC' ),
			'widgetsTrashPostfixWP'    => __( '(disabled)', 'AC' ),
			'unsavedChangesChangeRole' => __( 'You have unsaved changes. Are you sure you want to edit another role?', 'AC' ),
			'unsavedChangesExit'       => __( 'You have unsaved changes. Are you sure you want to exit?', 'AC' ),
		) );
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
	 * Outputs the interface widget edit buttons.
	 *
	 * @since 0.1.0
	 */
	function add_edit_HTML() {

		include_once __DIR__ . '/views/html-interface-widget-edit-actions.php';
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
	 * Outputs the interface widgets toolbar HTML.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function interface_widgets_toolbar_HTML() {
		include_once __DIR__ . '/views/html-interface-widgets-toolbar.php';
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

		// Also enqueue jquery ui effects
		wp_enqueue_script( 'jquery-effects-shake' );

		AC()->add_script_data( 'launch_interface' );
		AC()->add_script_data( 'current_role', $this->current_role );
		AC()->add_script_data( 'nonce', wp_create_nonce( 'ac-nonce' ) );

		if ( $this->customize_adminmenu->custom_menu ) {
			AC()->add_script_data( 'current_menu', $this->customize_adminmenu->custom_menu );
		} else {
			AC()->add_script_data( 'current_menu', $this->customize_adminmenu->current_menu );
		}

		AC()->add_script_data( 'dash_widgets', $this->customize_dashwidgets->dash_widgets );
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
		$dash_widgets        = array();

		if ( $customizations = get_option( "ac_customize_$this->current_role" ) ) {

			if ( isset( $customizations['menu'] ) && ! empty( $customizations['menu'] ) ) {
				$menu_customizations = $customizations['menu'];
			}

			if ( isset( $customizations['widgets'] ) && ! empty( $customizations['widgets'] ) ) {
				$dash_widgets = $customizations['widgets'];
			}
		}

		/**
		 * Allows filtering the custom menu.
		 *
		 * @since 0.1.0
		 */
		$this->customize_adminmenu->custom_menu = apply_filters( 'ac_custom_menu', $menu_customizations );

		/**
		 * Allows filtering the new widgets.
		 *
		 * @since 0.1.0
		 */
		$this->customize_dashwidgets->dash_widgets = apply_filters( 'ac_dash_widgets', $dash_widgets );
	}

	/**
	 * Adds admin notices for the Interface.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function interface_admin_notices() {

		// TODO Add button to PERMANENTLY hide
		?>
		<div id="ac-widgets-move-adminnotice" class="notice notice-warning is-dismissible">
			<p>
				<?php _e( '<strong>Notice:</strong> Meta-box positions will not be saved when using the Interface. Meta-box positions are customizable for each user.', 'AC' ); ?>
			</p>
			<p>
				<?php _e( 'If you would like to edit <strong>your</strong> meta-box positions, please exit the interface first.', 'AC' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Removes the default help tabs and populates it with Interface instructions.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function manage_help_tabs() {

		$screen = get_current_screen();

		// Remove default tabs
		$screen->remove_help_tab( 'overview' );
		$screen->remove_help_tab( 'help-navigation' );
		$screen->remove_help_tab( 'help-layout' );
		$screen->remove_help_tab( 'help-content' );

		// Remove sidebar
		$screen->set_help_sidebar( false );

		// Add new tabs
		// TODO Help Tabs
		$screen->add_help_tab( array(
			'id'      => 'ac-help-adminmenu',
			'title'   => __( 'Admin Menu' ),
			'content' => 'Test',
		) );
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
	 * Gets dashboard widgets for use in the interface.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function get_dash_widgets() {

		global $wp_meta_boxes;

		$custom_dashwidgets = $this->customize_dashwidgets->dash_widgets;
		$dash_widgets       = array();
		if ( isset( $wp_meta_boxes['dashboard'] ) && ! empty( $wp_meta_boxes['dashboard'] ) ) {
			foreach ( $wp_meta_boxes['dashboard'] as &$priorities ) {
				foreach ( $priorities as &$widgets ) {
					foreach ( $widgets as &$widget ) {

						$dash_widgets[ $widget['id'] ] = array_intersect_key( $widget, array_flip( array(
							'id',
							'title',
						) ) );

						// Customized dash widget
						if ( isset( $custom_dashwidgets[ $widget['id'] ] ) ) {
							$dash_widgets[ $widget['id'] ] = array_merge(
								$dash_widgets[ $widget['id'] ],
								$custom_dashwidgets[ $widget['id'] ]
							);
						}
					}
				}
			}
			unset( $priorities, $widgets, $widget );
		}

		$this->customize_dashwidgets->dash_widgets = apply_filters( 'ac_dash_widgets', $dash_widgets );
	}

	/**
	 * Saves the supplied menu via AJAX.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function ajax_save_interface() {

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

		$role    = $_REQUEST['role'];
		$menu    = isset( $_REQUEST['menu'] ) ? (array) $_REQUEST['menu'] : false;
		$widgets = isset( $_REQUEST['widgets'] ) ? (array) $_REQUEST['widgets'] : false;

		// Admin Menu
		if ( $menu ) {

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
		}

		// Widgets
		$widgets = ac_string_to_bool( $widgets );
		$widgets = wp_unslash( $widgets );

		// Remove permanently trashed AC widgets
		foreach ( $widgets as $widget_ID => $widget ) {
			if ( isset( $widget['ac_id'] ) && isset( $widget['trashed'] ) && $widget['trashed'] ) {
				unset( $widgets[ $widget_ID ] );
			}
		}

		$old_value = get_option( "ac_customize_$role" );
		$new_value = array(
			'menu'    => $menu,
			'widgets' => $widgets,
		);

		if ( $old_value === $new_value ) {

			wp_send_json( array(
				'status'    => 'fail',
				'error_msg' => 'Option already saved.',
			) );
		} else {

			if ( update_option( "ac_customize_$role", $new_value ) ) {

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

	/**
	 * Get a widget's HTML.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function ajax_get_widget_HTML() {

		if ( ! isset( $_REQUEST['widget'] ) ) {

			wp_send_json( array(
				'status'    => 'fail',
				'error_msg' => 'Could not get widget',
			) );
		}

		check_ajax_referer( 'ac-nonce', 'ac_nonce' );

		$widget = $_REQUEST['widget'];
		$output = $_REQUEST['output'];

		// Get widget properties
		$ID = $widget['ac_id'];
		unset( $widget['ac_id'] );

		switch ( $output ) {
			case 'widget':

				ob_start();
				self::$widgets[ $ID ]->_output( $widget );
				$output = ob_get_clean();
				break;

			case 'form':

				ob_start();
				self::$widgets[ $ID ]->_form( $widget );
				$output = ob_get_clean();
				break;

			default:
				wp_send_json( array(
					'status'    => 'fail',
					'error_msg' => 'No output type or incorrect output type specified.',
				) );
				break;
		}

		wp_send_json( array(
				'status' => 'success',
				'output' => $output,
			)
		);
	}

	/**
	 * Shows AC widgets.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	private function show_ac_widgets() {

		global $wp_meta_boxes;

		// TODO Better message
		if ( empty( $wp_meta_boxes['dashboard']['ac_new']['default'] ) ) {
			_e( 'No Widgets', 'AC' );
		}

		// Store and unset so they don't get printed out
		$ac_meta_boxes = $wp_meta_boxes['dashboard']['ac_new']['default'];
		unset( $wp_meta_boxes['dashboard']['ac_new'] );

		foreach ( $ac_meta_boxes as $widget ) {

			/** @var AC_Dashboard_Widget $widget_object */
			$widget_object = $widget['args']['widget'];

			include __DIR__ . '/views/html-interface-widgets-new.php';
		}
	}
}