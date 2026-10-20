<?php
/**
 * PHPUnit bootstrap for WordPress-free unit tests.
 *
 * @package Oli_Ajax_Search
 */

define( 'OLI_AS_TEST_BOOTSTRAP', true );

require dirname( __DIR__ ) . '/includes/class-oli-as-tokenizer.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-synonyms.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-relevance.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-index-engine.php';
require dirname( __DIR__ ) . '/includes/class-oli-as-language.php';
