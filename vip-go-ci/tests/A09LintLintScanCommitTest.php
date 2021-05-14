<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class A09LintLintScanCommitTest extends TestCase {
	var $options_lint_scan = array(
		'php-path'				=> null,
		'commit-test-lint-scan-commit-1'	=> null,
		'commit-test-lint-scan-commit-2'	=> null,
	);

	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'lint-scan',
			$this->options_lint_scan
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		$this->options = array_merge(
			$this->options_lint_scan,
			$this->options_git
		);

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$this->options['branches-ignore'] = array();

		$this->options['skip-draft-prs'] = false;

		global $vipgoci_debug_level;
		$vipgoci_debug_level = 2;
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options_lint_scan = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_lint_scan_commit
	 */
	public function testLintDoScan1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-lint-scan-commit-1'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					vipgoci_unittests_output_get()
			);

			return;
		}

		$issues_submit = array();
		$issues_stat = array();

		/*
		 * Get PRs implicated and warm up stats.
		 */
		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		foreach( $prs_implicated as $pr_item ) {
			$issues_stat[
				$pr_item->number
			][
				'error'
			] = 0;
		}

		if (
			( ! isset( $pr_item->number ) ) ||
			( ! is_numeric( $pr_item->number ) )
		) {
			$this->markTestSkipped(
				'Could not get Pull-Request information for the test: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_lint_scan_commit(
			$this->options,
			$issues_submit,
			$issues_stat
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * Some versions of PHP reverse the ',' and ';'
		 * in the string below; deal with that.
		 */
		$issues_submit[ $pr_item->number ][0]['issue']['message'] =
			vipgoci_unittests_php_syntax_error_compat(
				$issues_submit[ $pr_item->number][0]['issue']['message']
			);

		$this->assertSame(
			array(
				$pr_item->number => array(
					array(
						'type' => 'lint',
						'file_name' => 'lint-scan-commit-test-2.php',
						'file_line' => 4,
						'issue' => array(
							'message'	=> "syntax error, unexpected end of file, expecting ',' or ';'",
							'level'		=> 'ERROR',
							'severity'	=> 5,
						)
					)
				)
			),
			$issues_submit
		);

		$this->assertSame(
			array(
				$pr_item->number => array(
					'error' => 1,
				)
			),
			$issues_stat
		);

		unset( $this->options['commit'] );
	}


	/**
	 * @covers ::vipgoci_lint_scan_commit
	 */
	public function testLintDoScan2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-lint-scan-commit-2'];

		$this->options['lint-skip-folders'] = array(
			'tests3',
			'tests4000',
			'tests5000',
		);

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					vipgoci_unittests_output_get()
			);

			return;
		}

		$issues_submit = array();
		$issues_stat = array();

		/*
		 * Get PRs implicated and warm up stats.
		 */
		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		foreach( $prs_implicated as $pr_item ) {
			$issues_stat[
				$pr_item->number
			][
				'error'
			] = 0;
		}

		if (
			( ! isset( $pr_item->number ) ) ||
			( ! is_numeric( $pr_item->number ) )
		) {
			$this->markTestSkipped(
				'Could not get Pull-Request information for the test: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_lint_scan_commit(
			$this->options,
			$issues_submit,
			$issues_stat
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * Some versions of PHP reverse the ',' and ';'
		 * in the string below; deal with that.
		 */
		$issues_submit[ $pr_item->number ][0]['issue']['message'] =
			vipgoci_unittests_php_syntax_error_compat(
				$issues_submit[ $pr_item->number][0]['issue']['message']
			);

		$issues_submit[ $pr_item->number ][1]['issue']['message'] =
			vipgoci_unittests_php_syntax_error_compat(
				$issues_submit[ $pr_item->number][1]['issue']['message']
			);


		$this->assertSame(
			array(
				$pr_item->number => array(
					array(
						'type' => 'lint',
						'file_name' => 'tests1/myfile1.php',
						'file_line' => 4,
						'issue' => array(
							'message'	=> "syntax error, unexpected end of file, expecting ',' or ';'",
							'level'		=> 'ERROR',
							'severity'	=> 5,
						)
					),
					array(
						'type' => 'lint',
						'file_name' => 'tests2/myfile1.php',
						'file_line' => 4,
						'issue' => array(
							'message'	=> "syntax error, unexpected end of file, expecting ',' or ';'",
							'level'		=> 'ERROR',
							'severity'	=> 5,
						)
					),
					/*
					 * Note: tests3/myfile1.php should be skipped, 
					 * according to --lint-skip-folders option
					 * set above.
					 */
				)
			),
			$issues_submit
		);

		$this->assertSame(
			array(
				$pr_item->number => array(
					'error' => 2,
				)
			),
			$issues_stat
		);

		unset( $this->options['commit'] );
	}


}
