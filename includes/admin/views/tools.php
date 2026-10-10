<?php
/**
 * Tools page: test email and debug log.
 *
 * @package Relaymint
 *
 * @var array|false $test_result Result of the last test email.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relaymint_connections = Relaymint_Connections::all();
$relaymint_debug       = array_reverse( Relaymint_Debug_Log::get() );
$relaymint_user        = wp_get_current_user();
?>
<div class="wrap relaymint-wrap">
	<?php Relaymint_Admin::header( __( 'Relaymint Tools', 'relaymint' ) ); ?>

	<?php WPB_Admin_UI::card_start( array( 'title' => __( 'Send a Test Email', 'relaymint' ) ) ); ?>

	<?php if ( is_array( $test_result ) ) : ?>
		<?php
		if ( $test_result['success'] ) {
			$relaymint_alert = WPB_Admin_UI::alert(
				sprintf(
					/* translators: %s: email address */
					__( 'The test email was sent to %s.', 'relaymint' ),
					$test_result['to']
				),
				'success'
			);
		} elseif ( '' !== $test_result['error'] ) {
			$relaymint_alert = WPB_Admin_UI::alert( $test_result['error'], 'error', __( 'The test email could not be sent.', 'relaymint' ) );
		} else {
			$relaymint_alert = WPB_Admin_UI::alert( __( 'The test email could not be sent.', 'relaymint' ), 'error' );
		}
		echo $relaymint_alert; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WPB_Admin_UI::alert().
		?>
		<?php if ( '' !== $test_result['transcript'] ) : ?>
			<details <?php echo $test_result['success'] ? '' : 'open'; ?>>
				<summary><?php esc_html_e( 'Debug output', 'relaymint' ); ?></summary>
				<pre class="wpb-code"><?php echo esc_html( $test_result['transcript'] ); ?></pre>
			</details>
		<?php endif; ?>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="relaymint_test_email" />
		<?php wp_nonce_field( 'relaymint_test_email' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="relaymint-test-to"><?php esc_html_e( 'Send To', 'relaymint' ); ?></label></th>
				<td><input type="email" id="relaymint-test-to" name="to" class="regular-text" value="<?php echo esc_attr( $relaymint_user->user_email ); ?>" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="relaymint-test-connection"><?php esc_html_e( 'Connection', 'relaymint' ); ?></label></th>
				<td>
					<select id="relaymint-test-connection" name="connection">
						<?php foreach ( array_keys( $relaymint_connections ) as $relaymint_id ) : ?>
							<option value="<?php echo esc_attr( $relaymint_id ); ?>"><?php echo esc_html( Relaymint_Connections::label( $relaymint_id ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'HTML', 'relaymint' ); ?></th>
				<td><label><input type="checkbox" name="html" value="1" checked /> <?php esc_html_e( 'Send as HTML', 'relaymint' ); ?></label></td>
			</tr>
		</table>
		<?php submit_button( __( 'Send Test Email', 'relaymint' ) ); ?>
	</form>

	<?php WPB_Admin_UI::card_end(); ?>

	<section class="wpb-card" id="debug-log">
		<header class="wpb-card__header">
			<div>
				<h2 class="wpb-card__title"><?php esc_html_e( 'Debug Log', 'relaymint' ); ?></h2>
				<?php if ( ! Relaymint_Debug_Log::is_enabled() ) : ?>
					<p class="wpb-card__description"><?php esc_html_e( 'Only errors are recorded. Enable the debug log in the Misc settings to record the full SMTP conversation.', 'relaymint' ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $relaymint_debug ) : ?>
				<div class="wpb-row">
					<a class="button" data-wpb-confirm="<?php esc_attr_e( 'Are you sure?', 'relaymint' ); ?>" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=relaymint_clear_debug_log' ), 'relaymint_clear_debug_log' ) ); ?>"><?php esc_html_e( 'Clear debug log', 'relaymint' ); ?></a>
				</div>
			<?php endif; ?>
		</header>
		<div class="wpb-card__body">
			<?php if ( ! $relaymint_debug ) : ?>
				<p class="wpb-muted"><?php esc_html_e( 'The debug log is empty.', 'relaymint' ); ?></p>
			<?php else : ?>
				<div class="relaymint-debug-log">
					<?php foreach ( $relaymint_debug as $relaymint_entry ) : ?>
						<div class="relaymint-debug-entry relaymint-debug-<?php echo esc_attr( $relaymint_entry['level'] ); ?>">
							<div class="relaymint-debug-meta">
								<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) . ':s', (int) $relaymint_entry['time'] ) ); ?>
								&middot; <?php echo esc_html( strtoupper( $relaymint_entry['level'] ) ); ?>
							</div>
							<pre class="wpb-code"><?php echo esc_html( $relaymint_entry['message'] ); ?></pre>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</div>
