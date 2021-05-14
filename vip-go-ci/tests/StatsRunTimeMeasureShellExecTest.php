<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class StatsRunTimeMeasureShellExecTest extends TestCase {
	/**
	 * @covers ::vipgoci_runtime_measure_shell_exec
	 */
	function testRuntimeMeasure1() {
		vipgoci_runtime_measure_shell_exec(
			'sleep 1',
			'mytimer10'
		);

		$this->assertSame(
			'test_string',
			vipgoci_runtime_measure_shell_exec( 'echo -n "test_string"', 'mytimer10' )
		);
	
		$runtime_stats = vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_DUMP
		);

		$this->assertGreaterThanOrEqual(
			1,
			$runtime_stats[ 'mytimer10' ]
		);
	}
}

