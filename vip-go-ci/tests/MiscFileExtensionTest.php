<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscFileExtensionTest extends TestCase {
	/**
	 * @covers ::vipgoci_file_extension_get
	 */
	public function testFileExtension1() {
		$file_name = 'myfile.exe';

		$file_extension = vipgoci_file_extension_get(
			$file_name
		);

		$this->assertSame(
			'exe',
			$file_extension
		);
	}

	/**
	 * @covers ::vipgoci_file_extension_get
	 */
	public function testFileExtension2() {
		$file_name = 'myfile.EXE';

		$file_extension = vipgoci_file_extension_get(
			$file_name
		);

		$this->assertSame(
			'exe',
			$file_extension
		);
	}

	/**
	 * @covers ::vipgoci_file_extension_get
	 */
	public function testFileExtension3() {
		$file_name = 'myfile';

		$file_extension = vipgoci_file_extension_get(
			$file_name
		);

		$this->assertSame(
			null,
			$file_extension
		);
	}
}
