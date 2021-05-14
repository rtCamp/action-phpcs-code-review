<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanGetSniffsForStandardTest extends TestCase {
	var $options_phpcs = array(
		'phpcs-path'		=> null,
		'phpcs-standard'	=> null,
		'phpcs-sniffs-existing'	=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->options_phpcs
		);

		$this->options_phpcs['phpcs-sniffs-existing'] = explode(
			',',
			$this->options_phpcs['phpcs-sniffs-existing']
		);
	}

	protected function tearDown(): void {
		$this->options_phpcs = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_get_sniffs_for_standard
	 */
	public function testDoScanTest1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options_phpcs,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$phpcs_sniffs = vipgoci_phpcs_get_sniffs_for_standard(
			$this->options_phpcs['phpcs-path'],
			$this->options_phpcs['phpcs-standard']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$this->options_phpcs['phpcs-sniffs-existing']
		);

		$this->assertNotEmpty(
			$phpcs_sniffs
		);

		foreach(
			$this->options_phpcs['phpcs-sniffs-existing']
				as $sniff_name
		) {
			$this->assertNotFalse(
				in_array(
					$sniff_name,
					$phpcs_sniffs,
					true
				)
			);
		}
	}
}
