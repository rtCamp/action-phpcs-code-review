<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class VipgociExitStatusTest extends TestCase {
	/**
	 * @covers ::vipgoci_exit_status
	 */
	public function testExitStatus1() {
		$exit_status = vipgoci_exit_status(
			array(
				'stats'	=> array(
					'lint'	=> array(
						25	=> array(
							'error'	=> 0,
						)
					)
				)
			)
		);

		$this->assertSame(
			0,
			$exit_status
		);
	}

	/**
	 * @covers ::vipgoci_exit_status
	 */
	public function testExitStatus2() {
		$exit_status = vipgoci_exit_status(
			array(
				'stats'	=> array(
					'lint'	=> array(
						25	=> array(
							'error'	=> 30
						)
					)
				)
			)
		);

		$this->assertSame(
			250,
			$exit_status
		);
	}
}
