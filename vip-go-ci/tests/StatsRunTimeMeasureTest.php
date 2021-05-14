<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class StatsRunTimeMeasureTest extends TestCase {
	/**
	 * @covers ::vipgoci_runtime_measure
	 */
	function testRuntimeMeasure1() {
		return $this->assertSame(
			false,
			vipgoci_runtime_measure( 'illegalaction', 'mytimer1' )
		);
	}

	/**
	 * @covers ::vipgoci_runtime_measure
	 */
	function testRuntimeMeasure2() {
		return $this->assertSame(
			false,
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'mytimer2' )
		);
	}

	/**
	 * @covers ::vipgoci_runtime_measure
	 */
	function testRuntimeMeasure3() {
		$this->assertSame(
			true,
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'mytimer3' )
		);

		sleep( 2 );

		$this->assertGreaterThanOrEqual(
			1,
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'mytimer3' )
		);
	
		$runtime_stats = vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_DUMP
		);

		$this->assertGreaterThanOrEqual(
			1,
			$runtime_stats[ 'mytimer3' ]
		);
	}

	/**
	 * @covers ::vipgoci_runtime_measure
	 */
	function testRuntimeMeasure4() {
		$this->assertSame(
			true,
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'mytimer4' )
		);

		sleep( 2 );

		$runtime_stats = vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_DUMP
		);

		$this->assertGreaterThanOrEqual(
			1,
			$runtime_stats[ 'mytimer3' ]
		);

	}
}

