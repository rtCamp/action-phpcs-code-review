<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class VipgociOptionsArrayHandleTest extends TestCase {
	/**
	 * @covers ::vipgoci_option_array_handle
	 */
	public function testOptionsArrayHandle1() {
		$options = array(
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			array( 'myvalue' ),
			null,
			','
		);

		$this->assertSame(
			array(
				'myvalue',
			),
			$options['mytestoption']
		);
	}

	/**
	 * @covers ::vipgoci_option_array_handle
	 */
	public function testOptionsArrayHandle2() {
		$options = array(
			'mytestoption' => 'myvalue1,myvalue2,myvalue3',
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			'myvalue',
			null,
			','
		);

		$this->assertSame(
			array(
				'myvalue1',
				'myvalue2',
				'myvalue3',
			),
			$options['mytestoption']
		);
	}

	/**
	 * @covers ::vipgoci_option_array_handle
	 */
	public function testOptionsArrayHandle3() {
		$options = array(
			'mytestoption' => 'myvalue1,myvalue2,MYVALUE3',
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			'myvalue',
			null,
			','
		);

		$this->assertSame(
			array(
				'myvalue1',
				'myvalue2',
				'myvalue3', // should be transformed to lower-case by default
			),
			$options['mytestoption']
		);
	}

	/**
	 * @covers ::vipgoci_option_array_handle
	 */
	public function testOptionsArrayHandle4() {
		$options = array(
			'mytestoption' => 'myvalue1,myvalue2,MYVALUE3',
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			'myvalue',
			null,
			',',
			false // do not strtolower()
		);

		$this->assertSame(
			array(
				'myvalue1',
				'myvalue2',
				'MYVALUE3',
			),
			$options['mytestoption']
		);
	}
}
