<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class LintLintGetIssuesTest extends TestCase {
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
	 * @covers ::vipgoci_lint_parse_results
	 */
	public function testLintGetIssues1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options_php,
			array(  ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$php_file_path = vipgoci_save_temp_file(
			'test-lint-get-issues-1',
			'php',
			'<?php ' . PHP_EOL . 'echo "foo";' . PHP_EOL
		);

		$php_file_name = pathinfo(
			$php_file_path,
			PATHINFO_FILENAME
		);

		vipgoci_unittests_output_suppress();

		$lint_issues = vipgoci_lint_do_scan_file(
			$this->options_php['php-path'],
			$php_file_path
		);

		$lint_issues_parsed = vipgoci_lint_parse_results(
			$php_file_name,
			$php_file_name,
			$lint_issues
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
			),
			$lint_issues_parsed
		);
	}

	/**
	 * @covers ::vipgoci_lint_parse_results
	 */
	public function testLintDoScan2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options_php,
			array(  ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$php_file_path = vipgoci_save_temp_file(
			'test-lint-get-issues-2',
			'php',
			'<?php ' . PHP_EOL . 'echo "foo"' . PHP_EOL
		);

		$php_file_name = pathinfo(
			$php_file_path,
			PATHINFO_FILENAME
		);

		vipgoci_unittests_output_suppress();

		$lint_issues = vipgoci_lint_do_scan_file(
			$this->options_php['php-path'],
			$php_file_path
		);

		$lint_issues_parsed = vipgoci_lint_parse_results(
			'php-file-name.php',
			$php_file_path,
			$lint_issues
		);

		vipgoci_unittests_output_unsuppress();

		/* Fix PHP compatibility issue */
		$lint_issues_parsed[3][0]['message'] = 
			vipgoci_unittests_php_syntax_error_compat(
				$lint_issues_parsed[3][0]['message']
			);

		$this->assertSame(
			array(
				3 => array(
					array(
						'message' 	=> "syntax error, unexpected end of file, expecting ',' or ';'",
						'level'		=> 'ERROR',
						'severity'	=> 5,
					)
				)
			),
			$lint_issues_parsed
		);
	}
}
