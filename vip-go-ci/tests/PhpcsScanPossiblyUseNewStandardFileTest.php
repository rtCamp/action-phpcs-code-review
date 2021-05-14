<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanPossiblyUseNewStandardFileTest extends TestCase {
	protected function setUp(): void {
		$this->original_standard = array( 'WordPress-VIP-Go' );

		$this->options = array();
		$this->options['phpcs-standard'] = $this->original_standard;
		$this->options['phpcs-standard-file'] = false;
		$this->options['phpcs-sniffs-include'] = array();
	}

	protected function tearDown(): void {
		if (
			( true === $this->options['phpcs-standard-file'] )
			&&
			( file_exists(
				$this->options['phpcs-standard'][0]
			) )
		) {
			unlink(
				$this->options['phpcs-standard'][0]
			);
		}

		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_possibly_use_new_standard_file
	 */
	public function testDoNotUseNewstandardFileTest() {
		$this->assertEmpty(
			$this->options['phpcs-sniffs-include']
		);

		vipgoci_phpcs_possibly_use_new_standard_file(
			$this->options
		);

		$this->assertFalse(
			$this->options['phpcs-standard-file']
		);

		$this->assertSame(
			$this->original_standard,
			$this->options['phpcs-standard']
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_possibly_use_new_standard_file
	 */
	public function testDoUseNewstandardFileTest() {
		$this->options['phpcs-sniffs-include'] = array(
			'WordPress.DB.RestrictedFunctions'
		);

		vipgoci_phpcs_possibly_use_new_standard_file(
			$this->options
		);

		$this->assertTrue(
			$this->options['phpcs-standard-file']
		);

		$this->assertNotEquals(
			$this->original_standard,
			$this->options['phpcs-standard']
		);

		$this->assertTrue(
			file_exists(
				$this->options['phpcs-standard'][0]
			)
		);
	}
}
