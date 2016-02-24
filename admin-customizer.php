<?php
/*
Plugin Name: Admin Customizer Sandbox
Description: Allows customization of some admin features (sandbox).
Version: 0.1.0
Author: Joel Worsham
*/

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'AdminCustomizer' ) ) {

	/**
	 * Class Admin_Customizer
	 *
	 * Initiates the plugin.
	 *
	 * @since 0.1.0
	 *
	 * @package AdminCustomizer
	 */
	final class AdminCustomizer {

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
		 * @return AdminCustomizer()
		 */
		public static function instance() {

			static $instance = null;

			if ( $instance === null ) {
				$instance = new AdminCustomizer();
			}

			return $instance;
		}

		/**
		 * AdminCustomizer constructor.
		 *
		 * @since 0.1.0
		 */
		private function __construct() {

			$this->set_constants();
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
	}

	$GLOBALS['AdminCustomizer'] = AdminCustomizer::instance();
}