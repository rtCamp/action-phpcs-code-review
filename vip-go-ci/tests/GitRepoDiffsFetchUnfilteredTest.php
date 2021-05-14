<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );
require_once( __DIR__ . '/GitDiffsFetchUnfilteredTrait.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

/*
 * This test should be identical with GitHubDiffsFetchUnfilteredTest.php
 *
 * Note that these two tests share the same data, via
 * the GitDiffsFetchUnfilteredTrait.php file.
 */
final class GitRepoDiffsFetchUnfilteredTest extends TestCase {
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
		'git-path'		=> null,
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

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$this->options['branches-ignore'] = array();

		/* By default checkout 'master' branch */
		$this->options['commit'] = 'master';

		$this->options['local-git-repo'] = false;
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options_git_repo_tests = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
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
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
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
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
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
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch4() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
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
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch5() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
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
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch6() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-2-a'],
			$this->options['commit-test-repo-pr-diffs-2-b']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$this->_dataGitDiffsAssert6(),
			$diff
		);
	
		vipgoci_unittests_output_suppress();

		/*
		 * As an additional check, verify that caching is OK.
		 */
		$diff_same = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-2-a'],
			$this->options['commit-test-repo-pr-diffs-2-b']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$diff,
			$diff_same
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch7() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

		/*
		 * Try with invalid object, should
		 * return with null
		 */
		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
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

