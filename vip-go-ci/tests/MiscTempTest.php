<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscTempTest extends TestCase {
	/**
	 * @covers ::vipgoci_save_temp_file
	 */
	public function testTempFile1() {
		$file_name_prefix = 'myfilename1';
		$file_name_extension = null;
		$file_contents = 'mycontentsofthefile1' . PHP_EOL;

		$temp_file_name = vipgoci_save_temp_file(
			$file_name_prefix,
			$file_name_extension,
			$file_contents
		);

		$this->assertNotEquals(
			false,
			$temp_file_name
		);

		
		$temp_file_extension = pathinfo(
			$temp_file_name,
			PATHINFO_EXTENSION
		);

		$temp_file_contents = file_get_contents(
			$temp_file_name
		);

		$this->assertSame(
			'',
			$temp_file_extension
		);

		$this->assertSame(
			$file_contents,
			$temp_file_contents
		);

		unlink( $temp_file_name );
	}

	/**
	 * @covers ::vipgoci_save_temp_file
	 */
	public function testTempFile2() {
		$file_name_prefix = 'myfilename2';
		$file_name_extension = '';
		$file_contents = 'mycontentsofthefile2' . PHP_EOL;

		$temp_file_name = vipgoci_save_temp_file(
			$file_name_prefix,
			$file_name_extension,
			$file_contents
		);

		$this->assertNotEquals(
			false,
			$temp_file_name
		);

		
		$temp_file_extension = pathinfo(
			$temp_file_name,
			PATHINFO_EXTENSION
		);

		$temp_file_contents = file_get_contents(
			$temp_file_name
		);

		$this->assertSame(
			$file_name_extension,
			$temp_file_extension
		);

		$this->assertSame(
			$file_contents,
			$temp_file_contents
		);

		unlink( $temp_file_name );
	}


	/**
	 * @covers ::vipgoci_save_temp_file
	 */
	public function testTempFile3() {
		$file_name_prefix = 'myfilename3';
		$file_name_extension = 'txt';
		$file_contents = 'mycontentsofthefile3' . PHP_EOL;

		$temp_file_name = vipgoci_save_temp_file(
			$file_name_prefix,
			$file_name_extension,
			$file_contents
		);

		$this->assertNotEquals(
			false,
			$temp_file_name
		);

		
		$temp_file_extension = pathinfo(
			$temp_file_name,
			PATHINFO_EXTENSION
		);

		$temp_file_contents = file_get_contents(
			$temp_file_name
		);

		$this->assertSame(
			$file_name_extension,
			$temp_file_extension
		);

		$this->assertSame(
			$file_contents,
			$temp_file_contents
		);

		unlink( $temp_file_name );
	}
}
