<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanGetAllStandardsTest extends TestCase {
	var $options_phpcs = array(
		'phpcs-path'		=> null,
		'phpcs-standard'	=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->options_phpcs
		);
	}

	protected function tearDown(): void {
		$this->options_phpcs = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_get_all_standards
	 */
	public function testGetAllStandardsTest1() {
		$all_standards = vipgoci_phpcs_get_all_standards(
			$this->options_phpcs['phpcs-path']
		);

		$this->assertNotEmpty(
			$all_standards
		);

		$this->assertNotFalse(
			array_search(
				$this->options_phpcs['phpcs-standard'],
				$all_standards
			)
		);
	}
}
