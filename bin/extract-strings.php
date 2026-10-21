<?php
/**
 * Extracts gettext strings for the French catalog.
 *
 * @package Oli_Ajax_Search
 */

$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( dirname( __DIR__ ) ) );
$out = array();

foreach ( $rii as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}
	$path = $file->getPathname();
	if ( false !== strpos( $path, '/vendor/' ) || false !== strpos( $path, '/.git/' ) ) {
		continue;
	}
	if ( ! preg_match( '/\.(php|js)$/', $path ) ) {
		continue;
	}
	$src = file_get_contents( $path );
	if ( ! is_string( $src ) ) {
		continue;
	}
	if ( preg_match_all( '/(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e|_x|esc_html_x)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/', $src, $matches ) ) {
		foreach ( $matches[2] as $string ) {
			$out[ stripcslashes( $string ) ] = true;
		}
	}
}

ksort( $out );
echo implode( "\n", array_keys( $out ) );