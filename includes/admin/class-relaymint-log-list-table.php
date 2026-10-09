<?php
/**
 * Email log list table.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Lists email log entries with search, status filter and bulk delete.
 */
class Relaymint_Log_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'relaymint_log',
				'plural'   => 'relaymint_logs',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'subject'    => __( 'Subject', 'relaymint' ),
			'to_email'   => __( 'To', 'relaymint' ),
			'status'     => __( 'Status', 'relaymint' ),
			'connection' => __( 'Connection', 'relaymint' ),
			'initiator'  => __( 'Initiator', 'relaymint' ),
			'created_at' => __( 'Date', 'relaymint' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'subject'    => array( 'subject', false ),
			'status'     => array( 'status', false ),
			'created_at' => array( 'created_at', true ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array( 'delete' => __( 'Delete', 'relaymint' ) );
	}

	/**
	 * Status views.
	 *
	 * @return array
	 */
	protected function get_views() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filter only.
		$current = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$counts  = Relaymint_Logger::count_by_status();
		$views   = array();

		$views['all'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( Relaymint_Admin::url( 'log' ) ),
			'' === $current ? ' class="current" aria-current="page"' : '',
			esc_html__( 'All', 'relaymint' ),
			array_sum( $counts )
		);

		foreach ( Relaymint_Logger::statuses() as $status => $label ) {
			if ( empty( $counts[ $status ] ) ) {
				continue;
			}
			$views[ $status ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( Relaymint_Admin::url( 'log', array( 'status' => $status ) ) ),
				$current === $status ? ' class="current" aria-current="page"' : '',
				esc_html( $label ),
				$counts[ $status ]
			);
		}
		return $views;
	}

	/**
	 * Load items.
	 */
	public function prepare_items() {
		global $wpdb;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filters.
		$per_page = 20;
		$paged    = $this->get_pagenum();
		$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$orderby  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order    = isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'ASC' : 'DESC';
		// phpcs:enable

		if ( ! in_array( $orderby, array( 'subject', 'status', 'created_at' ), true ) ) {
			$orderby = 'created_at';
		}

		$where  = array( '1=1' );
		$params = array();
		if ( '' !== $status && isset( Relaymint_Logger::statuses()[ $status ] ) ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}
		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(subject LIKE %s OR to_email LIKE %s OR from_email LIKE %s OR cc LIKE %s OR bcc LIKE %s OR initiator_name LIKE %s)';
			$params  = array_merge( $params, array_fill( 0, 6, $like ) );
		}

		$table     = Relaymint_Installer::log_table();
		$where_sql = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- WHERE clauses are built from fixed fragments with placeholders.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) );

		$items_sql   = "SELECT id, status, subject, from_email, to_email, connection, initiator_type, initiator_name, error, message, created_at FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order}, id {$order} LIMIT %d OFFSET %d";
		$this->items = $wpdb->get_results( $wpdb->prepare( $items_sql, array_merge( $params, array( $per_page, ( $paged - 1 ) * $per_page ) ) ) );
		// phpcs:enable

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'subject' );
		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);
	}

	/**
	 * Message when no items exist.
	 */
	public function no_items() {
		esc_html_e( 'No emails logged yet.', 'relaymint' );
	}

	/**
	 * Checkbox column.
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="log[]" value="%d" />', (int) $item->id );
	}

	/**
	 * Subject column with row actions.
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_subject( $item ) {
		$view_url = Relaymint_Admin::url( 'log', array( 'view' => (int) $item->id ) );
		$subject  = '' !== $item->subject ? $item->subject : __( '(no subject)', 'relaymint' );

		$actions = array(
			'view' => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'View', 'relaymint' ) ),
		);
		if ( '' !== $item->message ) {
			$actions['resend'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=relaymint_resend&id=' . (int) $item->id ), 'relaymint_resend_' . (int) $item->id ) ),
				esc_html__( 'Resend', 'relaymint' )
			);
		}

		return sprintf( '<strong><a href="%s">%s</a></strong>', esc_url( $view_url ), esc_html( $subject ) ) . $this->row_actions( $actions );
	}

	/**
	 * Status column.
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_status( $item ) {
		$out = Relaymint_Admin::status_badge( $item->status );
		if ( '' !== $item->error ) {
			$out .= '<br /><small class="wpb-text-error">' . esc_html( wp_trim_words( $item->error, 15 ) ) . '</small>';
		}
		return $out;
	}

	/**
	 * Connection column.
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_connection( $item ) {
		if ( '' === $item->connection ) {
			return Relaymint_Logger::STATUS_QUEUED === $item->status || Relaymint_Logger::STATUS_BLOCKED === $item->status ? '&mdash;' : esc_html__( 'PHP mail()', 'relaymint' );
		}
		return esc_html( Relaymint_Connections::label( $item->connection ) );
	}

	/**
	 * Initiator column.
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_initiator( $item ) {
		return '' !== $item->initiator_name ? esc_html( $item->initiator_name ) : '&mdash;';
	}

	/**
	 * Date column.
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_created_at( $item ) {
		return esc_html( get_date_from_gmt( $item->created_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) );
	}

	/**
	 * Fallback column.
	 *
	 * @param object $item        Row.
	 * @param string $column_name Column.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}
}
