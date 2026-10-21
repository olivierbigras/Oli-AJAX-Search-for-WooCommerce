<?php
/**
 * Rate limiter tests.
 *
 * @package Oli_Ajax_Search
 */

/**
 * Tests IP hashing without WordPress transients.
 */
class RateLimitTest extends PHPUnit\Framework\TestCase {

	/**
	 * Hash is stable and does not contain the raw IP.
	 *
	 * @return void
	 */
	public function test_ip_hash_is_stable() {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		$one                    = Oli_AS_Rate_Limit::ip_hash();
		$two                    = Oli_AS_Rate_Limit::ip_hash();
		$this->assertSame( $one, $two );
		$this->assertSame( 16, strlen( $one ) );
		$this->assertStringNotContainsString( '203.0.113.10', $one );
	}
}
