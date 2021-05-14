<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscScandirGitRepoTest extends TestCase {
	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
	);

	var $git_repo_tests = array(
		'commit-test-scandir-repo-test-1'	=> null,
		'commit-test-scandir-repo-test-2'	=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->git_repo_tests
		);

		$this->options = array_merge(
			$this->options_git,
			$this->git_repo_tests
		);

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options_git = null;
		$this->git_repo_tests = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_scandir_git_repo
	 */
	public function testScandirRepoTest1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-scandir-repo-test-1'];

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

		$ret = vipgoci_scandir_git_repo(
			$this->options['local-git-repo'],
			null
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'README.md',
 				'myfile1.txt',	
				'myfile2.txt',
				'myfolder5/myotherfolder6/somefile2.txt',
				'myfolder5/somefile1.txt'
			),
			$ret
		);
	}

	/**
	 * @covers ::vipgoci_scandir_git_repo
	 */
	public function testScandirRepoTest2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-scandir-repo-test-2'];

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

		$ret = vipgoci_scandir_git_repo(
			$this->options['local-git-repo'],
			array(
				'file_extensions'	=> array( 'md' )
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'README.md'
			),
			$ret
		);
	}
}
