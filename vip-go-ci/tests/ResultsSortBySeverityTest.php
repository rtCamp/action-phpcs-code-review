<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ResultsSortBySeverityTest extends TestCase {
	protected function setUp(): void {
		$this->results = array(
			'issues' => array(
				24 => array(
					array(
						"type"		=> "phpcs",
						"file_name"	=> "testfile1.php",
						"file_line"	=> 100,
						"issue"		=> array(
							"message"	=> "Incorrect usage of function other_foo()",
							"source"	=> "RandomStandard.OtherSniff.random_function",
							"severity"	=> 100,
							"fixable"	=> false,
							"type"		=> "INFO",
							"line"		=> 100,
							"column"	=> 1,
							"level"		=> "INFO"
						)
					),
					array(
						"type"		=> "phpcs",
						"file_name"	=> "testfile1.php",
						"file_line"	=> 3,
						"issue"		=> array(
							"message"	=> "Incorrect usage of function foo()",
							"source"	=>  "RandomStandard.RandomSniff.random_function",
							"severity"	=> 3,
							"fixable"	=> false,
							"type"		=> "INFO",
							"line"		=> 3,
							"column"	=> 1,
							"level"		=> "INFO"
						)
					),
					array(
						"type"		=> "phpcs",
						"file_name"	=> "testfile1.php",
						"file_line"	=> 2,
						"issue"		=> array(
							"message"	=> "Incorrect usage of function foo()",
							"source"	=> "RandomStandard.RandomSniff.random_function",
							"severity"	=> 40,
							"fixable"	=> false,
							"type"		=> "INFO",
							"line"		=> 2,
							"column"	=> 1,
							"level"		=> "INFO"
						)
					),
					array(
						"type"		=> "phpcs",
						"file_name"	=> "testfile2.php",
						"file_line"	=> 100,
						"issue"		=> array(
							"message"	=> "Incorrect usage of function foo()",
							"source"	=> "RandomStandard.RandomSniff.random_function",
							"severity"	=> 101,
							"fixable"	=> false,
							"type"		=> "INFO",
							"line"		=> 100,
							"column"	=> 1,
							"level"		=> "INFO"
						)
					),
					array(
						"type"		=> "phpcs",
						"file_name"	=> "testfile2.php",
						"file_line"	=> 100,
						"issue"		=> array(
							"message"	=> "Incorrect usage of function foo()",
							"source"	=> "RandomStandard.RandomSniff.random_function",
							"severity"	=> 37,
							"fixable"	=> false,
							"type"		=> "INFO",
							"line"		=> 100,
							"column"	=> 1,
							"level"		=> "INFO"
						)
					),
				),

				7 => array(
					array(
						"type"		=> "phpcs",
						"file_name"	=> "testfile2.php",
						"file_line"	=> 100,
						"issue"		=> array(
							"message"	=> "Incorrect usage of function foo()",
							"source"	=> "RandomStandard.RandomSniff.random_function",
							"severity"	=> 7,
							"fixable"	=> false,
							"type"		=> "INFO",
							"line"		=> 100,
							"column"	=> 1,
							"level"		=> "INFO"
						)
					),
					array(
						"type"		=> "phpcs",
						"file_name"	=> "testfile2.php",
						"file_line"	=> 100,
						"issue"		=> array(
							"message"	=> "Incorrect usage of function testfoo()",
							"source"	=> "RandomStandard.RandomSniff.test_function",
							"severity"	=> 200,
							"fixable"	=> false,
							"type"		=> "INFO",
							"line"		=> 100,
							"column"	=> 1,
							"level"		=> "INFO"
						)
					),
					array(
						"type"		=> "phpcs",
						"file_name"	=> "testfile2.php",
						"file_line"	=> 100,
						"issue"		=> array(
							"message"	=> "Incorrect usage of function myfoo()",
							"source"	=> "RandomStandard.RandomSniff.myfoo_function",
							"severity"	=> 377,
							"fixable"	=> false,
							"type"		=> "INFO",
							"line"		=> 100,
							"column"	=> 1,
							"level"		=> "INFO"
						)
					),
				),
			)
		);
	}

	protected function tearDown(): void {
		$this->options = null;
		$this->results = null;
	}

	/**
	 * @covers ::vipgoci_results_sort_by_severity
	 */
	public function testSortingNotConfigured() {
		$this->options['results-comments-sort'] = false;
		$this->results_before = $this->results;

		vipgoci_unittests_output_suppress();

		vipgoci_results_sort_by_severity(
			$this->options,
			$this->results
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$this->results_before
		);

		// Not configured to sort, should remain unchanged
		$this->assertSame(
			$this->results_before,
			$this->results
		);
	}

	/**
	 * @covers ::vipgoci_results_sort_by_severity
	 */
	public function testSortingCorrect1() {
		$this->options['results-comments-sort'] = true;

		vipgoci_unittests_output_suppress();

		vipgoci_results_sort_by_severity(
			$this->options,
			$this->results
		);

		vipgoci_unittests_output_unsuppress();

		// Configured to sort, should be changed
		$this->assertSame(
			array(
				'issues' => array(
					24 => array(
						array(
							"type"		=> "phpcs",
							"file_name"	=> "testfile2.php",
							"file_line"	=> 100,
							"issue"		=> array(
								"message"	=> "Incorrect usage of function foo()",
								"source"	=> "RandomStandard.RandomSniff.random_function",
								"severity"	=> 101,
								"fixable"	=> false,
								"type"		=> "INFO",
								"line"		=> 100,
								"column"	=> 1,
								"level"		=> "INFO"
							)
						),
						array(
							"type"		=> "phpcs",
							"file_name"	=> "testfile1.php",
							"file_line"	=> 100,
							"issue"		=> array(
								"message"	=> "Incorrect usage of function other_foo()",
								"source"	=> "RandomStandard.OtherSniff.random_function",
								"severity"	=> 100,
								"fixable"	=> false,
								"type"		=> "INFO",
								"line"		=> 100,
								"column"	=> 1,
								"level"		=> "INFO"
							)
						),
						array(
							"type"		=> "phpcs",
							"file_name"	=> "testfile1.php",
							"file_line"	=> 2,
							"issue"		=> array(
								"message"	=> "Incorrect usage of function foo()",
								"source"	=> "RandomStandard.RandomSniff.random_function",
								"severity"	=> 40,
								"fixable"	=> false,
								"type"		=> "INFO",
								"line"		=> 2,
								"column"	=> 1,
								"level"		=> "INFO"
							)
						),
						array(
							"type"		=> "phpcs",
							"file_name"	=> "testfile2.php",
							"file_line"	=> 100,
							"issue"		=> array(
								"message"	=> "Incorrect usage of function foo()",
								"source"	=> "RandomStandard.RandomSniff.random_function",
								"severity"	=> 37,
								"fixable"	=> false,
								"type"		=> "INFO",
								"line"		=> 100,
								"column"	=> 1,
								"level"		=> "INFO"
							)
						),
						array(
							"type"		=> "phpcs",
							"file_name"	=> "testfile1.php",
							"file_line"	=> 3,
							"issue"		=> array(
								"message"	=> "Incorrect usage of function foo()",
								"source"	=>  "RandomStandard.RandomSniff.random_function",
								"severity"	=> 3,
								"fixable"	=> false,
								"type"		=> "INFO",
								"line"		=> 3,
								"column"	=> 1,
								"level"		=> "INFO"
							)
						),
					),
	
					7 => array(
						array(
							"type"		=> "phpcs",
							"file_name"	=> "testfile2.php",
							"file_line"	=> 100,
							"issue"		=> array(
								"message"	=> "Incorrect usage of function myfoo()",
								"source"	=> "RandomStandard.RandomSniff.myfoo_function",
								"severity"	=> 377,
								"fixable"	=> false,
								"type"		=> "INFO",
								"line"		=> 100,
								"column"	=> 1,
								"level"		=> "INFO"
							)
						),
						array(
							"type"		=> "phpcs",
							"file_name"	=> "testfile2.php",
							"file_line"	=> 100,
							"issue"		=> array(
								"message"	=> "Incorrect usage of function testfoo()",
								"source"	=> "RandomStandard.RandomSniff.test_function",
								"severity"	=> 200,
								"fixable"	=> false,
								"type"		=> "INFO",
								"line"		=> 100,
								"column"	=> 1,
								"level"		=> "INFO"
							)
						),
						array(
							"type"		=> "phpcs",
							"file_name"	=> "testfile2.php",
							"file_line"	=> 100,
							"issue"		=> array(
								"message"	=> "Incorrect usage of function foo()",
								"source"	=> "RandomStandard.RandomSniff.random_function",
								"severity"	=> 7,
								"fixable"	=> false,
								"type"		=> "INFO",
								"line"		=> 100,
								"column"	=> 1,
								"level"		=> "INFO"
							)
						),
					),
				)
			),

			$this->results
		);
	}


}
