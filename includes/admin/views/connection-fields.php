<?php
/**
 * Form fields of a connection (SMTP or Microsoft 365 / Outlook).
 *
 * @package Relaymint
 *
 * @var string $id         Connection ID ('' for a new one).
 * @var array  $connection Stored connection values.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relaymint_locked    = static function ( $field ) use ( $id ) {
	return '' !== $id && Relaymint_Connections::is_overridden( $id, $field );
};
$relaymint_lock_note = static function ( $field ) use ( $id ) {
	if ( '' !== $id && Relaymint_Connections::is_overridden( $id, $field ) ) {
		echo '<p class="description relaymint-locked">' . esc_html(
			sprintf(
				/* translators: %s: constant name */
				__( 'Defined by the constant %s in wp-config.php.', 'relaymint' ),
				Relaymint_Connections::constant_name( $id, $field )
			)
		) . '</p>';
	}
};
$relaymint_has_pass  = '' !== $connection['pass'] || $relaymint_locked( 'pass' );
$relaymint_mailer    = '' !== $id && Relaymint_Connections::is_overridden( $id, 'mailer' ) ? constant( Relaymint_Connections::constant_name( $id, 'mailer' ) ) : $connection['mailer'];
?>
<h2><?php esc_html_e( 'Mailer', 'relaymint' ); ?></h2>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><?php esc_html_e( 'Send via', 'relaymint' ); ?></th>
		<td>
			<fieldset class="relaymint-mailer">
				<?php foreach ( Relaymint_Connections::mailers() as $relaymint_value => $relaymint_label ) : ?>
					<label><input type="radio" name="connection[mailer]" value="<?php echo esc_attr( $relaymint_value ); ?>" <?php checked( $relaymint_mailer, $relaymint_value ); ?> <?php disabled( $relaymint_locked( 'mailer' ) ); ?> /> <?php echo esc_html( $relaymint_label ); ?></label>&nbsp;&nbsp;
				<?php endforeach; ?>
			</fieldset>
			<?php $relaymint_lock_note( 'mailer' ); ?>
			<p class="description"><?php esc_html_e( 'SMTP works with any provider. Microsoft 365 / Outlook sends through the Microsoft Graph API with OAuth 2.0 and does not need SMTP AUTH or a mailbox password.', 'relaymint' ); ?></p>
		</td>
	</tr>
</table>

