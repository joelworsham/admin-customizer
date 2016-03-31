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

$query_args = array(
	'ac_customize' => '1',
	'referrer' => urlencode( $_SERVER['REQUEST_URI'] ),
)
?>

<li id="ac-interface-launch">
	<a href="<?php echo add_query_arg( $query_args, admin_url( 'index.php' ) ); ?>" class="button">Customize</a>
</li>