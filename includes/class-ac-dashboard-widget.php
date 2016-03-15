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
	protected $ID;

	/**
	 * The widget name.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * All added widget instances.
	 *
	 * @since 0.1.0
	 *
	 * @var array
	 */
	public static $instances = array();

	/**
	 * The current widget instance.
	 *
	 * @since 0.1.0
	 *
	 * @var bool|array
	 */
	public $current_instance = false;

	/**
	 * AC_Dashboard_Widget constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $ID The ID of the widget.
	 * @param string $name The default name of the widget.
	 */
	function __construct( $ID, $name ) {

		$this->ID   = $ID;
		$this->name = $name;

		AC_Interface::$widgets[ $ID ] = $this;

		if ( isset( $_REQUEST['ac_customize'] ) ) {

			// Add to interface
			add_action( 'wp_dashboard_setup', array( $this, 'add_to_ac_new_dashboard' ), 999 );

			// Localize widget instances for use in the Interface
			add_action( 'wp_dashboard_setup', array( $this, 'localize_data' ), 1000 );
		}
	}

	/**
	 * Adds the widget to the AC New dashboar area
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function add_to_ac_new_dashboard() {

		add_meta_box(
			"ac-widget-{$this->ID}",
			$this->name,
			array( $this, '_output' ),
			'dashboard',
			'ac_new',
			'default',
			array(
				'widget' => $this
			)
		);
	}

	/**
	 * Localize widget instances.
	 *
	 * @since 0.1.0
	 * @access private
	 */
	function localize_data() {

		AC()->add_script_data( 'widget_instances', self::$instances );
	}

	/**
	 * Adds the widget to the dashboard.
	 *
	 * @since 0.1.0
	 * @access private
	 *
	 * @param array $instance The current widget instance args.
	 */
	function add_to_dashboard( $instance ) {

		if ( ! isset( self::$instances[ $this->ID ] ) ) {
			self::$instances[ $this->ID ] = array();
		}

		self::$instances[ $this->ID ][] = $instance;

		$index = count( self::$instances[ $this->ID ] ) - 1;

		wp_add_dashboard_widget(
			"ac-widget-{$this->ID}_{$index}",
			! empty( $instance['title'] ) ? $instance['title'] : $this->name,
			array( $this, 'call_output' ),
			null,
			array(
				'instance' => $instance,
			)
		);
	}

	/**
	 * Calls the widget output.
	 *
	 * @since 0.1.0
	 * @access private
	 *
	 * @param mixed $object In this case (dashboard, always an empty string.
	 * @param array $box Various properties about the meta box and current instance.
	 */
	function call_output( $object, $box ) {

		$this->_output( $box['args']['instance'] );
	}

	/**
	 * Calls the widget form callback.
	 *
	 * @since 0.1.0
	 * @access private
	 *
	 * @param array $instance The current widget instance.
	 */
	function _form( $instance = array() ) {

		$this->current_instance = $instance;
		?>
		<form class="ac-widget-form">
			<input type="hidden" name="ac_id" value="<?php echo $this->ID; ?>"/>

			<div class="ac-widget-form-custom">
				<?php $this->form( $instance ); ?>
			</div>

		</form>
		<?php
		$this->current_instance = false;
	}

	/**
	 * Calls the widget output callback.
	 *
	 * @since 0.1.0
	 * @access private
	 *
	 * @param array $instance The current widget instance.
	 */
	function _output( $instance = array() ) {

		$this->current_instance = $instance;
		$this->output( $instance );
		$this->current_instance = false;
	}

	/**
	 * The default form output.
	 *
	 * @since 0.1.0
	 *
	 * @param array $instance The current widget instance.
	 */
	public function form( $instance ) {
		echo __( 'This widget has no options.', 'AC' );
	}

	/**
	 * The default widget output.
	 *
	 * @since 0.1.0
	 *
	 * @param array|bool $instance
	 */
	public function output( $instance ) {
	}

	public function get_field_id( $name ) {

		if ( $this->current_instance ) {
			return "{$this->current_instance['id']}_$name";
		} else {
			return "{$this->ID}_$name";
		}
	}

	public function get_field_value( $name ) {

		if ( $this->current_instance && isset( $this->current_instance['args'][ $name ] ) ) {
			return $this->current_instance['args'][ $name ];
		} else {
			return false;
		}
	}
}