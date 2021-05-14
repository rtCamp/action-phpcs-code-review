<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitRepoSubmoduleFilePathGetTest extends TestCase {
	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
	);

	var $options_git_repo_tests = array(
		'commit-test-submodule-list-get-2'	=> null,
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
			$this->options_git_repo_tests,
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
		$this->options_git_repo_tests = null;
	}

	/**
	 * @covers ::vipgoci_gitrepo_submodule_file_path_get
	 */
	public function testSubmoduleFilePathGet1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-submodule-list-get-2'];

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
		}

		vipgoci_unittests_output_unsuppress();

		/*
		 * Init and checkout submodules
		 */
		vipgoci_gitrepo_submodules_setup(
			$this->options['local-git-repo']
		);

		$submodule_file_info = vipgoci_gitrepo_submodule_file_path_get(
			$this->options['local-git-repo'],
			'folder1/vip-go-ci-repo/vip-go-ci.php'
		);

		$this->assertSame(
			array(
				'commit_id'		=> 'a0dba40108fe19f028dbd5970022281cc2cabf81',
				'submodule_path'	=> 'folder1/vip-go-ci-repo',
				'submodule_tag'		=> '0.47-1-ga0dba40',
			),
			$submodule_file_info
		);


		$submodule_file_info = vipgoci_gitrepo_submodule_file_path_get(
			$this->options['local-git-repo'],
			'vip-go-ci/notexistingfile.php'
		);

		$this->assertSame(
			null,
			$submodule_file_info
		);


		$submodule_file_info = vipgoci_gitrepo_submodule_file_path_get(
			$this->options['local-git-repo'],
			'vip-go-INVALID/vip-go-ci.php'
		);

		$this->assertSame(
			null,
			$submodule_file_info
		);


		$submodule_file_info = vipgoci_gitrepo_submodule_file_path_get(
			$this->options['local-git-repo'],
			'README.md'
		);

		$this->assertSame(
			null,
			$submodule_file_info
		);
	}
}
