<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrsImplicatedTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-pr-files-changed-1'	=> null,
		'commit-test-repo-pr-files-changed-2'	=> null,
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

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$this->options['branches-ignore'] = array();
	}

	protected function tearDown(): void {
		$this->options_git_repo_tests = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_prs_implicated
	 */
	public function testGitHubPrsImplicatedIncludeDraftPrs() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-pr-files-changed-1'];

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			259078544,
			$prs_implicated[9]->id
		);

		$this->assertSame(
			'80ebd6d65db88e87665b6ff1aa045f68d17ddeb7',
			$prs_implicated[9]->merge_commit_sha
		);

		$this->assertSame(
			'open',
			$prs_implicated[9]->state
		);

		unset( $this->options['commit'] );
	}

	/**
	 * @covers ::vipgoci_github_prs_implicated
	 */
	public function testGitHubPrsImplicatedSkipDraftPrsFalse() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-pr-files-changed-2'];

		/*
		 * Get all PRs, draft and non-draft.
		 */

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore'],
			false
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertCount(
			2,
			$prs_implicated
		);

		/*
		 * Verify non-draft PR.
		 */
		$this->assertSame(
			'open',
			$prs_implicated[33]->state
		);

		$this->assertSame(
			463586588,
			$prs_implicated[33]->id
		);

		$this->assertSame(
			'ac10d1f29e64504d7741cd8ca22981c426c26e9a',
			$prs_implicated[33]->base->sha
		);

		$this->assertSame(
			false,
			$prs_implicated[33]->draft
		);

		/*
		 * Verify draft PR.
		 */
		$this->assertSame(
			'open',
			$prs_implicated[34]->state
		);

		$this->assertSame(
			463587649,
			$prs_implicated[34]->id
		);

		$this->assertSame(
			'027de6d804e1d40dbe1b13a3ede7cfa758787b85',
			$prs_implicated[34]->base->sha
		);

		$this->assertSame(
			true,
			$prs_implicated[34]->draft
		);

		/*
		 * Repeat fetching of PRs, and check
		 * if we get the same result as earlier.
		 */

		vipgoci_unittests_output_suppress();

		$prs_implicated_2 = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore'],
			false
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$prs_implicated,
			$prs_implicated_2
		);
	}

	/**
	 * @covers ::vipgoci_github_prs_implicated
	 */
	public function testGitHubPrsImplicatedSkipDraftPrsTrue() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-pr-files-changed-2'];


		/*
		 * Now fetch all PRs, draft and non-draft.
		 */
	
		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore'],
			true
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertCount(
			1,
			$prs_implicated
		);

		/*
		 * Verify non-draft PR.
		 */
		$this->assertSame(
			'open',
			$prs_implicated[33]->state
		);

		$this->assertSame(
			463586588,
			$prs_implicated[33]->id
		);

		$this->assertSame(
			'ac10d1f29e64504d7741cd8ca22981c426c26e9a',
			$prs_implicated[33]->base->sha
		);

		$this->assertSame(
			false,
			$prs_implicated[33]->draft
		);

		/*
		 * Repeat and check if we get the same
		 * results again. This is to check if
		 * the caching-functionality works correctly.
		 */
		vipgoci_unittests_output_suppress();

		$prs_implicated_2 = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore'],
			true
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$prs_implicated,
			$prs_implicated_2
		);

		unset( $this->options['commit'] );
	}
}
