<?php
/**
 * Smart Routing tab.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relaymint_routing    = Relaymint_Options::get( 'routing' );
$relaymint_additional = Relaymint_Connections::additional();
$relaymint_fields     = Relaymint_Router::fields();
$relaymint_operators  = Relaymint_Router::operators();

/**
 * Render a condition row.
 *
 * @param array $condition Condition.
 */
$relaymint_condition = static function ( array $condition ) use ( $relaymint_fields, $relaymint_operators ) {
	?>
	<div class="relaymint-condition">
		<select data-name="field" aria-label="<?php esc_attr_e( 'Field', 'relaymint' ); ?>">
			<?php foreach ( $relaymint_fields as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $condition['field'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<select data-name="operator" aria-label="<?php esc_attr_e( 'Operator', 'relaymint' ); ?>">
			<?php foreach ( $relaymint_operators as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $condition['operator'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="text" data-name="value" class="regular-text" value="<?php echo esc_attr( $condition['value'] ); ?>" aria-label="<?php esc_attr_e( 'Value', 'relaymint' ); ?>" />
		<button type="button" class="button-link relaymint-remove-condition" aria-label="<?php esc_attr_e( 'Remove condition', 'relaymint' ); ?>"><span class="dashicons dashicons-no-alt"></span></button>
	</div>
	<?php
};

/**
 * Render a condition group.
 *
 * @param array $group Conditions.
 */
$relaymint_group = static function ( array $group ) use ( $relaymint_condition ) {
	?>
	<div class="relaymint-group">
		<div class="relaymint-conditions">
			<?php
			foreach ( $group as $condition ) {
				$relaymint_condition( $condition );
			}
			?>
		</div>
		<button type="button" class="button button-small relaymint-add-condition"><?php esc_html_e( '+ And', 'relaymint' ); ?></button>
		<span class="relaymint-or-label"><?php esc_html_e( 'or', 'relaymint' ); ?></span>
	</div>
	<?php
};

/**
 * Render a route.
 *
 * @param array $route Route.
 */
$relaymint_route = static function ( array $route ) use ( $relaymint_group, $relaymint_additional ) {
	?>
	<div class="relaymint-route">
		<div class="relaymint-route-header">
			<label class="relaymint-route-enabled"><input type="checkbox" data-name="enabled" value="1" <?php checked( ! empty( $route['enabled'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'relaymint' ); ?></label>
			<label>
				<?php esc_html_e( 'Send via', 'relaymint' ); ?>
				<select data-name="connection">
					<?php foreach ( $relaymint_additional as $id => $connection ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $route['connection'], $id ); ?>><?php echo esc_html( $connection['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<span class="relaymint-route-actions">
				<button type="button" class="button-link relaymint-move-up" aria-label="<?php esc_attr_e( 'Move up', 'relaymint' ); ?>"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
				<button type="button" class="button-link relaymint-move-down" aria-label="<?php esc_attr_e( 'Move down', 'relaymint' ); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
				<button type="button" class="button-link relaymint-remove-route relaymint-delete"><?php esc_html_e( 'Remove route', 'relaymint' ); ?></button>
			</span>
		</div>
		<p class="relaymint-route-if"><?php esc_html_e( 'if the following conditions match:', 'relaymint' ); ?></p>
		<div class="relaymint-groups">
			<?php
			foreach ( $route['groups'] as $group ) {
				$relaymint_group( $group );
			}
			?>
		</div>
		<button type="button" class="button relaymint-add-group"><?php esc_html_e( '+ Add condition group (or)', 'relaymint' ); ?></button>
	</div>
	<?php
};

$relaymint_empty_condition = array(
	'field'    => 'subject',
	'operator' => 'contains',
	'value'    => '',
);
?>
<p><?php esc_html_e( 'Send emails through different connections based on conditions. Routes are checked from top to bottom; the first matching route wins. Emails that match no route use the primary connection.', 'relaymint' ); ?></p>

<?php if ( ! $relaymint_additional ) : ?>
	<div class="notice notice-info inline">
		<p>
			<?php esc_html_e( 'Smart Routing needs at least one additional connection.', 'relaymint' ); ?>
			<a href="<?php echo esc_url( Relaymint_Admin::connection_url( 'new' ) ); ?>"><?php esc_html_e( 'Add a connection', 'relaymint' ); ?></a>
		</p>
	</div>
	<?php
	return;
endif;
?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="relaymint-routing-form">
	<input type="hidden" name="action" value="relaymint_save" />
	<input type="hidden" name="tab" value="routing" />
	<?php wp_nonce_field( 'relaymint_save' ); ?>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Smart Routing', 'relaymint' ); ?></th>
			<td><label><input type="checkbox" name="routing_enabled" value="1" <?php checked( $relaymint_routing['enabled'] ); ?> /> <?php esc_html_e( 'Enable Smart Routing', 'relaymint' ); ?></label></td>
		</tr>
	</table>

	<div class="relaymint-routes">
		<?php
		foreach ( $relaymint_routing['routes'] as $relaymint_r ) {
			$relaymint_route( $relaymint_r );
		}
		?>
	</div>

	<p><button type="button" class="button relaymint-add-route"><?php esc_html_e( '+ Add route', 'relaymint' ); ?></button></p>

	<?php submit_button(); ?>
</form>

<template id="relaymint-tpl-route">
	<?php
	$relaymint_route(
		array(
			'enabled'    => true,
			'connection' => '',
			'groups'     => array( array( $relaymint_empty_condition ) ),
		)
	);
	?>
</template>
<template id="relaymint-tpl-group">
	<?php $relaymint_group( array( $relaymint_empty_condition ) ); ?>
</template>
<template id="relaymint-tpl-condition">
	<?php $relaymint_condition( $relaymint_empty_condition ); ?>
</template>
