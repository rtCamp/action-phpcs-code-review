<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitRepoRepoGetFileAtCommitTest extends TestCase {
	var $options_git = array(
		'repo-owner'			=> null,
		'repo-name'			=> null,
		'git-path'			=> null,
		'github-repo-url'		=> null,
	);

	// Use auto-approval settings for repository-data
	var $options_auto_approvals_nonfunc = array(
		'commit-test-repo-get-file-at-commit-1'		=> null,
		'commit-test-repo-get-file-at-commit-2'		=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'auto-approvals',
			$this->options_auto_approvals_nonfunc
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_auto_approvals_nonfunc
		);

		$this->options['commit'] =
			$this->options['commit-test-repo-get-file-at-commit-2'];

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options = null;
		$this->options_git = null;
		$this->options_auto_approvals_nonfunc = null;
	}

	/**
	 * @covers ::vipgoci_gitrepo_get_file_at_commit
	 */
	public function testGetFileData1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(
				'commit-test-repo-get-file-at-commit-1',
				'commit-test-repo-get-file-at-commit-2'
			),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		/*
		 * Get file1.php from two different
		 * commits, check SHA1 sum.
		 */

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-1'],
			'file1.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'04f338f924cabbe47994043660304e58a5a3f78f',
			sha1( $file_content )
		);

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-2'],
			'file1.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'c4587f2e42de3ab1ecdf51c993a3135bb1314b68',
			sha1( $file_content )
		);


		/*
		 * Same with file2.php.
		 */

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-1'],
			'file2.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'04f338f924cabbe47994043660304e58a5a3f78f',
			sha1( $file_content )
		);

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-2'],
			'file2.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'f8c824b9bc01a5655e77a10a3f2e5fa704a58f9c',
			sha1( $file_content )
		);
	}
}
