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

$relaymint_back    = Relaymint_Admin::url( 'log' );
$relaymint_actions = array(
	array(
		'label' => __( 'Back to the email log', 'relaymint' ),
		'url'   => $relaymint_back,
	),
);
if ( $entry && '' !== $entry->message ) {
	$relaymint_actions[] = array(
		'label'   => __( 'Resend', 'relaymint' ),
		'url'     => wp_nonce_url( admin_url( 'admin-post.php?action=relaymint_resend&id=' . (int) $entry->id ), 'relaymint_resend_' . (int) $entry->id ),
		'primary' => true,
	);
}
?>
<div class="wrap relaymint-wrap">
	<?php Relaymint_Admin::header( __( 'Email Details', 'relaymint' ), $relaymint_actions ); ?>

	<?php if ( ! $entry ) : ?>
		<?php echo WPB_Admin_UI::alert( __( 'Log entry not found.', 'relaymint' ), 'error' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the helper. ?>
		<?php
		return;
	endif;

	$relaymint_attachments = json_decode( (string) $entry->attachments, true );
	$relaymint_rows        = array(
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

	<section class="wpb-card relaymint-entry">
		<div class="wpb-card__body">
			<dl class="wpb-kv">
				<dt><?php esc_html_e( 'Status', 'relaymint' ); ?></dt>
				<dd><?php echo Relaymint_Admin::status_badge( $entry->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WPB_Admin_UI::badge(). ?></dd>
				<?php foreach ( $relaymint_rows as $relaymint_label => $relaymint_value ) : ?>
					<?php
					if ( '' === (string) $relaymint_value ) {
						continue;
					}
					?>
					<dt><?php echo esc_html( $relaymint_label ); ?></dt>
					<dd><?php echo esc_html( $relaymint_value ); ?></dd>
				<?php endforeach; ?>
				<?php if ( '' !== $entry->error ) : ?>
					<dt><?php esc_html_e( 'Error', 'relaymint' ); ?></dt>
					<dd><span class="wpb-text-error"><?php echo esc_html( $entry->error ); ?></span></dd>
				<?php endif; ?>
			</dl>
		</div>
	</section>

	<?php if ( '' !== $entry->headers ) : ?>
		<?php WPB_Admin_UI::card_start( array( 'title' => __( 'Headers', 'relaymint' ) ) ); ?>
		<pre class="wpb-code"><?php echo esc_html( $entry->headers ); ?></pre>
		<?php WPB_Admin_UI::card_end(); ?>
	<?php endif; ?>

	<?php WPB_Admin_UI::card_start( array( 'title' => __( 'Message', 'relaymint' ) ) ); ?>
	<?php if ( '' === $entry->message ) : ?>
		<p class="wpb-muted"><?php esc_html_e( 'The message content was not logged. Enable "Log Email Content" in the settings to store it.', 'relaymint' ); ?></p>
	<?php elseif ( 'text/html' === $entry->content_type ) : ?>
		<?php // The sandbox without allow-scripts keeps scripts in logged HTML from running in the admin. ?>
		<iframe class="relaymint-preview" sandbox="" referrerpolicy="no-referrer" srcdoc="<?php echo esc_attr( $entry->message ); ?>"></iframe>
	<?php else : ?>
		<pre class="wpb-code"><?php echo esc_html( $entry->message ); ?></pre>
	<?php endif; ?>
	<?php WPB_Admin_UI::card_end(); ?>
</div>