<h2><?php esc_html_e( 'Sender', 'relaymint' ); ?></h2>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="relaymint-from-email"><?php esc_html_e( 'From Email', 'relaymint' ); ?></label></th>
		<td>
			<input type="email" id="relaymint-from-email" class="regular-text" name="connection[from_email]" value="<?php echo esc_attr( $connection['from_email'] ); ?>" <?php disabled( $relaymint_locked( 'from_email' ) ); ?> />
			<?php $relaymint_lock_note( 'from_email' ); ?>
			<p class="description"><?php esc_html_e( 'The email address emails are sent from. Most SMTP providers require an address of the authenticated account or domain.', 'relaymint' ); ?></p>
			<p><label><input type="checkbox" name="connection[force_from_email]" value="1" <?php checked( $connection['force_from_email'] ); ?> /> <?php esc_html_e( 'Force From Email', 'relaymint' ); ?></label></p>
			<p class="description"><?php esc_html_e( 'Use this address for every email, even if a plugin sets a different one. Otherwise it only replaces the WordPress default address.', 'relaymint' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="relaymint-from-name"><?php esc_html_e( 'From Name', 'relaymint' ); ?></label></th>
		<td>
			<input type="text" id="relaymint-from-name" class="regular-text" name="connection[from_name]" value="<?php echo esc_attr( $connection['from_name'] ); ?>" <?php disabled( $relaymint_locked( 'from_name' ) ); ?> />
			<?php $relaymint_lock_note( 'from_name' ); ?>
			<p><label><input type="checkbox" name="connection[force_from_name]" value="1" <?php checked( $connection['force_from_name'] ); ?> /> <?php esc_html_e( 'Force From Name', 'relaymint' ); ?></label></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Return Path', 'relaymint' ); ?></th>
		<td>
			<label><input type="checkbox" name="connection[return_path]" value="1" <?php checked( $connection['return_path'] ); ?> /> <?php esc_html_e( 'Set the return path to match the From Email', 'relaymint' ); ?></label>
			<p class="description"><?php esc_html_e( 'Bounce notifications are then sent to the From Email address.', 'relaymint' ); ?></p>
		</td>
	</tr>
</table>

<div class="relaymint-mailer-section" data-mailer="<?php echo esc_attr( Relaymint_Connections::MAILER_SMTP ); ?>">
<h2><?php esc_html_e( 'SMTP Server', 'relaymint' ); ?></h2>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="relaymint-host"><?php esc_html_e( 'SMTP Host', 'relaymint' ); ?></label></th>
		<td>
			<input type="text" id="relaymint-host" class="regular-text" name="connection[host]" value="<?php echo esc_attr( $connection['host'] ); ?>" placeholder="smtp.example.com" <?php disabled( $relaymint_locked( 'host' ) ); ?> />
			<?php $relaymint_lock_note( 'host' ); ?>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Encryption', 'relaymint' ); ?></th>
		<td>
			<fieldset class="relaymint-encryption">
				<?php
				$relaymint_encryptions = array(
					'none' => array( __( 'None', 'relaymint' ), 25 ),
					'ssl'  => array( __( 'SSL', 'relaymint' ), 465 ),
					'tls'  => array( __( 'TLS', 'relaymint' ), 587 ),
				);
				foreach ( $relaymint_encryptions as $relaymint_value => $relaymint_encryption ) :
					?>
					<label><input type="radio" name="connection[encryption]" value="<?php echo esc_attr( $relaymint_value ); ?>" data-port="<?php echo esc_attr( $relaymint_encryption[1] ); ?>" <?php checked( $connection['encryption'], $relaymint_value ); ?> <?php disabled( $relaymint_locked( 'encryption' ) ); ?> /> <?php echo esc_html( $relaymint_encryption[0] ); ?></label>&nbsp;&nbsp;
				<?php endforeach; ?>
			</fieldset>
			<?php $relaymint_lock_note( 'encryption' ); ?>
			<p class="description"><?php esc_html_e( 'TLS (STARTTLS) is recommended for port 587, SSL for port 465.', 'relaymint' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="relaymint-port"><?php esc_html_e( 'SMTP Port', 'relaymint' ); ?></label></th>
		<td>
			<input type="number" id="relaymint-port" class="small-text" min="1" max="65535" name="connection[port]" value="<?php echo esc_attr( $connection['port'] ); ?>" <?php disabled( $relaymint_locked( 'port' ) ); ?> />
			<?php $relaymint_lock_note( 'port' ); ?>
		</td>
	</tr>
	<tr class="relaymint-autotls-row">
		<th scope="row"><?php esc_html_e( 'Auto TLS', 'relaymint' ); ?></th>
		<td>
			<label><input type="checkbox" name="connection[autotls]" value="1" <?php checked( $connection['autotls'] ); ?> /> <?php esc_html_e( 'Use TLS automatically if the server supports it', 'relaymint' ); ?></label>
			<p class="description"><?php esc_html_e( 'Only relevant when encryption is set to "None". Disable it if your server advertises TLS but fails to use it.', 'relaymint' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Authentication', 'relaymint' ); ?></th>
		<td>
			<label><input type="checkbox" class="relaymint-auth-toggle" name="connection[auth]" value="1" <?php checked( $connection['auth'] ); ?> /> <?php esc_html_e( 'Use SMTP authentication', 'relaymint' ); ?></label>
		</td>
	</tr>
	<tr class="relaymint-auth-row">
		<th scope="row"><label for="relaymint-user"><?php esc_html_e( 'SMTP Username', 'relaymint' ); ?></label></th>
		<td>
			<input type="text" id="relaymint-user" class="regular-text" name="connection[user]" value="<?php echo esc_attr( $connection['user'] ); ?>" autocomplete="off" <?php disabled( $relaymint_locked( 'user' ) ); ?> />
			<?php $relaymint_lock_note( 'user' ); ?>
		</td>
	</tr>
	<tr class="relaymint-auth-row">
		<th scope="row"><label for="relaymint-pass"><?php esc_html_e( 'SMTP Password', 'relaymint' ); ?></label></th>
		<td>
			<?php if ( $relaymint_locked( 'pass' ) ) : ?>
				<input type="password" id="relaymint-pass" class="regular-text" value="" placeholder="••••••••" disabled />
				<?php $relaymint_lock_note( 'pass' ); ?>
			<?php else : ?>
				<input type="password" id="relaymint-pass" class="regular-text" name="connection[pass]" value="" placeholder="<?php echo $relaymint_has_pass ? '••••••••' : ''; ?>" autocomplete="new-password" />
				<?php if ( $relaymint_has_pass ) : ?>
					<p class="description"><?php esc_html_e( 'A password is stored (encrypted). Leave empty to keep it.', 'relaymint' ); ?></p>
					<p><label><input type="checkbox" name="connection[clear_pass]" value="1" /> <?php esc_html_e( 'Remove the stored password', 'relaymint' ); ?></label></p>
				<?php endif; ?>
				<?php if ( '' !== $id ) : ?>
					<p class="description">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: constant name */
								__( 'Tip: define %s in wp-config.php to keep the password out of the database.', 'relaymint' ),
								Relaymint_Connections::constant_name( $id, 'pass' )
							)
						);
						?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</td>
	</tr>
</table>
</div>

<div class="relaymint-mailer-section" data-mailer="<?php echo esc_attr( Relaymint_Connections::MAILER_MICROSOFT ); ?>">
	<?php require __DIR__ . '/connection-fields-microsoft.php'; ?>
</div>
