<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscSanitizePathPrefixTest extends TestCase {
	/**
	 * @covers ::vipgoci_sanitize_path_prefix
	 */
	public function testSanitizePathPrefix1() {
		$path = vipgoci_sanitize_path_prefix(
			'a/folder1',
			array( 'a/' )
		);

		$this->assertSame(
			'folder1',
			$path
		);
	}

	/**
	 * @covers ::vipgoci_sanitize_path_prefix
	 */
	public function testSanitizePathPrefix2() {
		$path = vipgoci_sanitize_path_prefix(
			'a/b/folder1',
			array( 'a/', 'b/' )
		);

		$this->assertSame(
			'b/folder1',
			$path
		);
	}

	/**
	 * @covers ::vipgoci_sanitize_path_prefix
	 */
	public function testSanitizePathPrefix3() {
		$path = vipgoci_sanitize_path_prefix(
			'a/folder1',
			array( 'b/' )
		);

		$this->assertSame(
			'a/folder1',
			$path
		);
	}
}
