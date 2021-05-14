<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class LintLintDoScanTest extends TestCase {
	var $options_php = array(
		'php-path'	=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'lint-scan',
			$this->options_php
		);
	}

	protected function tearDown(): void {
		$this->options_php = null;
	}

	/**
	 * @covers ::vipgoci_lint_do_scan_file
	 */
	public function testLintDoScan1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options_php,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$php_file_path = vipgoci_save_temp_file(
			'test-lint-do-scan-1',
			'php',
			'<?php ' . PHP_EOL . 'echo "foo";' . PHP_EOL
		);

		$php_file_name = pathinfo(
			$php_file_path,
			PATHINFO_FILENAME
		);

		vipgoci_unittests_output_suppress();

		$ret = vipgoci_lint_do_scan_file(
			$this->options_php['php-path'],
			$php_file_path
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'No syntax errors detected in ' . $php_file_path
			),
			$ret
		);
	}

	/**
	 * @covers ::vipgoci_lint_do_scan_file
	 */
	public function testLintDoScan2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options_php,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$php_file_path = vipgoci_save_temp_file(
			'test-lint-do-scan-2',
			'php',
			'<?php ' . PHP_EOL . 'echo "foo"' . PHP_EOL
		);

		$php_file_name = pathinfo(
			$php_file_path,
			PATHINFO_FILENAME
		);

		vipgoci_unittests_output_suppress();

		$ret = vipgoci_lint_do_scan_file(
			$this->options_php['php-path'],
			$php_file_path
		);

		vipgoci_unittests_output_unsuppress();


		$ret[0] = vipgoci_unittests_php_syntax_error_compat(
			$ret[0]
		);

		$this->assertSame(
			array(
				"PHP Parse error:  syntax error, unexpected end of file, expecting ',' or ';' in " . $php_file_path . " on line 3",
				'Errors parsing ' . $php_file_path
			),
			$ret
		);
	}
}
