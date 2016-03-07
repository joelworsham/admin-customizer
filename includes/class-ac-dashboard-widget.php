<?php
/**
 * An AC Dashboard Widget.
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes
 */

defined( 'ABSPATH' ) || die();

/**
 * Class AC_Dashboard_Widget
 *
 * All custom dashboard widgets are built off this.
 *
 * @since 0.1.0
 */
class AC_Dashboard_Widget {

	/**
	 * The widget ID.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private $ID;

	/**
	 * The widget instance.
	 *
	 * @since 0.1.0
	 *
	 * @var int
	 */
	public $instance;

	/**
	 * The widget name.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private $name;

	/**
	 * AC_Dashboard_Widget constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $ID
	 * @param string $name
	 */
	function __construct( $ID, $name ) {

		$this->ID   = $ID;
		$this->name = $name;

		// Set instance and add widget to static $widgets
		if ( ! isset( AC_Interface::$widgets[ $ID ] ) ) {
			AC_Interface::$widgets[ $ID ] = array( $this->instance = 0 => $this );
		} else {
			AC_Interface::$widgets[ $ID ][ $this->instance = count( AC_Interface::$widgets[ $ID ] ) ] = $this;
		}

		add_action( 'wp_dashboard_setup', array( $this, 'add_to_dashboard' ) );
	}

	/**
	 * Adds the widget to the dashboard metaboxes.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function add_to_dashboard() {

		add_meta_box(
			$this->ID,
			$this->name,
			array( $this, '_output' ),
			'dashboard',
			'ac_new',
			'default',
			array(
				'widget' => $this,
			)
		);
	}

	/**
	 * Calls the widget form callback.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function _form() {

		?>
		<form class="ac-widget-form">
			<input type="hidden" name="widget_name" value="<?php echo $this->name; ?>"/>
			<input type="hidden" name="widget_id" value="<?php echo $this->ID; ?>"/>
			<input type="hidden" name="widget_instance" value="<?php echo $this->instance; ?>"/>

			<div class="ac-widget-form-custom">
				<?php $this->form(); ?>
			</div>

		</form>
		<?php
	}

	/**
	 * Calls the widget output callback.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function _output() {

		$instance = array( 'test' );
		$this->output( $instance );
	}

	/**
	 * The default form output.
	 *
	 * @since 0.1.0
	 */
	public function form() {
		echo __( 'This widget has no options.', 'AC' );
	}

	/**
	 * The default widget output.
	 *
	 * @since 0.1.0
	 */
	public function output( $instance ) {
	}

	/**
	 * Gets a field ID for the widget.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name The field name (no dashes or spaces).
	 *
	 * @return string The full field name.
	 */
	public function get_field_id( $name ) {
		return "{$this->ID}_{$this->instance}_$name";
	}
}