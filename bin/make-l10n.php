<?php
/**
 * Converts a WordPress .po file into a .l10n.php array.
 *
 * @package Oli_Ajax_Search
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "CLI only.\n" );
	exit( 1 );
}

$po_path = isset( $argv[1] ) ? $argv[1] : '';
if ( '' === $po_path || ! is_readable( $po_path ) ) {
	fwrite( STDERR, "Usage: php bin/make-l10n.php languages/oli-ajax-search-fr_CA.po\n" );
	exit( 1 );
}

$lines    = file( $po_path, FILE_IGNORE_NEW_LINES );
$messages = array();
$msgid    = null;
$msgstr   = null;
$state    = null;
$header   = array();

foreach ( $lines as $line ) {
	if ( '' === $line || '#' === $line[0] ) {
		if ( null !== $msgid && null !== $msgstr ) {
			if ( '' === $msgid ) {
				foreach ( explode( "\n", $msgstr ) as $meta ) {
					if ( false !== strpos( $meta, ':' ) ) {
						list( $k, $v ) = array_map( 'trim', explode( ':', $meta, 2 ) );
						$header[ strtolower( $k ) ] = $v;
					}
				}
			} elseif ( '' !== $msgstr ) {
				$messages[ $msgid ] = $msgstr;
			}
			$msgid  = null;
			$msgstr = null;
			$state  = null;
		}
		continue;
	}

	if ( 0 === strpos( $line, 'msgid ' ) ) {
		$msgid = stripcslashes( substr( $line, 7, -1 ) );
		$state = 'msgid';
		continue;
	}
	if ( 0 === strpos( $line, 'msgstr ' ) ) {
		$msgstr = stripcslashes( substr( $line, 8, -1 ) );
		$state  = 'msgstr';
		continue;
	}
	if ( '"' === $line[0] && null !== $state ) {
		$chunk = stripcslashes( substr( $line, 1, -1 ) );
		if ( 'msgid' === $state ) {
			$msgid .= $chunk;
		} else {
			$msgstr .= $chunk;
		}
	}
}

if ( null !== $msgid && null !== $msgstr && '' !== $msgid && '' !== $msgstr ) {
	$messages[ $msgid ] = $msgstr;
}

$out = array(
	'messages' => array_merge(
		array(
			'' => array(
				'plural-forms' => isset( $header['plural-forms'] ) ? $header['plural-forms'] : 'nplurals=2; plural=(n > 1);',
				'language'     => isset( $header['language'] ) ? $header['language'] : 'fr_CA',
				'domain'       => 'oli-ajax-search',
			),
		),
		$messages
	),
);

$php_path = preg_replace( '/\.po$/', '.l10n.php', $po_path );
$export   = "<?php\nreturn " . var_export( $out, true ) . ";\n";
file_put_contents( $php_path, $export );
echo "Wrote {$php_path}\n";
