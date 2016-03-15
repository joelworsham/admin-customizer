<?php
/**
 * Customizes the Dashboard Widgets.
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes/customize
 */

defined( 'ABSPATH' ) || die();

/**
 * Class AC_Customize_DashWidgets
 *
 * Customizes the Dashboard Widgets.
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes/customize
 */
class AC_Customize_DashWidgets {

	/**
	 * Widgets that have been added.
	 *
	 * @since 0.1.0
	 *
	 * @var array|bool
	 */
	public $dash_widgets = array();

	/**
	 * AC_Customize_DashWidgets constructor.
	 *
	 * @since 0.1.0
	 */
	function __construct() {

		add_action( 'wp_dashboard_setup', array( $this, 'add_dash_widgets' ) );

		if ( ! isset( $_REQUEST['ac_customize'] ) ) {
			add_action( 'wp_dashboard_setup', array( $this, 'remove_dash_widgets' ), 1000 );
		}
	}

	/**
	 * Adds any new widgets (from the interface) to the dashboard.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function add_dash_widgets() {

		global $wp_meta_boxes;

		$dash_widgets = $this->dash_widgets;

		// If any new widgets are already set, this means they aren't AC widgets and only have the title modified. So
		// we need to change the title and then unset the new widget.
		if ( $dash_widgets ) {
			foreach ( $wp_meta_boxes['dashboard'] as &$priorities ) {
				foreach ( $priorities as &$widgets ) {
					foreach ( $widgets as $widget_ID => &$widget ) {
						if ( isset( $dash_widgets[ $widget_ID ] ) ) {

							if ( isset( $dash_widgets[ $widget_ID ]['title'] ) ) {
								$widget['title'] = $dash_widgets[ $widget_ID ]['title'];
							}

							unset( $dash_widgets[ $widget_ID ] );
						}
					}
				}
			}

			unset( $priorities, $widgets, $widget );
		}

		// Now we continue on and add any remaining new widgets.
		if ( $dash_widgets ) {
			foreach ( $dash_widgets as $widget_ID => $widget ) {

				// Make sure this is indeed an AC widget
				if ( ! isset( $widget['ac_id'] ) ) {
					unset( $dash_widgets[ $widget_ID ] );
					continue;
				}

				// Make sure the saved widget exists
				if ( ! isset( AC_Interface::$widgets[ $widget['ac_id'] ] ) ) {
					continue;
				}

				call_user_func( array(
					AC_Interface::$widgets[ $widget['ac_id'] ],
					'add_to_dashboard'
				), $widget );
			}
		}
	}

	/**
	 * Removes any disabled (from the interface) widgets from the dashboard.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function remove_dash_widgets() {

		global $wp_meta_boxes;

		if ( $this->dash_widgets ) {
			foreach ( $wp_meta_boxes['dashboard'] as $context => $priorities ) {
				foreach ( $priorities as $widgets ) {
					foreach ( $widgets as $widget_ID => $widget ) {
						if ( isset( $this->dash_widgets[ $widget_ID ] ) ) {

							// Trashed
							if ( isset( $this->dash_widgets[ $widget_ID ]['trashed'] ) &&
							     $this->dash_widgets[ $widget_ID ]['trashed']
							) {
								remove_meta_box( $widget_ID, 'dashboard', $context );
							}
						}
					}
				}
			}
		}
	}
}