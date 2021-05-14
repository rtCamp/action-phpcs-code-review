<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ApFileTypesTest extends TestCase {
	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
		'repo-owner'			=> null,
		'repo-name'			=> null,
	);

	var $options_auto_approvals = array(
		'commit-test-file-types-1'	=> null,	
		'autoapprove-filetypes'		=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'auto-approvals',
			$this->options_auto_approvals
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_auto_approvals
		);

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		if ( empty( $this->options[ 'github-token' ] ) ) {
			$this->options[ 'github-token' ] = '';
		}

		$this->options['token'] =
			$this->options['github-token'];

		unset( $this->options['github-token'] );
	
		$this->options['commit'] =
			$this->options['commit-test-file-types-1'];
	
		$this->options['autoapprove'] = true;
		$this->options['autoapprove-filetypes'] =
			explode(
				',',
				$this->options['autoapprove-filetypes']
			);

		$this->options['branches-ignore'] = array();

		$this->options['skip-draft-prs'] = false;
	}

	protected function tearDown(): void {
		$this->options = null;
		$this->options_git = null;
		$this->options_auto_approval = null;
	}

	/**
	 * @covers ::vipgoci_ap_file_types
	 */
	public function testFileTypes1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$auto_approved_files_arr = array();

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

		vipgoci_ap_file_types(
			$this->options,
			$auto_approved_files_arr
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'README.md'		=> 'autoapprove-filetypes',
				'auto-approvable-1.txt' => 'autoapprove-filetypes',
				'auto-approvable-2.txt' => 'autoapprove-filetypes',
				'auto-approvable-3.jpg' => 'autoapprove-filetypes',
			),
			$auto_approved_files_arr
		);
	}
}
