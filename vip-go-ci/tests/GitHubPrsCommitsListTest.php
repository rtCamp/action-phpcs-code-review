<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrsCommitsListTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-prs-commits-list-1'	=> null,
		'pr-test-repo-prs-commits-list-1'	=> null,
	);

	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->options_git_repo_tests
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		$this->options = array_merge(
			$this->options_git_repo_tests,
			$this->options_git
		);

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$this->options['branches-ignore'] = array();

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);
	}

	protected function tearDown(): void {
		$this->options_git_repo_tests = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_prs_commits_list
	 */
	public function testGitHubPrsCommitsList1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$commits_list = vipgoci_github_prs_commits_list(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr-test-repo-prs-commits-list-1'],
			$this->options['github-token']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				$this->options['commit-test-repo-prs-commits-list-1']
			),
			$commits_list
		);
	}
}
