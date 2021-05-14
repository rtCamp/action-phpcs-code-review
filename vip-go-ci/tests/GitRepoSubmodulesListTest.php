<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitRepoSubmodulesListTest extends TestCase {
	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
	);

	var $options_git_repo_tests = array(
		'commit-test-submodule-list-get-1'	=> null,
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
	 * @covers ::vipgoci_gitrepo_submodules_list
	 */
	public function testSubmodulesListGet1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-submodule-list-get-1'];

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

		$ret = vipgoci_gitrepo_submodules_list(
			$this->options['local-git-repo']
		);

		$this->assertSame(
			array(
				array(
					'commit_id'		=> 'a0dba40108fe19f028dbd5970022281cc2cabf81',
					'submodule_path'	=> 'vip-go-ci',
					'submodule_tag'		=> '0.47-1-ga0dba40',
				),
			),
			$ret,
		);
	}
}
