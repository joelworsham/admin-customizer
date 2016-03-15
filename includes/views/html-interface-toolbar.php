<?php
/**
 * The interface toolbar view.
 *
 * @since 0.1.0
 *
 * @var array $roles
 * @var string $current_user_role
 *
 * @package AdminCustomizer
 * @subpackage AdminCustomizer/includes/views
 */

defined( 'ABSPATH' ) || die();
?>

<div id="ac-interface-toolbar">
	<div data-ac-interface-save class="button">Save</div>
	<div data-ac-interface-reset class="button">Reset</div>
	<a href="<?php echo remove_query_arg( array( 'ac_customize', 'ac_current_role' ) ); ?>" class="button"
	   data-ac-interface-exit>Exit</a>

	<?php if ( $roles ) : ?>
		<label class="screen-reader-text" for="ac-interface-select-role">Select Role</label>
		<select id="ac-interface-select-role" name="ac-interface-select-role" data-ac-interface-select-role>
			<?php foreach ( $roles as $role_ID => $role ) : ?>
				<option value="<?php echo $role_ID; ?>" <?php selected( $current_user_role, $role_ID ); ?>>
					<?php echo $role['name']; ?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php endif; ?>
</div>

<div id="ac-interface-cover"><span class="spinner"></span></div>