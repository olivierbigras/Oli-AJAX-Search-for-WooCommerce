<?php
/**
 * Settings helper tests.
 *
 * @package Oli_Ajax_Search
 */

/**
 * Tests ID lists and fuzzy helpers.
 */
class SettingsParseTest extends PHPUnit\Framework\TestCase {

	/**
	 * Parses mixed separators.
	 *
	 * @return void
	 */
	public function test_parse_id_list() {
		$ids = Oli_AS_Settings::parse_id_list( '12, 0, 8 8, 4' );
		$this->assertSame( array( 12, 8, 4 ), $ids );
	}

	/**
	 * Default fuzzy level is normal.
	 *
	 * @return void
	 */
	public function test_defaults_include_original_labels() {
		$defaults = Oli_AS_Settings::defaults();
		$this->assertSame( 3, $defaults['min_chars'] );
		$this->assertSame( 'oval', $defaults['search_style'] );
		$this->assertSame( 'classic', $defaults['layout'] );
		$this->assertSame( 'normal', $defaults['fuzzy_level'] );
		$this->assertSame( 'Search for products...', $defaults['placeholder'] );
	}

	/**
	 * Hours cover a full day.
	 *
	 * @return void
	 */
	public function test_hours() {
		$hours = Oli_AS_Settings::hours();
		$this->assertCount( 24, $hours );
		$this->assertSame( '03:00', $hours[3] );
	}
}
