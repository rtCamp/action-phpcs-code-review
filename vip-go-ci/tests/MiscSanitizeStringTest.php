<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscSanitizeStringTest extends TestCase {
	/**
	 * @covers ::vipgoci_sanitize_string
	 */
	public function testSanitizeString1() {
		$this->assertSame(
			'foobar',
			vipgoci_sanitize_string(
				'FooBar'
			)
		);

		$this->assertSame(
			'foobar',
			vipgoci_sanitize_string(
				'   FooBar   '
			)
		);
	}
}
