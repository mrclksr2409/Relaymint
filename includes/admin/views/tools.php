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
	<h1><?php esc_html_e( 'Relaymint Tools', 'relaymint' ); ?></h1>

	<h2><?php esc_html_e( 'Send a Test Email', 'relaymint' ); ?></h2>

	<?php if ( is_array( $test_result ) ) : ?>
		<?php if ( $test_result['success'] ) : ?>
			<div class="notice notice-success inline">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: email address */
							__( 'The test email was sent to %s.', 'relaymint' ),
							$test_result['to']
						)
					);
					?>
				</p>
			</div>
		<?php else : ?>
			<div class="notice notice-error inline">
				<p><strong><?php esc_html_e( 'The test email could not be sent.', 'relaymint' ); ?></strong></p>
				<?php if ( '' !== $test_result['error'] ) : ?>
					<p><?php echo esc_html( $test_result['error'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $test_result['transcript'] ) : ?>
			<details <?php echo $test_result['success'] ? '' : 'open'; ?>>
				<summary><?php esc_html_e( 'SMTP debug output', 'relaymint' ); ?></summary>
				<pre class="relaymint-pre"><?php echo esc_html( $test_result['transcript'] ); ?></pre>
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

	<h2 id="debug-log"><?php esc_html_e( 'Debug Log', 'relaymint' ); ?></h2>
	<?php if ( ! Relaymint_Debug_Log::is_enabled() ) : ?>
		<p class="description">
			<?php esc_html_e( 'Only errors are recorded. Enable the debug log in the Misc settings to record the full SMTP conversation.', 'relaymint' ); ?>
		</p>
	<?php endif; ?>

	<?php if ( ! $relaymint_debug ) : ?>
		<p><?php esc_html_e( 'The debug log is empty.', 'relaymint' ); ?></p>
	<?php else : ?>
		<p>
			<a class="button relaymint-confirm" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=relaymint_clear_debug_log' ), 'relaymint_clear_debug_log' ) ); ?>"><?php esc_html_e( 'Clear debug log', 'relaymint' ); ?></a>
		</p>
		<div class="relaymint-debug-log">
			<?php foreach ( $relaymint_debug as $relaymint_entry ) : ?>
				<div class="relaymint-debug-entry relaymint-debug-<?php echo esc_attr( $relaymint_entry['level'] ); ?>">
					<div class="relaymint-debug-meta">
						<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) . ':s', (int) $relaymint_entry['time'] ) ); ?>
						&middot; <?php echo esc_html( strtoupper( $relaymint_entry['level'] ) ); ?>
					</div>
					<pre class="relaymint-pre"><?php echo esc_html( $relaymint_entry['message'] ); ?></pre>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
