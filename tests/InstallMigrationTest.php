<?php
/**
 * Option-migration planner tests.
 *
 * @package Oli_Ajax_Search
 */

/**
 * Verifies legacy options are copied then dropped.
 */
class InstallMigrationTest extends PHPUnit\Framework\TestCase {

	/**
	 * Copies an old key onto a missing new key and marks the old key for deletion.
	 *
	 * @return void
	 */
	public function test_copies_missing_new_option_then_deletes_old() {
		$plan = Oli_AS_Install::plan_option_migration(
			array(
				'legacy_settings' => array( 'min_chars' => 4 ),
				'oli_as_settings' => false,
			),
			array(
				'legacy_settings' => 'oli_as_settings',
			)
		);

		$this->assertSame( array( 'min_chars' => 4 ), $plan['keep']['oli_as_settings'] );
		$this->assertSame( array( 'legacy_settings' ), $plan['delete'] );
		$this->assertArrayNotHasKey( 'legacy_settings', $plan['keep'] );
	}

	/**
	 * Keeps an existing new option and still deletes the leftover old key.
	 *
	 * @return void
	 */
	public function test_does_not_overwrite_existing_new_option() {
		$plan = Oli_AS_Install::plan_option_migration(
			array(
				'legacy_settings' => array( 'min_chars' => 9 ),
				'oli_as_settings' => array( 'min_chars' => 3 ),
			),
			array(
				'legacy_settings' => 'oli_as_settings',
			)
		);

		$this->assertSame( array( 'min_chars' => 3 ), $plan['keep']['oli_as_settings'] );
		$this->assertSame( array( 'legacy_settings' ), $plan['delete'] );
	}

	/**
	 * Current prefix map is empty because 0.1.x already used oli_as_.
	 *
	 * @return void
	 */
	public function test_current_map_is_empty() {
		$this->assertSame( array(), Oli_AS_Install::legacy_option_map() );
	}
}
