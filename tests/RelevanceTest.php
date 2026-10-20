<?php
/**
 * Relevance tests.
 *
 * @package Oli_Ajax_Search
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers Oli_AS_Relevance
 */
class RelevanceTest extends TestCase {

	public function test_exact_title_scores_higher_than_content() {
		$query  = array( 'hoodie' );
		$title  = Oli_AS_Relevance::score( $query, array( 'title' => array( 'hoodie' ) ) );
		$content = Oli_AS_Relevance::score( $query, array( 'content' => array( 'hoodie' ) ) );
		$this->assertGreaterThan( $content, $title );
	}

	public function test_sku_exact_match_is_boosted() {
		$score = Oli_AS_Relevance::score( array( 'ab100' ), array( 'sku' => array( 'ab100' ) ) );
		$this->assertGreaterThan( 40, $score );
	}

	public function test_prefix_match_is_weaker_than_exact() {
		$exact  = Oli_AS_Relevance::apply_kind( 10, 'exact' );
		$prefix = Oli_AS_Relevance::apply_kind( 10, 'prefix' );
		$fuzzy  = Oli_AS_Relevance::apply_kind( 10, 'fuzzy' );
		$this->assertGreaterThan( $prefix, $exact );
		$this->assertGreaterThan( $fuzzy, $prefix );
	}

	public function test_fuzzy_match_allows_one_typo() {
		$this->assertTrue( Oli_AS_Relevance::is_fuzzy_match( 'hoodie', 'hoodee' ) );
		$this->assertTrue( Oli_AS_Relevance::is_fuzzy_match( 'shirt', 'shirt' ) );
		$this->assertFalse( Oli_AS_Relevance::is_fuzzy_match( 'hat', 'hot' ) );
	}

	public function test_all_tokens_coverage_bonus() {
		$partial = Oli_AS_Relevance::score(
			array( 'blue', 'shirt' ),
			array( 'title' => array( 'blue' ) )
		);
		$full    = Oli_AS_Relevance::score(
			array( 'blue', 'shirt' ),
			array( 'title' => array( 'blue', 'shirt' ) )
		);
		$this->assertGreaterThan( $partial, $full );
	}

	public function test_levenshtein_distance() {
		$this->assertSame( 0, Oli_AS_Relevance::distance( 'abc', 'abc' ) );
		$this->assertSame( 1, Oli_AS_Relevance::distance( 'abcd', 'abce' ) );
		$this->assertGreaterThan( 2, Oli_AS_Relevance::distance( 'short', 'completely', 2 ) );
	}
}
