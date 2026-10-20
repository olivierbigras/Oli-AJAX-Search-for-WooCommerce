<?php
/**
 * In-memory index tests.
 *
 * @package Oli_Ajax_Search
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers Oli_AS_Index_Engine
 * @covers Oli_AS_Synonyms
 */
class IndexEngineTest extends TestCase {

	/**
	 * Builds a small catalog.
	 *
	 * @return Oli_AS_Index_Engine
	 */
	protected function catalog() {
		$index = new Oli_AS_Index_Engine();
		$index->add_document(
			Oli_AS_Index_Engine::make_key( 'product', 1, 'en' ),
			array(
				'type'     => 'product',
				'language' => 'en',
				'title'    => 'Blue Hoodie',
				'id'       => 1,
			),
			array(
				'title' => array( 'blue', 'hoodie' ),
				'sku'   => array( 'bh100', 'bh-100' ),
			)
		);
		$index->add_document(
			Oli_AS_Index_Engine::make_key( 'product', 2, 'en' ),
			array(
				'type'     => 'product',
				'language' => 'en',
				'title'    => 'Red Shirt',
				'id'       => 2,
			),
			array(
				'title' => array( 'red', 'shirt' ),
			)
		);
		$index->add_document(
			Oli_AS_Index_Engine::make_key( 'product', 1, 'fr_fr' ),
			array(
				'type'     => 'product',
				'language' => 'fr_fr',
				'title'    => 'Sweat bleu',
				'id'       => 1,
			),
			array(
				'title' => array( 'sweat', 'bleu' ),
			)
		);
		$index->add_document(
			Oli_AS_Index_Engine::make_key( 'category', 9, 'en' ),
			array(
				'type'     => 'category',
				'language' => 'en',
				'title'    => 'Hoodies',
				'id'       => 9,
			),
			array(
				'title' => array( 'hoodies' ),
			)
		);
		return $index;
	}

	public function test_exact_title_search() {
		$results = $this->catalog()->search( 'blue hoodie', array( 'language' => 'en' ) );
		$this->assertNotEmpty( $results );
		$this->assertSame( 1, $results[0]['id'] );
	}

	public function test_sku_search() {
		$results = $this->catalog()->search( 'bh100', array( 'language' => 'en' ) );
		$this->assertSame( 1, $results[0]['id'] );
	}

	public function test_language_isolation() {
		$en = $this->catalog()->search( 'bleu', array( 'language' => 'en' ) );
		$fr = $this->catalog()->search( 'bleu', array( 'language' => 'fr_fr' ) );
		$this->assertSame( array(), $en );
		$this->assertNotEmpty( $fr );
		$this->assertSame( 'Sweat bleu', $fr[0]['title'] );
	}

	public function test_type_filter() {
		$results = $this->catalog()->search(
			'hoodie',
			array(
				'language' => 'en',
				'types'    => array( 'category' ),
			)
		);
		$this->assertCount( 1, $results );
		$this->assertSame( 'category', $results[0]['type'] );
	}

	public function test_fuzzy_typo() {
		$results = $this->catalog()->search(
			'hoodee',
			array(
				'language' => 'en',
				'fuzzy'    => true,
			)
		);
		$this->assertNotEmpty( $results );
		$this->assertSame( 1, $results[0]['id'] );
	}

	public function test_fuzzy_level_off_skips_typos() {
		$results = $this->catalog()->search(
			'hoodee',
			array(
				'language'    => 'en',
				'fuzzy_level' => 'off',
			)
		);
		$this->assertSame( array(), $results );
	}

	public function test_fuzzy_level_soft_still_matches_hoodie() {
		$results = $this->catalog()->search(
			'hoodee',
			array(
				'language'    => 'en',
				'fuzzy_level' => 'soft',
			)
		);
		$this->assertNotEmpty( $results );
		$this->assertSame( 1, $results[0]['id'] );
	}

	public function test_synonym_expansion() {
		$results = $this->catalog()->search(
			'sweatshirt',
			array(
				'language' => 'en',
				'synonyms' => array( array( 'hoodie', 'sweatshirt' ) ),
			)
		);
		$this->assertNotEmpty( $results );
		$this->assertSame( 1, $results[0]['id'] );
	}

	public function test_remove_document() {
		$index = $this->catalog();
		$index->remove_document( Oli_AS_Index_Engine::make_key( 'product', 1, 'en' ) );
		$results = $index->search( 'hoodie', array( 'language' => 'en' ) );
		$ids     = array_column( $results, 'id' );
		$this->assertNotContains( 1, $ids );
	}

	public function test_synonym_parse() {
		$groups = Oli_AS_Synonyms::parse( "hoodie, sweatshirt\ntee, tshirt" );
		$this->assertCount( 2, $groups );
		$expanded = Oli_AS_Synonyms::expand( array( 'hoodie' ), $groups );
		$this->assertContains( 'sweatshirt', $expanded );
	}

	public function test_make_key() {
		$this->assertSame( 'product:12:fr_fr', Oli_AS_Index_Engine::make_key( 'product', 12, 'fr_fr' ) );
	}
}
