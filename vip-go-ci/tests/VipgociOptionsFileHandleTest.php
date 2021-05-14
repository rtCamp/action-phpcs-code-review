<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class VipgociOptionsFileHandleTest extends TestCase {
	/**
	 * @covers ::vipgoci_option_file_handle
	 */
	public function testOptionsFileHandle1() {
		$options = array(
		);

		$temp_file_name = vipgoci_save_temp_file(
			'my-test-file',
			'txt',
			'content'
		);

		vipgoci_option_file_handle(
			$options,
			'mytestoption',
			$temp_file_name
		);

		$this->assertSame(
			$options['mytestoption'],
			$temp_file_name
		);

		unset( $temp_file_name );
	}
}
