<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitRepoRepoBlameForFileTest extends TestCase {
	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
		'repo-owner'			=> null,
		'repo-name'			=> null,
	);

	var $options_git_repo_tests = array(
		'commit-test-repo-blame-for-file-2'	=> null,
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
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options );
		unset( $this->options_git );
		unset( $this->options_git_repo_tests );
	}

	/**
	 * @covers ::vipgoci_gitrepo_blame_for_file
	 */
	public function testGitRepoBlameForFile1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-blame-for-file-2'];

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

		$this->options['token'] =
			$this->options['github-token'];


		$ret = vipgoci_gitrepo_blame_for_file(
			$this->options['commit'],
			'README.md',
			$this->options['local-git-repo']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				array(
					'commit_id'	=> '4869335189752462325aaef4838c9761d56195ce',
					'file_name'	=> 'README.md',
					'line_no'	=> 1,
					'content'	=> '# vip-go-ci-testing',
				),
				array(
					'commit_id'	=> '45b9e6479dfba4d54b584d53ace1814ce155d35e',
					'file_name'	=> 'README.md',
					'line_no'	=> 2,
					'content'	=> 'Pull-Requests, commits and data to test <a href="https://github.com/automattic/vip-go-ci/">vip-go-ci</a>\'s functionality. Please do not remove or alter unless you\'ve contacted the VIP Team first. ',
				)
			),
			$ret
		);

		vipgoci_unittests_output_suppress();

		$ret = vipgoci_gitrepo_blame_for_file(
			$this->options['commit'],
			'file1.txt',
			$this->options['local-git-repo']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				array(
					'commit_id'	=> 'bb001b24bf6bbdd98004ea49511a4290e173a965',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 1,
					'content'	=> 'Line 1',
				),
				array(
					'commit_id'	=> 'bb001b24bf6bbdd98004ea49511a4290e173a965',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 2,
					'content'	=> '',
				),
				array(
					'commit_id'	=> '179ed3fa92f15c65b127adb459974d65ff8df053',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 3,
					'content'	=> 'Line 3',
				),
				array(
					'commit_id'	=> '179ed3fa92f15c65b127adb459974d65ff8df053',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 4,
					'content'	=> '',
				),
				array(
					'commit_id'	=> '5292767197e77cbd1259671913bd2912b24d7e10',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 5,
					'content'	=> 'Line 5',
				),
				array(
					'commit_id'	=> '5292767197e77cbd1259671913bd2912b24d7e10',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 6,
					'content'	=> '',
				),
				array(
					'commit_id'	=> '179ed3fa92f15c65b127adb459974d65ff8df053',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 7,
					'content'	=> 'Line 7',
				),
				array(
					'commit_id'	=> '179ed3fa92f15c65b127adb459974d65ff8df053',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 8,
					'content'	=> '',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 9,
					'content'	=> 'echo "1";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 10,
					'content'	=> 'echo "2";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 11,
					'content'	=> 'echo "3";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 12,
					'content'	=> 'echo "4";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 13,
					'content'	=> 'echo "5";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 14,
					'content'	=> 'echo "6";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 15,
					'content'	=> 'echo "7";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 16,
					'content'	=> 'echo "8";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 17,
					'content'	=> 'echo "9";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 18,
					'content'	=> 'echo "10";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 19,
					'content'	=> 'echo "11";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 20,
					'content'	=> 'echo "12";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 21,
					'content'	=> 'echo "13";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 22,
					'content'	=> 'echo "14";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 23,
					'content'	=> 'echo "15";',
				),
				array(
					'commit_id'	=> 'ac5aa2f4199906a2dcb335f97ec053995a59c546',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 24,
					'content'	=> '',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 25,
					'content'	=> 'echo "495";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 26,
					'content'	=> 'echo "496";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 27,
					'content'	=> 'echo "497";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 28,
					'content'	=> 'echo "498";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 29,
					'content'	=> 'echo "499";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 30,
					'content'	=> 'echo "500";',
				),
				array(
					'commit_id'	=> 'bf3d2edfa286b3531d4f0d491fbb2ce27a75af9e',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 31,
					'content'	=> '',
				),
				array(
					'commit_id'	=> 'bb001b24bf6bbdd98004ea49511a4290e173a965',
					'file_name'	=> 'file1.txt',
					'line_no'	=> 32,
					'content'	=> 'Last line of file',
				),
			),
			$ret
		);
	}
}
