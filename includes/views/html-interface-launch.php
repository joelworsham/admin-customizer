<?php
/**
 * The interface launch button.
 *
 * @since 0.1.0
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes/views
 */

defined( 'ABSPATH' ) || die();
?>

<li id="ac-interface-launch">
	<a href="<?php echo add_query_arg( 'ac_customize', '1', admin_url( 'index.php' ) ); ?>" class="button">Customize</a>
</li>