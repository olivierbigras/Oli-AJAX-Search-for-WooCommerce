<?php
/**
 * IP-based rate limiter for public search endpoints.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Limits anonymous suggestion traffic per hashed IP.
 *
 * @since 0.2.0
 */
class Oli_AS_Rate_Limit {

	/**
	 * Maximum public searches per minute per IP.
	 *
	 * @since 0.2.0
	 * @var int
	 */
	const PER_MINUTE = 40;

	/**
	 * Whether the current request may run a public search.
	 *
	 * @since 0.2.0
	 *
	 * @param string $bucket Bucket name.
	 * @return bool
	 */
	public static function allow( $bucket = 'search' ) {
		$max = (int) apply_filters( 'oli_as_rate_limit_per_minute', self::PER_MINUTE, $bucket );
		if ( $max < 1 ) {
			return true;
		}

		$key  = 'oli_as_rl_' . sanitize_key( $bucket ) . '_' . self::ip_hash();
		$hits = (int) get_transient( $key );
		if ( $hits >= $max ) {
			return false;
		}

		set_transient( $key, $hits + 1, MINUTE_IN_SECONDS );

		return true;
	}

	/**
	 * Hashes the requester IP so the raw address is not stored.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public static function ip_hash() {
		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'oli-as';

		return substr( md5( $ip . '|' . $salt ), 0, 16 );
	}
}
