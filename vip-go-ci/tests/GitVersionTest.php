<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitVersionTest extends TestCase {
	/**
	 * Get git version, ensure the returned value
	 * is as expected.
	 *
	 * @covers ::vipgoci_git_version
	 */
	public function testVersion1() {
		$git_version = vipgoci_git_version();

		$this->assertSame(
			0,
			preg_match(
				'/[a-zA-Z, ]/',
				$git_version
			)
		);
	}
}
