<?php
/**
 * Language helper tests.
 *
 * @package Oli_Ajax_Search
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers Oli_AS_Language
 */
class LanguageTest extends TestCase {

	public function test_same_language_matches_slug_and_locale() {
		$language = Oli_AS_Language::instance();
		$this->assertTrue( $language->same_language( 'fr', 'fr_FR' ) );
		$this->assertTrue( $language->same_language( 'fr_fr', 'fr' ) );
		$this->assertTrue( $language->same_language( 'en_US', 'en' ) );
		$this->assertFalse( $language->same_language( 'fr', 'en' ) );
		$this->assertFalse( $language->same_language( 'default', 'en' ) );
	}

	public function test_normalize_code_lowercases_and_strips() {
		$language = Oli_AS_Language::instance();
		$this->assertSame( 'fr_fr', $language->normalize_code( 'fr-FR' ) );
		$this->assertSame( 'en', $language->normalize_code( 'en' ) );
	}
}
