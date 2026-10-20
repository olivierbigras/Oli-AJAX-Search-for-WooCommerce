<?php
/**
 * PHPUnit bootstrap for WordPress-free unit tests.
 *
 * @package Oli_Ajax_Search
 */

define( 'OLI_AS_TEST_BOOTSTRAP', true );

if ( ! function_exists( '__' ) ) {
	/**
	 * Test stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Test stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html__( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Test stub.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Test stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function sanitize_text_field( $text ) {
		return trim( strip_tags( (string) $text ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Test stub.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Test stub.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $key ) );
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/oli-as-tests/' );
}

require dirname( __DIR__ ) . '/includes/class-oli-as-tokenizer.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-synonyms.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-relevance.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-index-engine.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-language.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-settings.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-visibility.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-rate-limit.php';
