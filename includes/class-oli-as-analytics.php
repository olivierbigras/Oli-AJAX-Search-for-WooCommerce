<?php
/**
 * Local search analytics.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores query counts on the site. No remote tracking.
 *
 * @since 0.1.0
 */
class Oli_AS_Analytics {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Analytics|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Analytics
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {}

	/**
	 * Records a search query.
	 *
	 * @since 0.1.0
	 *
	 * @param string $query        Query.
	 * @param string $language     Language.
	 * @param int    $result_count Hits.
	 * @return void
	 */
	public function record( $query, $language, $result_count ) {
		global $wpdb;

		$query = trim( mb_strtolower( $query, 'UTF-8' ) );
		if ( '' === $query ) {
			return;
		}

		$query    = mb_substr( $query, 0, 191, 'UTF-8' );
		$hash     = md5( $query );
		$table    = Oli_AS_Install::stats_table();
		$now      = current_time( 'mysql' );
		$language = sanitize_key( $language );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE query_hash = %s AND language = %s", $hash, $language ) );

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET hits = hits + 1, result_count = %d, last_hit = %s WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					(int) $result_count,
					$now,
					(int) $existing
				)
			);
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table,
			array(
				'query_hash'   => $hash,
				'query_text'   => $query,
				'language'     => $language,
				'result_count' => (int) $result_count,
				'hits'         => 1,
				'last_hit'     => $now,
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s' )
		);
	}

	/**
	 * Top queries.
	 *
	 * @since 0.1.0
	 *
	 * @param int $limit Limit.
	 * @return array<int, object>
	 */
	public function top_queries( $limit = 20 ) {
		global $wpdb;
		$table = Oli_AS_Install::stats_table();
		$limit = max( 1, min( 100, (int) $limit ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT query_text, language, hits, result_count, last_hit FROM {$table} ORDER BY hits DESC, last_hit DESC LIMIT {$limit}" );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Queries that returned no results.
	 *
	 * @since 0.1.0
	 *
	 * @param int $limit Limit.
	 * @return array<int, object>
	 */
	public function no_result_queries( $limit = 20 ) {
		global $wpdb;
		$table = Oli_AS_Install::stats_table();
		$limit = max( 1, min( 100, (int) $limit ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT query_text, language, hits, result_count, last_hit FROM {$table} WHERE result_count = 0 ORDER BY hits DESC, last_hit DESC LIMIT {$limit}" );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Deletes analytics rows.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function clear() {
		global $wpdb;
		$table = Oli_AS_Install::stats_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
