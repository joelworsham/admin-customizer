<?php
/**
 * The interface widgets toolbar.
 *
 * @since 0.1.0
 * @var WP_Screen $screen
 * @var AC_Interface $this
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes/views
 */

defined( 'ABSPATH' ) || die();
?>

<div id="ac-interface-widgets-toolbar" class="metabox-holder columns-2">
	<div class="ac-interface-widgets-trash-container">
		<h2 class="ac-interface-widgets-title">
			<span class="dashicons dashicons-trash"></span>
			Trash
		</h2>

		<div class="ac-interface-widgets-trash">

		</div>
	</div>

	<div class="ac-interface-widgets-add-new ">
		<h2 class="ac-interface-widgets-title">
			<span class="dashicons dashicons-plus"></span>
			Add New
		</h2>

		<div class="ac-interface-widgets-new meta-box-sortables">
			<?php $this->show_ac_widgets(); ?>
		</div>
	</div>

	<?php // No closing </div> because the hook we're in (welcome_panel) will add that.