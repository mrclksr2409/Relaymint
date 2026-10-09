<?php
/**
 * Email log settings tab.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relaymint_logs = Relaymint_Options::get( 'logs' );
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="relaymint_save" />
	<input type="hidden" name="tab" value="logs" />
	<?php wp_nonce_field( 'relaymint_save' ); ?>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Email Log', 'relaymint' ); ?></th>
			<td>
				<label><input type="checkbox" name="logs[enabled]" value="1" <?php checked( $relaymint_logs['enabled'] ); ?> /> <?php esc_html_e( 'Enable the email log', 'relaymint' ); ?></label>
				<p class="description"><?php esc_html_e( 'Keeps a record of every email: recipients, subject, status, connection, initiator and error messages.', 'relaymint' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Log Email Content', 'relaymint' ); ?></th>
			<td>
				<label><input type="checkbox" name="logs[log_content]" value="1" <?php checked( $relaymint_logs['log_content'] ); ?> /> <?php esc_html_e( 'Store the message body and headers', 'relaymint' ); ?></label>
				<p class="description"><?php esc_html_e( 'Required to view and resend emails. Emails can contain personal data or password reset links, so only enable this if you need it.', 'relaymint' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="relaymint-retention"><?php esc_html_e( 'Log Retention', 'relaymint' ); ?></label></th>
			<td>
				<select id="relaymint-retention" name="logs[retention_days]">
					<?php
					foreach ( array(
						0   => __( 'Forever', 'relaymint' ),
						1   => __( '1 day', 'relaymint' ),
						7   => __( '1 week', 'relaymint' ),
						30  => __( '1 month', 'relaymint' ),
						90  => __( '3 months', 'relaymint' ),
						180 => __( '6 months', 'relaymint' ),
						365 => __( '1 year', 'relaymint' ),
					) as $relaymint_days => $relaymint_label ) :
						?>
						<option value="<?php echo esc_attr( $relaymint_days ); ?>" <?php selected( (int) $relaymint_logs['retention_days'], $relaymint_days ); ?>><?php echo esc_html( $relaymint_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Older log entries are deleted automatically once a day.', 'relaymint' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button(); ?>
</form>
