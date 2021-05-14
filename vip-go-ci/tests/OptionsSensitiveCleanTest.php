<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class OptionsSensitiveCleanTest extends TestCase {
	/**
	 * @covers ::vipgoci_options_sensitive_clean
	 */
	public function testSensitiveClean1 () {
		$options = array(
			'a1'	=> 'secret',
			'b1'	=> 'notsecret',
			'c1'	=> 'secret',
			'd1'	=> 'secret',
			'e1'	=> 'notsecret',
			'f1'	=> 'notsecret',
		);

		$options_clean = vipgoci_options_sensitive_clean(
			$options
		);

		/*
		 * No options have been registered for
		 * cleaning, should remain unchanged.
		 */		

		$this->assertSame(
			$options,
			$options_clean
		);

		/*
		 * Register two options for cleaning,
		 * those should be cleaned, but one 'secret'
		 * options should remain unchanged.
		 */
		vipgoci_options_sensitive_clean(
			null,
			array(
				'a1',
				'c1',
			)
		);

		$options_clean = vipgoci_options_sensitive_clean(
			$options
		);

		$this->assertSame(
			array(
				'a1'	=> '***',
				'b1'	=> 'notsecret',
				'c1'	=> '***',
				'd1'	=> 'secret',
				'e1'	=> 'notsecret',
				'f1'	=> 'notsecret',
			),
			$options_clean
		);

		/*
		 * Add one more yet, so all
		 * 'secret' options should be cleaned now.
		 */

		vipgoci_options_sensitive_clean(
			null,
			array(
				'd1'
			)
		);

		$options_clean = vipgoci_options_sensitive_clean(
			$options
		);

		$this->assertSame(
			array(
				'a1'	=> '***',
				'b1'	=> 'notsecret',
				'c1'	=> '***',
				'd1'	=> '***',
				'e1'	=> 'notsecret',
				'f1'	=> 'notsecret',
			),
			$options_clean
		);
	}
}
