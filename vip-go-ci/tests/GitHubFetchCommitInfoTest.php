<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubFetchCommitInfoTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-fetch-commit-info-1'	=> null,
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
	 * @covers ::vipgoci_github_fetch_commit_info
	 */
	public function testFetchCommitInfo1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-fetch-commit-info-1'];

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

		$commit_info = vipgoci_github_fetch_commit_info(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			null
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'2533219d08192025f3209a17ddcf9ff21845a08c',
			$commit_info->sha
		);

		unset(
			$commit_info->files[0]->blob_url,
			$commit_info->files[0]->raw_url,
			$commit_info->files[0]->contents_url
		);

		$this->assertSame(
			array(
				'sha'		=> '524acfffa760fd0b8c1de7cf001f8dd348b399d8',
				'filename'	=> 'test1.txt',
				'status'	=> 'added',
				'additions'	=> 1,
				'deletions'	=> 0,
				'changes'	=> 1,
				'patch'		=> '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
			),
			(array) $commit_info->files[0]
		);


		unset( $this->options['commit'] );
	}
}
