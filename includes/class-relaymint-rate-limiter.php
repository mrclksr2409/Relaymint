<?php
/**
 * Email rate limiting.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Limits how many queued emails are sent per minute/hour/day/week.
 *
 * Sent emails are counted from the queue table (rows with status "sent"),
 * which is why rate limiting requires "Optimize Email Sending".
 */
class Relaymint_Rate_Limiter {

	/**
	 * Whether rate limiting is active.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return Relaymint_Queue::is_enabled() && (bool) Relaymint_Options::value( 'misc', 'rate_limit' );
	}

	/**
	 * Configured limits keyed by period length in seconds (0 = unlimited, omitted).
	 *
	 * @return array<int,int>
	 */
	public static function limits() {
		$misc   = Relaymint_Options::get( 'misc' );
		$limits = array(
			MINUTE_IN_SECONDS => (int) $misc['rate_limit_minute'],
			HOUR_IN_SECONDS   => (int) $misc['rate_limit_hour'],
			DAY_IN_SECONDS    => (int) $misc['rate_limit_day'],
			WEEK_IN_SECONDS   => (int) $misc['rate_limit_week'],
		);

		/**
		 * Filter the rate limits.
		 *
		 * @param array<int,int> $limits Max emails keyed by period length in seconds.
		 */
		$limits = (array) apply_filters( 'relaymint_rate_limits', $limits );

		return array_filter(
			$limits,
			static function ( $limit ) {
				return $limit > 0;
			}
		);
	}

	/**
	 * Check whether another email may be sent now.
	 *
	 * @return int 0 when sending is allowed, otherwise the Unix timestamp when the next slot opens.
	 */
	public static function next_slot() {
		global $wpdb;

		if ( ! self::is_enabled() ) {
			return 0;
		}

		$table = Relaymint_Installer::queue_table();
		$now   = time();
		$next  = 0;

		foreach ( self::limits() as $period => $limit ) {
			$since = gmdate( 'Y-m-d H:i:s', $now - $period );
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = 'sent' AND sent_at >= %s", $since ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( $count < $limit ) {
				continue;
			}

			// The slot opens when the oldest email that still counts against the limit leaves the window.
			$oldest = $wpdb->get_var( $wpdb->prepare( "SELECT sent_at FROM {$table} WHERE status = 'sent' AND sent_at >= %s ORDER BY sent_at ASC LIMIT 1 OFFSET %d", $since, $count - $limit ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$slot   = $oldest ? strtotime( $oldest . ' UTC' ) + $period + 1 : $now + $period;
			$next   = max( $next, $slot );
		}

		return $next > $now ? $next : 0;
	}
}
