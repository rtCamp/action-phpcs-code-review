<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ApNonfunctionalChangesTest extends TestCase {
	var $options_git = array(
		'repo-owner'			=> null,
		'repo-name'			=> null,
		'git-path'			=> null,
		'github-repo-url'		=> null,
	);

	var $options_auto_approvals_nonfunc = array(
		'commit-test-ap-nonfunctionalchanges-1b'	=> null,
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

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

		$this->options['token'] =
			$this->options['github-token'];

		unset( $this->options['github-token'] );
	
		$this->options['commit'] =
			$this->options['commit-test-ap-nonfunctionalchanges-1b'];
	
		$this->options['autoapprove'] = true;

		$this->options['branches-ignore'] = array();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		$this->options['skip-draft-prs'] = false;
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options = null;
		$this->options_git = null;
		$this->options_auto_approval = null;
	}

	/**
	 * @covers ::vipgoci_ap_nonfunctional_changes
	 */
	public function testNonFunctionalChanges1() {

		$auto_approved_files_arr = array();

		vipgoci_unittests_output_suppress();

		vipgoci_ap_nonfunctional_changes(
			$this->options,
			$auto_approved_files_arr
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'file1.php'	=> 'autoapprove-nonfunctional-changes',
				'file2.php'	=> 'autoapprove-nonfunctional-changes',
				/*
				 * - file3.php is not approved, has changed functionally
				 * - file100.txt is not approvable by this function
				 * - file101.png, same.
				 */
			),
			$auto_approved_files_arr
		);
	}
}
