<?php
/**
 * Visibility helper tests.
 *
 * @package Oli_Ajax_Search
 */

/**
 * Tests searchable-product guards with stubs.
 */
class VisibilityTest extends PHPUnit\Framework\TestCase {

	/**
	 * Rejects empty products.
	 *
	 * @return void
	 */
	public function test_rejects_empty_product() {
		$this->assertFalse( Oli_AS_Visibility::is_searchable_product( null ) );
		$this->assertFalse( Oli_AS_Visibility::is_searchable_product( false ) );
	}

	/**
	 * Rejects unpublished products.
	 *
	 * @return void
	 */
	public function test_rejects_draft_product() {
		$product = new class() {
			/**
			 * Status.
			 *
			 * @return string
			 */
			public function get_status() {
				return 'draft';
			}
		};

		$this->assertFalse( Oli_AS_Visibility::is_searchable_product( $product ) );
	}

	/**
	 * Rejects catalog-only products.
	 *
	 * @return void
	 */
	public function test_rejects_catalog_only_product() {
		$product = new class() {
			/**
			 * Status.
			 *
			 * @return string
			 */
			public function get_status() {
				return 'publish';
			}

			/**
			 * Catalog visibility.
			 *
			 * @return string
			 */
			public function get_catalog_visibility() {
				return 'catalog';
			}
		};

		$this->assertFalse( Oli_AS_Visibility::is_searchable_product( $product ) );
	}

	/**
	 * Accepts search-visible published products.
	 *
	 * @return void
	 */
	public function test_accepts_visible_product() {
		$product = new class() {
			/**
			 * Status.
			 *
			 * @return string
			 */
			public function get_status() {
				return 'publish';
			}

			/**
			 * Catalog visibility.
			 *
			 * @return string
			 */
			public function get_catalog_visibility() {
				return 'visible';
			}
		};

		$this->assertTrue( Oli_AS_Visibility::is_searchable_product( $product ) );
	}
}
