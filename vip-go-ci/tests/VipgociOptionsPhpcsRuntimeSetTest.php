<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class VipgociOptionsPhpcsRuntimeSetTest extends TestCase {
	/**
	 * @covers ::vipgoci_option_phpcs_runtime_set
	 */
	public function testOptionsPhpcsRuntimeSet1() {
		$this->options = array(
			'myphpcsruntimeoption' => 'testVersion 7.4-,allowUnusedVariablesBeforeRequire true,allowUndefinedVariablesInFileScope false',
			'other-option1' => '123 456',
			'other-option2' => array(
				'1',
				'2',
			)
		);

		vipgoci_option_phpcs_runtime_set(
			$this->options,
			'myphpcsruntimeoption',
		);

		$this->assertSame(
			array(
				'myphpcsruntimeoption' => array(
					array(
						'testVersion',
						'7.4-'
					),
					array(
						'allowUnusedVariablesBeforeRequire',
						'true',
					),
					array(
						'allowUndefinedVariablesInFileScope',
						'false'
					),
				),
				'other-option1' => '123 456',
				'other-option2' => array(
					'1',
					'2',
				)
			),
			$this->options
		);

		unset(
			$this->options
		);
	}
}
