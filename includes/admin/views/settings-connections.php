<?php
/**
 * Additional connections tab.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
$relaymint_edit       = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : '';
$relaymint_additional = Relaymint_Connections::additional();
$relaymint_is_new     = 'new' === $relaymint_edit;
$relaymint_editing    = $relaymint_is_new || isset( $relaymint_additional[ $relaymint_edit ] );
$relaymint_list_url   = Relaymint_Admin::url( '', array( 'tab' => 'connections' ) );

if ( $relaymint_editing ) :
	$relaymint_id         = $relaymint_is_new ? '' : $relaymint_edit;
	$relaymint_connection = $relaymint_is_new ? Relaymint_Options::connection_defaults() : $relaymint_additional[ $relaymint_edit ];
	?>
	<h2><?php echo $relaymint_is_new ? esc_html__( 'Add Connection', 'relaymint' ) : esc_html__( 'Edit Connection', 'relaymint' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="relaymint-connection-form">
		<input type="hidden" name="action" value="relaymint_save" />
		<input type="hidden" name="tab" value="connections" />
		<input type="hidden" name="connection_id" value="<?php echo esc_attr( $relaymint_id ); ?>" />
		<?php wp_nonce_field( 'relaymint_save' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="relaymint-name"><?php esc_html_e( 'Connection Name', 'relaymint' ); ?></label></th>
				<td><input type="text" id="relaymint-name" class="regular-text" name="connection[name]" value="<?php echo esc_attr( $relaymint_connection['name'] ); ?>" required /></td>
			</tr>
		</table>

		<?php Relaymint_Admin::render_connection_fields( $relaymint_id, $relaymint_connection ); ?>

		<p class="submit">
			<?php submit_button( __( 'Save Connection', 'relaymint' ), 'primary', 'submit', false ); ?>
			<a class="button" href="<?php echo esc_url( $relaymint_list_url ); ?>"><?php esc_html_e( 'Back', 'relaymint' ); ?></a>
		</p>
	</form>
	<?php
	return;
endif;
?>
<p><?php esc_html_e( 'Additional connections can be used by Smart Routing to send specific emails through a different SMTP account.', 'relaymint' ); ?></p>
<p><a class="button button-primary" href="<?php echo esc_url( Relaymint_Admin::connection_url( 'new' ) ); ?>"><?php esc_html_e( 'Add Connection', 'relaymint' ); ?></a></p>

<table class="widefat striped relaymint-connections">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Name', 'relaymint' ); ?></th>
			<th><?php esc_html_e( 'From Email', 'relaymint' ); ?></th>
			<th><?php esc_html_e( 'SMTP Host', 'relaymint' ); ?></th>
			<th><?php esc_html_e( 'Connection ID', 'relaymint' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'relaymint' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( ! $relaymint_additional ) : ?>
			<tr><td colspan="5"><?php esc_html_e( 'No additional connections yet.', 'relaymint' ); ?></td></tr>
		<?php endif; ?>
		<?php foreach ( $relaymint_additional as $relaymint_cid => $relaymint_conn ) : ?>
			<tr>
				<td><strong><a href="<?php echo esc_url( Relaymint_Admin::connection_url( $relaymint_cid ) ); ?>"><?php echo esc_html( $relaymint_conn['name'] ); ?></a></strong></td>
				<td><?php echo esc_html( $relaymint_conn['from_email'] ); ?></td>
				<td><?php echo esc_html( $relaymint_conn['host'] . ':' . $relaymint_conn['port'] ); ?></td>
				<td><code><?php echo esc_html( $relaymint_cid ); ?></code></td>
				<td>
					<a href="<?php echo esc_url( Relaymint_Admin::connection_url( $relaymint_cid ) ); ?>"><?php esc_html_e( 'Edit', 'relaymint' ); ?></a>
					|
					<a class="relaymint-confirm relaymint-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=relaymint_delete_connection&id=' . rawurlencode( $relaymint_cid ) ), 'relaymint_delete_connection_' . $relaymint_cid ) ); ?>"><?php esc_html_e( 'Delete', 'relaymint' ); ?></a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
