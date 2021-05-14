<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubLabelsFetchTest extends TestCase {
	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
		'repo-owner'			=> null,
		'repo-name'			=> null,
	);

	var $options_git_repo_tests = array(
		'pr-test-labels-fetch-test-1'	=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->options_git_repo_tests
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_git_repo_tests
		);

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);
	}

	protected function tearDown(): void {
		$this->options = null;
		$this->options_git = null;
		$this->options_git_repo_tests = null;
	}

	/**
	 * @covers ::vipgoci_github_pr_labels_get
	 */
	public function testLabelsFetch1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$labels = vipgoci_github_pr_labels_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['pr-test-labels-fetch-test-1'],
			null
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'enhancement',
			$labels[0]->name
		);

		$this->assertSame(
			'a2eeef',
			$labels[0]->color
		);
	}
}

