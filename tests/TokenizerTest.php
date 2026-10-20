<?php
/**
 * Tokenizer tests.
 *
 * @package Oli_Ajax_Search
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers Oli_AS_Tokenizer
 */
class TokenizerTest extends TestCase {

	public function test_normalize_strips_accents_and_html() {
		$result = Oli_AS_Tokenizer::normalize( '<p>Chapeau Été</p>' );
		$this->assertSame( 'chapeau ete', $result );
	}

	public function test_tokenize_splits_and_deduplicates() {
		$tokens = Oli_AS_Tokenizer::tokenize( 'Blue Shirt blue' );
		$this->assertSame( array( 'blue', 'shirt' ), $tokens );
	}

	public function test_tokenize_respects_min_length() {
		$tokens = Oli_AS_Tokenizer::tokenize( 'a to shirt', 3 );
		$this->assertSame( array( 'shirt' ), $tokens );
	}

	public function test_tokenize_sku_keeps_compact_form() {
		$tokens = Oli_AS_Tokenizer::tokenize_sku( 'AB-100' );
		$this->assertContains( 'ab', $tokens );
		$this->assertContains( '100', $tokens );
		$this->assertContains( 'ab100', $tokens );
		$this->assertContains( 'ab-100', $tokens );
	}

	public function test_remove_stopwords() {
		$tokens = Oli_AS_Tokenizer::remove_stopwords( array( 'the', 'blue', 'shirt' ), array( 'the' ) );
		$this->assertSame( array( 'blue', 'shirt' ), $tokens );
	}
}
