<?php
/**
 * Default Widget: Text
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes/widgets
 */

defined( 'ABSPATH' ) || die();

/**
 * Class AC_Widget_Text
 *
 * Creates an AC widget, specifically the one for "Text".
 *
 * @since 0.1.0
 */
class AC_Widget_Text extends AC_Dashboard_Widget {

	/**
	 * AC_Widget_Text constructor.
	 *
	 * @since 0.1.0
	 */
	function __construct() {

		parent::__construct(
			'text',
			'Text'
		);
	}

	/**
	 * Outputs the widget.
	 *
	 * @since 0.1.0
	 */
	public function output( $instance ) {

		$text = isset( $instance['text'] ) ? $instance['text'] : false;

		if ( $text ) {
			echo $text;
		}
	}

	/**
	 * Outputs the widget form.
	 *
	 * @since 0.1.0
	 *
	 * @param array $instance The current widget instance.
	 */
	public function form( $instance ) {
		?>
		<label for="<?php echo $this->get_field_id( 'text' ); ?>">
			<?php _e( 'Text / HTML', 'AC' ); ?>
		</label>

		<br/>

		<textarea id="<?php echo $this->get_field_id( 'text' ); ?>" name="text"
		><?php echo $this->get_field_value( 'text' ); ?></textarea>
		<?php
	}
}