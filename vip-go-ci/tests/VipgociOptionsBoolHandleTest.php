<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class VipgociOptionsBoolHandleTest extends TestCase {
	/**
	 * @covers ::vipgoci_option_bool_handle
	 */
	public function testOptionsBoolHandle1() {
		$options = array(
		);

		vipgoci_option_bool_handle(
			$options,
			'mytestoption',
			'false'
		);

		$this->assertSame(
			false,
			$options['mytestoption']
		);
	}
	/**
	 * @covers ::vipgoci_option_bool_handle
	 */
	public function testOptionsBoolHandle2() {
		$options = array(
			'mytestoption' => 'false',
		);

		vipgoci_option_bool_handle(
			$options,
			'mytestoption',
			false
		);

		$this->assertSame(
			false,
			$options['mytestoption']
		);
	}

	/**
	 * @covers ::vipgoci_option_bool_handle
	 */
	public function testOptionsBoolHandle3() {
		$options = array(
			'mytestoption' => 'true',
		);

		vipgoci_option_bool_handle(
			$options,
			'mytestoption',
			true
		);

		$this->assertSame(
			true,
			$options['mytestoption']
		);
	}
}
