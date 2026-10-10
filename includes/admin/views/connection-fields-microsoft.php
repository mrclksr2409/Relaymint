<?php
/**
 * Form fields of a Microsoft 365 / Outlook connection (included by connection-fields.php).
 *
 * @package Relaymint
 *
 * @var string   $id                  Connection ID ('' for a new one).
 * @var array    $connection          Stored connection values.
 * @var callable $relaymint_locked    Whether a field is defined by a constant.
 * @var callable $relaymint_lock_note Prints the constant note of a field.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relaymint_resolved     = '' !== $id ? Relaymint_Connections::get( $id ) : null;
$relaymint_has_secret   = '' !== $connection['ms_client_secret'] || $relaymint_locked( 'ms_client_secret' );
$relaymint_redirect_uri = Relaymint_Microsoft::redirect_uri();
$relaymint_auth         = $relaymint_resolved ? $relaymint_resolved['ms_auth'] : $connection['ms_auth'];
$relaymint_ready        = $relaymint_resolved && Relaymint_Connections::MAILER_MICROSOFT === $relaymint_resolved['mailer'] && '' !== $relaymint_resolved['ms_client_id'] && '' !== $relaymint_resolved['ms_client_secret'];
$relaymint_ms_url       = static function ( $action ) use ( $id ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=' . $action . '&id=' . rawurlencode( $id ) ), $action . '_' . $id );
};
?>
<h2><?php esc_html_e( 'Microsoft 365 / Outlook', 'relaymint' ); ?></h2>
<p class="description">
	<?php esc_html_e( 'Register an app in the Microsoft Entra admin center (App registrations), add the redirect URI below as platform "Web", create a client secret and grant the Microsoft Graph permission Mail.Send.', 'relaymint' ); ?>
</p>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="relaymint-ms-redirect"><?php esc_html_e( 'Redirect URI', 'relaymint' ); ?></label></th>
		<td>
			<input type="text" id="relaymint-ms-redirect" class="large-text code" value="<?php echo esc_attr( $relaymint_redirect_uri ); ?>" readonly onfocus="this.select();" />
			<?php if ( 0 !== strpos( $relaymint_redirect_uri, 'https://' ) && ! preg_match( '#^http://(localhost|127\.0\.0\.1)[:/]#', $relaymint_redirect_uri ) ) : ?>
				<p class="description relaymint-locked"><?php esc_html_e( 'Microsoft only accepts HTTPS redirect URIs (except for localhost). Enable HTTPS for the WordPress admin to sign in.', 'relaymint' ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Only needed for "Sign in with the mailbox".', 'relaymint' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Authentication', 'relaymint' ); ?></th>
		<td>
			<fieldset class="relaymint-ms-auth">
				<p><label><input type="radio" name="connection[ms_auth]" value="<?php echo esc_attr( Relaymint_Microsoft::AUTH_DELEGATED ); ?>" <?php checked( $relaymint_auth, Relaymint_Microsoft::AUTH_DELEGATED ); ?> /> <?php esc_html_e( 'Sign in with the mailbox (delegated permission)', 'relaymint' ); ?></label></p>
				<p class="description"><?php esc_html_e( 'Works with Microsoft 365 work or school accounts and personal Outlook.com accounts. Required permissions: Mail.Send, User.Read and offline_access (delegated).', 'relaymint' ); ?></p>
				<p><label><input type="radio" name="connection[ms_auth]" value="<?php echo esc_attr( Relaymint_Microsoft::AUTH_APPLICATION ); ?>" <?php checked( $relaymint_auth, Relaymint_Microsoft::AUTH_APPLICATION ); ?> /> <?php esc_html_e( 'App-only (application permission)', 'relaymint' ); ?></label></p>
				<p class="description"><?php esc_html_e( 'Microsoft 365 only, no sign-in needed. Required permission: Mail.Send (application) with admin consent. Emails are sent from the From Email mailbox; restrict the app with an Exchange application access policy.', 'relaymint' ); ?></p>
			</fieldset>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="relaymint-ms-tenant"><?php esc_html_e( 'Directory (tenant) ID', 'relaymint' ); ?></label></th>
		<td>
			<input type="text" id="relaymint-ms-tenant" class="regular-text code" name="connection[ms_tenant]" value="<?php echo esc_attr( $connection['ms_tenant'] ); ?>" placeholder="common" <?php disabled( $relaymint_locked( 'ms_tenant' ) ); ?> />
			<?php $relaymint_lock_note( 'ms_tenant' ); ?>
			<p class="description"><?php esc_html_e( 'Tenant ID or domain (e.g. contoso.onmicrosoft.com). For "Sign in with the mailbox" you can also use "common" (any account), "organizations" or "consumers" (Outlook.com). App-only requires your tenant ID.', 'relaymint' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="relaymint-ms-client-id"><?php esc_html_e( 'Application (client) ID', 'relaymint' ); ?></label></th>
		<td>
			<input type="text" id="relaymint-ms-client-id" class="regular-text code" name="connection[ms_client_id]" value="<?php echo esc_attr( $connection['ms_client_id'] ); ?>" autocomplete="off" <?php disabled( $relaymint_locked( 'ms_client_id' ) ); ?> />
			<?php $relaymint_lock_note( 'ms_client_id' ); ?>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="relaymint-ms-client-secret"><?php esc_html_e( 'Client Secret', 'relaymint' ); ?></label></th>
		<td>
			<?php if ( $relaymint_locked( 'ms_client_secret' ) ) : ?>
				<input type="password" id="relaymint-ms-client-secret" class="regular-text" value="" placeholder="••••••••" disabled />
				<?php $relaymint_lock_note( 'ms_client_secret' ); ?>
			<?php else : ?>
				<input type="password" id="relaymint-ms-client-secret" class="regular-text" name="connection[ms_client_secret]" value="" placeholder="<?php echo $relaymint_has_secret ? '••••••••' : ''; ?>" autocomplete="new-password" />
				<p class="description"><?php esc_html_e( 'The secret value (not the secret ID). Client secrets expire; create a new one in time.', 'relaymint' ); ?></p>
				<?php if ( $relaymint_has_secret ) : ?>
					<p class="description"><?php esc_html_e( 'A client secret is stored (encrypted). Leave empty to keep it.', 'relaymint' ); ?></p>
					<p><label><input type="checkbox" name="connection[clear_ms_client_secret]" value="1" /> <?php esc_html_e( 'Remove the stored client secret', 'relaymint' ); ?></label></p>
				<?php endif; ?>
				<?php if ( '' !== $id ) : ?>
					<p class="description">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: constant name */
								__( 'Tip: define %s in wp-config.php to keep the client secret out of the database.', 'relaymint' ),
								Relaymint_Connections::constant_name( $id, 'ms_client_secret' )
							)
						);
						?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</td>
	</tr>
	<tr class="relaymint-ms-delegated-row">
		<th scope="row"><?php esc_html_e( 'Microsoft Account', 'relaymint' ); ?></th>
		<td>
			<?php if ( ! $relaymint_resolved ) : ?>
				<p class="description"><?php esc_html_e( 'Save the connection first, then sign in with the mailbox.', 'relaymint' ); ?></p>
			<?php else : ?>
				<?php if ( Relaymint_Microsoft::is_authorized( $relaymint_resolved ) ) : ?>
					<p>
						<?php
						$relaymint_account = Relaymint_Microsoft::account( $relaymint_resolved );
						echo WPB_Admin_UI::badge( '' !== $relaymint_account ? $relaymint_account : __( 'Connected', 'relaymint' ), 'success', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WPB_Admin_UI::badge().
						?>
					</p>
					<p>
						<a class="button" href="<?php echo esc_url( $relaymint_ms_url( 'relaymint_ms_connect' ) ); ?>"><?php esc_html_e( 'Reconnect', 'relaymint' ); ?></a>
						<a class="button wpb-link-danger" data-wpb-confirm="<?php esc_attr_e( 'Are you sure?', 'relaymint' ); ?>" href="<?php echo esc_url( $relaymint_ms_url( 'relaymint_ms_disconnect' ) ); ?>"><?php esc_html_e( 'Disconnect', 'relaymint' ); ?></a>
					</p>
				<?php else : ?>
					<p><?php echo WPB_Admin_UI::badge( __( 'Not connected', 'relaymint' ), 'warning', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WPB_Admin_UI::badge(). ?></p>
					<?php if ( $relaymint_ready ) : ?>
						<p><a class="button button-primary" href="<?php echo esc_url( $relaymint_ms_url( 'relaymint_ms_connect' ) ); ?>"><?php esc_html_e( 'Connect with Microsoft', 'relaymint' ); ?></a></p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'Select Microsoft 365 / Outlook, enter the client ID and secret and save the connection, then sign in with the mailbox.', 'relaymint' ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Save your changes before connecting. Leave the From Email empty to send as the signed-in mailbox; other addresses need "Send As" permission in Exchange.', 'relaymint' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
</table>
