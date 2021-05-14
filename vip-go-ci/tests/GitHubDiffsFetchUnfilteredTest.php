<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );
require_once( __DIR__ . '/GitDiffsFetchUnfilteredTrait.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

/*
 * This test should be identical with GitRepoDiffsFetchUnfilteredTest.php
 *
 * Note that these two tests share the same data, via
 * the GitDiffsFetchUnfilteredTrait.php file.
 */
final class GitHubDiffsFetchUnfilteredTest extends TestCase {
	use GitDiffsFetchUnfilteredTrait;

	var $options_git_repo_tests = array(
		'commit-test-repo-pr-diffs-1-a'	=> null,
		'commit-test-repo-pr-diffs-1-b'	=> null,
		'commit-test-repo-pr-diffs-1-c'	=> null,
		'commit-test-repo-pr-diffs-1-d'	=> null,
		'commit-test-repo-pr-diffs-1-e'	=> null,
		'commit-test-repo-pr-diffs-1-f' => null,
		'commit-test-repo-pr-diffs-1-g' => null,
		'commit-test-repo-pr-diffs-2-a' => null,
		'commit-test-repo-pr-diffs-2-b' => null,
	);

	var $options_git = array(
		'repo-owner'		=> null,
		'repo-name'		=> null,
		'github-repo-url'	=> null,
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

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

		$this->options[ 'token' ] =
			$this->options['github-token'];

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$this->options['branches-ignore'] = array();

		/* By default checkout 'master' branch */
		$this->options['commit'] = 'master';
	}

	protected function tearDown(): void {
		$this->options_git_repo_tests = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_github_diffs_fetch_unfiltered
	 */
	public function testGitHubDiffsFetch1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$diff = vipgoci_github_diffs_fetch_unfiltered(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-b']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$this->_dataGitDiffsAssert1(),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_github_diffs_fetch_unfiltered
	 */
	public function testGitHubDiffsFetch2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$diff = vipgoci_github_diffs_fetch_unfiltered(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-c']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$this->_dataGitDiffsAssert2(),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_github_diffs_fetch_unfiltered
	 */
	public function testGitHubDiffsFetch3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$diff = vipgoci_github_diffs_fetch_unfiltered(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-e']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$this->_dataGitDiffsAssert3(),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_github_diffs_fetch_unfiltered
	 */
	public function testGitHubDiffsFetch4() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$diff = vipgoci_github_diffs_fetch_unfiltered(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['commit-test-repo-pr-diffs-1-e'],
			$this->options['commit-test-repo-pr-diffs-1-f']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$this->_dataGitDiffsAssert4(),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_github_diffs_fetch_unfiltered
	 */
	public function testGitHubDiffsFetch5() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$diff = vipgoci_github_diffs_fetch_unfiltered(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['commit-test-repo-pr-diffs-1-f'],
			$this->options['commit-test-repo-pr-diffs-1-g']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$this->_dataGitDiffsAssert5(),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_github_diffs_fetch_unfiltered
	 */
	public function testGitHubDiffsFetch6() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$diff = vipgoci_github_diffs_fetch_unfiltered(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['commit-test-repo-pr-diffs-2-a'],
			$this->options['commit-test-repo-pr-diffs-2-b']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$this->_dataGitDiffsAssert6(),
			$diff
		);
	
		/*
		 * As an additional check, verify that caching is OK.
		 */
		$diff_same = vipgoci_github_diffs_fetch_unfiltered(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['commit-test-repo-pr-diffs-2-a'],
			$this->options['commit-test-repo-pr-diffs-2-b']
		);

		$this->assertSame(
			$diff,
			$diff_same
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_github_diffs_fetch_unfiltered
	 */
	public function testGitHubDiffsFetch7() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		/*
		 * Try with invalid object, should
		 * return with null
		 */
		$diff = vipgoci_github_diffs_fetch_unfiltered(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['commit-test-repo-pr-diffs-2-a'],
			'1111111111111111111111111111111111111111'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			null,
			$diff
		);
	}
}

