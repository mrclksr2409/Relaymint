<?php
/**
 * Misc tab.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relaymint_misc    = Relaymint_Options::get( 'misc' );
$relaymint_pending = Relaymint_Queue::count_pending();
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="relaymint-misc-form">
	<input type="hidden" name="action" value="relaymint_save" />
	<input type="hidden" name="tab" value="misc" />
	<?php wp_nonce_field( 'relaymint_save' ); ?>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Domain Check', 'relaymint' ); ?></th>
			<td>
				<label><input type="checkbox" class="relaymint-toggle" data-target=".relaymint-domain-check" name="misc[domain_check]" value="1" <?php checked( $relaymint_misc['domain_check'] ); ?> /> <?php esc_html_e( 'Enable domain check', 'relaymint' ); ?></label>
				<p class="description"><?php esc_html_e( 'Only use the SMTP settings when the site runs on one of the allowed domains. Useful for staging copies or migrated sites.', 'relaymint' ); ?></p>
				<div class="relaymint-domain-check relaymint-sub">
					<p>
						<label for="relaymint-allowed-domains"><?php esc_html_e( 'Allowed domains (comma separated)', 'relaymint' ); ?></label><br />
						<input type="text" id="relaymint-allowed-domains" class="large-text" name="misc[domain_check_allowed]" value="<?php echo esc_attr( $relaymint_misc['domain_check_allowed'] ); ?>" placeholder="<?php echo esc_attr( Relaymint_Domain_Check::site_domain() ); ?>" />
					</p>
					<p class="description">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: site domain */
								__( 'Current site domain: %s', 'relaymint' ),
								Relaymint_Domain_Check::site_domain()
							)
						);
						?>
					</p>
					<p><label><input type="checkbox" name="misc[domain_check_block_all]" value="1" <?php checked( $relaymint_misc['domain_check_block_all'] ); ?> /> <?php esc_html_e( 'Block all emails when the domain does not match', 'relaymint' ); ?></label></p>
					<p class="description"><?php esc_html_e( 'Without this option, emails are sent with the default PHP mailer instead of SMTP on non-matching domains.', 'relaymint' ); ?></p>
				</div>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Do Not Send', 'relaymint' ); ?></th>
			<td>
				<label><input type="checkbox" name="misc[do_not_send]" value="1" <?php checked( $relaymint_misc['do_not_send'] ); ?> /> <?php esc_html_e( 'Stop sending all emails', 'relaymint' ); ?></label>
				<p class="description"><?php esc_html_e( 'No emails are sent from this site. Blocked emails still appear in the email log. Useful for development and staging sites.', 'relaymint' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Allow Insecure SSL Certificates', 'relaymint' ); ?></th>
			<td>
				<label><input type="checkbox" name="misc[allow_insecure_ssl]" value="1" <?php checked( $relaymint_misc['allow_insecure_ssl'] ); ?> /> <?php esc_html_e( 'Allow self-signed or invalid SSL certificates', 'relaymint' ); ?></label>
				<p class="description"><?php esc_html_e( 'Disables certificate verification for the SMTP connection. Only enable this if your server uses a self-signed certificate — it makes the connection vulnerable to interception.', 'relaymint' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Debug Log', 'relaymint' ); ?></th>
			<td>
				<label><input type="checkbox" name="misc[debug_log]" value="1" <?php checked( $relaymint_misc['debug_log'] ); ?> /> <?php esc_html_e( 'Enable debug log', 'relaymint' ); ?></label>
				<p class="description">
					<?php esc_html_e( 'Records the SMTP conversation of every email (credentials are masked). Errors are always recorded.', 'relaymint' ); ?>
					<a href="<?php echo esc_url( Relaymint_Admin::url( 'tools' ) . '#debug-log' ); ?>"><?php esc_html_e( 'View debug log', 'relaymint' ); ?></a>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Optimize Email Sending', 'relaymint' ); ?></th>
			<td>
				<label><input type="checkbox" class="relaymint-toggle" data-target=".relaymint-rate-limit-row" name="misc[optimize_sending]" value="1" <?php checked( $relaymint_misc['optimize_sending'] ); ?> /> <?php esc_html_e( 'Send emails in the background', 'relaymint' ); ?></label>
				<p class="description"><?php esc_html_e( 'Emails are queued and sent asynchronously via Action Scheduler, so pages that send emails load faster.', 'relaymint' ); ?></p>
				<?php if ( $relaymint_pending > 0 ) : ?>
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of emails */
								_n( '%d email is waiting in the queue.', '%d emails are waiting in the queue.', $relaymint_pending, 'relaymint' ),
								$relaymint_pending
							)
						);
						?>
						<a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=relaymint_run_queue' ), 'relaymint_run_queue' ) ); ?>"><?php esc_html_e( 'Process queue now', 'relaymint' ); ?></a>
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<tr class="relaymint-rate-limit-row">
			<th scope="row"><?php esc_html_e( 'Email Rate Limiting', 'relaymint' ); ?></th>
			<td>
				<label><input type="checkbox" class="relaymint-toggle" data-target=".relaymint-rate-limits" name="misc[rate_limit]" value="1" <?php checked( $relaymint_misc['rate_limit'] ); ?> /> <?php esc_html_e( 'Limit the number of emails sent', 'relaymint' ); ?></label>
				<p class="description"><?php esc_html_e( 'Emails above the limit stay in the queue and are sent as soon as the limit allows. Leave a field empty or 0 for no limit.', 'relaymint' ); ?></p>
				<div class="relaymint-rate-limits relaymint-sub">
					<?php
					foreach ( array(
						'minute' => __( 'Per minute', 'relaymint' ),
						'hour'   => __( 'Per hour', 'relaymint' ),
						'day'    => __( 'Per day', 'relaymint' ),
						'week'   => __( 'Per week', 'relaymint' ),
					) as $relaymint_period => $relaymint_label ) :
						?>
						<p>
							<label>
								<span class="relaymint-rate-label"><?php echo esc_html( $relaymint_label ); ?></span>
								<input type="number" min="0" class="small-text" name="misc[rate_limit_<?php echo esc_attr( $relaymint_period ); ?>]" value="<?php echo esc_attr( $relaymint_misc[ 'rate_limit_' . $relaymint_period ] ? $relaymint_misc[ 'rate_limit_' . $relaymint_period ] : '' ); ?>" />
							</label>
						</p>
					<?php endforeach; ?>
				</div>
			</td>
		</tr>
	</table>

	<?php submit_button(); ?>
</form>
