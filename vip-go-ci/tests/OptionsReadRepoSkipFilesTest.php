<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class OptionsReadRepoSkipFilesTest extends TestCase {
	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-owner'		=> null,
		'repo-name'		=> null,
	);

	var $options_git_repo_tests = array(
		'commit-test-options-read-repo-skip-files-1'	=> null,
		'commit-test-options-read-repo-skip-files-2'	=> null,
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

		$this->options['phpcs-skip-folders-in-repo-options-file'] = false;

		$this->options['phpcs-skip-folders'] = array(
			'qqq-75x-n/plugins'
		);

		$this->options['lint-skip-folders-in-repo-options-file'] = false;

		$this->options['lint-skip-folders'] = array(
			'mmm-300/800',
		);

		$this->options['token'] = null;
	}

	protected function tearDown(): void {
		$this->tearDownLocalGitrepo();

		$this->options = null;
		$this->options_git_repo_tests = null;
		$this->options_git = null;
	}

	protected function setUpLocalGitRepo() {
		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
		);
	}

	protected function tearDownLocalGitrepo() {
		if ( ! empty( $this->options['local-git-repo'] ) ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * Tests when options files are present, but not configured
	 * to read them.
	 */
	public function testOptionsReadRepoFilePhpcsTest1() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->setUpLocalGitRepo();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * Uses commit without options files for skip-folders,
	 * is configured to read one of them.
	 */
	public function testOptionsReadRepoFilePhpcsTest2() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-2'];

		$this->options['phpcs-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * Commit with valid skip-folders options file, configured to read
	 * in skip-folders folders for PHPCS.
	 */
	public function testOptionsReadRepoFilePhpcsTest3() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->options['phpcs-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
				'bar-34/751-508x',
				'foo-79/m-250',
				'foo-82/l-folder-450',
				'foo-m/folder-b',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * Configuration file available for PHP Lint folder skipping,
	 * but not configured to read it.
	 */
	public function testOptionsReadRepoFileLintTest1() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->setUpLocalGitRepo();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * Uses commit without options files for PHP linting skip-folders,
	 * is configured to read it.
	 */
	public function testOptionsReadRepoFileLintTest2() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-2'];

		$this->options['lint-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * Uses commit with options files for skip-folders,
	 * is configured to read it.
	 */
	public function testOptionsReadRepoFileLintTest3() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->options['lint-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
				'foo-bar-1/750-500x',
				'bar-foo-3/m-900',
				'foo-foo-9/t-folder-750',
				'foo-test/folder7',
			),
			$this->options['lint-skip-folders']
		);
	}
}
