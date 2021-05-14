<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscCacheTest extends TestCase {
	/**
	 * @covers ::vipgoci_cache
	 */
	public function testCache1() {
		$cache_id1 =
			__CLASS__ .
			'_' . 
			__FUNCTION__ .
			'_mytest1';

		$cache_id2 =
			__CLASS__ .
			'_' . 
			__FUNCTION__ .
			'_mytest2';

		$r1 = openssl_random_pseudo_bytes(
			100
		);

		$r2 = $r1 . $r1;

		vipgoci_cache(
			$cache_id1,
			$r1
		);

		vipgoci_cache(
			$cache_id2,
			$r2
		);

		$r1_retrieved = vipgoci_cache(
			$cache_id1
		);

		$r2_retrieved = vipgoci_cache(
			$cache_id2
		);

		$this->assertSame(
			$r1,
			$r1_retrieved
		);

		$this->assertSame(
			$r2,
			$r2_retrieved
		);


		$this->assertNotEquals(
			$r1,
			$r2
		);

		$this->assertNotEquals(
			$r1_retrieved,
			$r2_retrieved
		);
	}

	/**
	 * Test clearing of cache.
	 *
	 * @covers ::vipgoci_cache
	 */
	public function testCache2() {
		$cache_id =
			__CLASS__ .
			'_' . 
			__FUNCTION__;

		/*
		 * Clear cache.
		 */
		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		/*
		 * Cache something,
		 * be sure it cached properly.
		 */
		vipgoci_cache(
			$cache_id,
			'mytext'
		);

		$cached_data = vipgoci_cache(
			$cache_id
		);

		$this->assertSame(
			'mytext',
			$cached_data
		);

		/*
		 * Clear the cache,
		 * make sure it cleared.
		 */
		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		$cached_data = vipgoci_cache(
			$cache_id
		);

		$this->assertSame(
			false,
			$cached_data
		);
	}
}
