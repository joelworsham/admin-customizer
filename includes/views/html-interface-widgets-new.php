<?php
/**
 * The new widgets section of the interface.
 *
 * @since 0.1.0
 *
 * @var array $widget
 * @var AC_Dashboard_Widget $widget_object
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes/views
 */

defined( 'ABSPATH' ) || die();
?>
<div id="<?php echo $widget['id']; ?>"
     class="ac-interface-widget postbox closed">

	<button type="button" class="handlediv button-link" aria-expanded="true">
		<span class="screen-reader-text">
			Toggle panel: <?php echo $widget['title']; ?>
		</span>
		<span class="toggle-indicator" aria-hidden="true"></span>
	</button>

	<h2 class="ac-widget-title hndle">
		<span class="ac-widget-title-text"><?php echo $widget['title']; ?></span>
		<input type="text" name="ac_widget_title" class="ac-widget-title-input"
		       placeholder="Title" aria-label="Title"/>
	</h2>

	<div class="inside">
		<form name="post" class="ac-widget-form">
			<?php $widget_object->_form(); ?>
		</form>

		<div class="ac-widget-inside">
			<?php $widget_object->_output(); ?>
		</div>
	</div>
</div>