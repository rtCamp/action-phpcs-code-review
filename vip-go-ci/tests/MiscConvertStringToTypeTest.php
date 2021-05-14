<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscConvertStringToTypeTest extends TestCase {
	/**
	 * @covers ::vipgoci_convert_string_to_type
	 */
	public function testConvert1() {
		$this->assertSame(
			true,
			vipgoci_convert_string_to_type('true')
		);

		$this->assertSame(
			false,
			vipgoci_convert_string_to_type('false')
		);

		$this->assertSame(
			null,
			vipgoci_convert_string_to_type('null')
		);

		$this->assertSame(
			'somestring',
			vipgoci_convert_string_to_type('somestring')
		);
	}
}
