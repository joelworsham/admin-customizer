<?php
/*
Plugin Name: Admin Customizer Sandbox
Description: Allows customization of some admin features (sandbox).
Version: 0.1.0
Author: Joel Worsham
*/

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'AC' ) ) {

	/**
	 * Class Admin_Customizer
	 *
	 * Initiates the plugin.
	 *
	 * @since 0.1.0
	 *
	 * @package AdminCustomizer
	 */
	final class AC {

		/**
		 * The admin module.
		 *
		 * @since 0.1.0
		 *
		 * @var AC_Admin
		 */
		private $admin;

		/**
		 * The Interface module.
		 *
		 * The Interface is the "Customizer" for the backend of WP.
		 *
		 * @since 0.1.0
		 *
		 * @var AC_Admin
		 */
		private $interface;

		/**
		 * All data to be localized to the primary script.
		 *
		 * @since 0.1.0
		 *
		 * @var array
		 */
		private $script_data = array();

		/**
		 * Disable cloning.
		 *
		 * @since 0.1.0
		 */
		protected function __clone() {
		}

		/**
		 * Call this method to get singleton
		 *
		 * @since 0.1.0
		 *
		 * @return AC()
		 */
		public static function instance() {

			static $instance = null;

			if ( $instance === null ) {
				$instance = new AC();
			}

			return $instance;
		}

		/**
		 * AC constructor.
		 *
		 * @since 0.1.0
		 */
		private function __construct() {

			// This plugin only loads on the administrative side of WordPress
			if ( ! is_admin() ) {
				return;
			}

			$this->set_constants();
			$this->require_necessities();

			add_action( 'admin_init', array( $this, 'register_assets' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			$this->admin     = new AC_Admin();
			$this->interface = new AC_Interface();
		}

		/**
		 * Setup constants.
		 *
		 * @since 0.1.0
		 */
		private function set_constants() {

			define( 'ADMINCUSTOMIZER_VER', '0.1.0' );
			define( 'ADMINCUSTOMIZER_DIR', plugin_dir_path( __FILE__ ) );
			define( 'ADMINCUSTOMIZER_URI', plugins_url( '', __FILE__ ) );
		}

		/**
		 * Requires all plugin files.
		 *
		 * @since 0.1.0
		 */
		private function require_necessities() {

			require_once ADMINCUSTOMIZER_DIR . '/includes/class-ac-admin.php';
			require_once ADMINCUSTOMIZER_DIR . '/includes/class-ac-interface.php';
			require_once ADMINCUSTOMIZER_DIR . '/includes/class-ac-dashboard-widget.php';
			require_once ADMINCUSTOMIZER_DIR . '/includes/widgets/class-ac-widget-text.php';
			require_once ADMINCUSTOMIZER_DIR . '/includes/customize/class-ac-customize-adminmenu.php';
			require_once ADMINCUSTOMIZER_DIR . '/includes/customize/class-ac-customize-dashwidgets.php';
		}

		/**
		 * Registers all plugin assets.
		 *
		 * @since 0.1.0
		 * @access private
		 */
		function register_assets() {

			wp_register_script(
				'ac',
				ADMINCUSTOMIZER_URI . '/assets/dist/js/admin-customizer.min.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : ADMINCUSTOMIZER_VER,
				true
			);

			wp_register_style(
				'ac',
				ADMINCUSTOMIZER_URI . '/assets/dist/css/admin-customizer.min.css',
				array(),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : ADMINCUSTOMIZER_VER
			);
		}

		/**
		 * Enqueues all plugin assets.
		 *
		 * @since 0.1.0
		 * @access private
		 */
		function enqueue_assets() {

			/**
			 * Filter all localization data.
			 *
			 * @since 0.1.0
			 */
			$data = apply_filters( 'ac_script_data', $this->script_data );
			wp_localize_script( 'ac', 'AC', $data );

			wp_enqueue_script( 'ac' );
			wp_enqueue_style( 'ac' );
		}

		/**
		 * Adds a piece of data to the script data to be localized.
		 *
		 * @since 0.1.0
		 *
		 * @param string $key
		 * @param mixed $value
		 */
		public function add_script_data( $key, $value = '1' ) {

			/**
			 * Filter the localization data piece.
			 *
			 * @since 0.1.0
			 */
			$data = apply_filters( 'ac_add_script_data', array( $key, $value ) );

			if ( ! isset( $this->script_data[ $data[0] ] ) ) {
				$this->script_data[ $data[0] ] = $data[1];
			}
		}
	}

	// Primary instantiation
	require_once __DIR__ . '/includes/ac-functions.php';
	$GLOBALS['AC'] = AC();
}