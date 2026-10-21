<?php
/**
 * Short-lived search result cache.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Versioned transients so index updates invalidate previous answers.
 *
 * @since 0.2.0
 */
class Oli_AS_Cache {

	/**
	 * Option that bumps the cache namespace.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	const VERSION_OPTION = 'oli_as_cache_ver';

	/**
	 * Returns a cached suggestion payload.
	 *
	 * @since 0.2.0
	 *
	 * @param string $query Query.
	 * @param string $lang  Language.
	 * @return array<string, mixed>|null
	 */
	public static function get( $query, $lang ) {
		$cached = get_transient( self::key( $query, $lang ) );
		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * Stores a suggestion payload.
	 *
	 * @since 0.2.0
	 *
	 * @param string               $query   Query.
	 * @param string               $lang    Language.
	 * @param array<string, mixed> $payload Payload.
	 * @return void
	 */
	public static function set( $query, $lang, $payload ) {
		$ttl = (int) apply_filters( 'oli_as_search_cache_ttl', 45, $query, $lang );
		if ( $ttl < 1 ) {
			return;
		}

		set_transient( self::key( $query, $lang ), $payload, $ttl );
	}

	/**
	 * Invalidates all cached suggestion payloads.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public static function bump() {
		$version = (int) get_option( self::VERSION_OPTION, 1 );
		update_option( self::VERSION_OPTION, $version + 1, false );
	}

	/**
	 * Transient key for a query.
	 *
	 * @since 0.2.0
	 *
	 * @param string $query Query.
	 * @param string $lang  Language.
	 * @return string
	 */
	protected static function key( $query, $lang ) {
		$version = (int) get_option( self::VERSION_OPTION, 1 );
		return 'oli_as_q_' . md5( strtolower( trim( $query ) ) . '|' . $lang . '|' . $version );
	}
}
