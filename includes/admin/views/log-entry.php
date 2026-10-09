<?php
/**
 * Single email log entry.
 *
 * @package Relaymint
 *
 * @var object|null $entry Log entry.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relaymint_back = Relaymint_Admin::url( 'log' );
?>
<div class="wrap relaymint-wrap">
	<h1><?php esc_html_e( 'Email Details', 'relaymint' ); ?></h1>
	<p><a href="<?php echo esc_url( $relaymint_back ); ?>">&larr; <?php esc_html_e( 'Back to the email log', 'relaymint' ); ?></a></p>

	<?php if ( ! $entry ) : ?>
		<div class="notice notice-error inline"><p><?php esc_html_e( 'Log entry not found.', 'relaymint' ); ?></p></div>
		<?php
		return;
	endif;

	$relaymint_statuses    = Relaymint_Logger::statuses();
	$relaymint_attachments = json_decode( (string) $entry->attachments, true );
	$relaymint_rows        = array(
		__( 'Status', 'relaymint' )      => isset( $relaymint_statuses[ $entry->status ] ) ? $relaymint_statuses[ $entry->status ] : $entry->status,
		__( 'Date', 'relaymint' )        => get_date_from_gmt( $entry->created_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
		__( 'Subject', 'relaymint' )     => $entry->subject,
		__( 'From', 'relaymint' )        => $entry->from_email,
		__( 'To', 'relaymint' )          => $entry->to_email,
		__( 'CC', 'relaymint' )          => $entry->cc,
		__( 'BCC', 'relaymint' )         => $entry->bcc,
		__( 'Reply-To', 'relaymint' )    => $entry->reply_to,
		__( 'Connection', 'relaymint' )  => '' !== $entry->connection ? Relaymint_Connections::label( $entry->connection ) : '—',
		__( 'Initiator', 'relaymint' )   => trim( $entry->initiator_name . ( $entry->initiator_type ? ' (' . $entry->initiator_type . ')' : '' ) ),
		__( 'File', 'relaymint' )        => $entry->initiator_file,
		__( 'Attachments', 'relaymint' ) => is_array( $relaymint_attachments ) ? implode( ', ', $relaymint_attachments ) : '',
	);
	?>

	<table class="widefat striped relaymint-entry">
		<tbody>
			<?php foreach ( $relaymint_rows as $relaymint_label => $relaymint_value ) : ?>
				<?php
				if ( '' === (string) $relaymint_value ) {
					continue;
				}
				?>
				<tr>
					<th scope="row"><?php echo esc_html( $relaymint_label ); ?></th>
					<td><?php echo esc_html( $relaymint_value ); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( '' !== $entry->error ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Error', 'relaymint' ); ?></th>
					<td class="relaymint-error-text"><?php echo esc_html( $entry->error ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( '' !== $entry->headers ) : ?>
		<h2><?php esc_html_e( 'Headers', 'relaymint' ); ?></h2>
		<pre class="relaymint-pre"><?php echo esc_html( $entry->headers ); ?></pre>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Message', 'relaymint' ); ?></h2>
	<?php if ( '' === $entry->message ) : ?>
		<p><em><?php esc_html_e( 'The message content was not logged. Enable "Log Email Content" in the settings to store it.', 'relaymint' ); ?></em></p>
	<?php elseif ( 'text/html' === $entry->content_type ) : ?>
		<?php // The sandbox without allow-scripts keeps scripts in logged HTML from running in the admin. ?>
		<iframe class="relaymint-preview" sandbox="" referrerpolicy="no-referrer" srcdoc="<?php echo esc_attr( $entry->message ); ?>"></iframe>
	<?php else : ?>
		<pre class="relaymint-pre"><?php echo esc_html( $entry->message ); ?></pre>
	<?php endif; ?>

	<?php if ( '' !== $entry->message ) : ?>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=relaymint_resend&id=' . (int) $entry->id ), 'relaymint_resend_' . (int) $entry->id ) ); ?>"><?php esc_html_e( 'Resend', 'relaymint' ); ?></a>
		</p>
	<?php endif; ?>
</div>
