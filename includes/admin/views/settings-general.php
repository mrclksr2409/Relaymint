<?php
/**
 * General tab: primary SMTP connection.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relaymint_connections = Relaymint_Connections::all();
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="relaymint-connection-form">
	<input type="hidden" name="action" value="relaymint_save" />
	<input type="hidden" name="tab" value="general" />
	<?php wp_nonce_field( 'relaymint_save' ); ?>

	<p><?php esc_html_e( 'The primary connection is used for all emails unless a Smart Routing rule picks another connection.', 'relaymint' ); ?></p>

	<?php Relaymint_Admin::render_connection_fields( Relaymint_Connections::PRIMARY, $relaymint_connections[ Relaymint_Connections::PRIMARY ] ); ?>

	<?php submit_button(); ?>
</form>
<p>
	<a href="<?php echo esc_url( Relaymint_Admin::url( 'tools' ) ); ?>"><?php esc_html_e( 'Send a test email', 'relaymint' ); ?> &rarr;</a>
</p>
