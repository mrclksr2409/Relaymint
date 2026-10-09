<?php
/**
 * Email log list.
 *
 * @package Relaymint
 *
 * @var Relaymint_Log_List_Table $table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap relaymint-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Email Log', 'relaymint' ); ?></h1>
	<?php if ( $table->has_items() ) : ?>
		<a class="page-title-action relaymint-confirm" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=relaymint_delete_logs' ), 'relaymint_delete_logs' ) ); ?>"><?php esc_html_e( 'Delete all', 'relaymint' ); ?></a>
	<?php endif; ?>
	<hr class="wp-header-end" />

	<?php if ( ! Relaymint_Logger::is_enabled() ) : ?>
		<div class="notice notice-info inline">
			<p>
				<?php esc_html_e( 'The email log is disabled.', 'relaymint' ); ?>
				<a href="<?php echo esc_url( Relaymint_Admin::url( '', array( 'tab' => 'logs' ) ) ); ?>"><?php esc_html_e( 'Enable it', 'relaymint' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<?php $table->views(); ?>
	<form method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( Relaymint_Admin::SLUG . '-log' ); ?>" />
		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- keep the current filter.
		if ( ! empty( $_GET['status'] ) ) :
			?>
			<input type="hidden" name="status" value="<?php echo esc_attr( sanitize_key( wp_unslash( $_GET['status'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />
		<?php endif; ?>
		<?php
		$table->search_box( __( 'Search emails', 'relaymint' ), 'relaymint-log' );
		$table->display();
		?>
	</form>
</div>
