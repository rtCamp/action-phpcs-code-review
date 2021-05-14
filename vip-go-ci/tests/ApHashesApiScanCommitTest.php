<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ApHashesApiScanCommitTest extends TestCase {
	var $options_git = array(
		'repo-owner'			=> null,
		'repo-name'			=> null,
		'github-repo-url'		=> null,
		'git-path'			=> null,
	);

	var $options_auto_approvals = array(
		'commit-test-hashes-api-scan-commit'	=> null,
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

		$this->options['hashes-api'] = true;

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];

		unset( $this->options['github-token'] );
		
		$this->options['branches-ignore'] = array();

		foreach (
			array(
				'hashes-api-url',
				'hashes-oauth-token',
				'hashes-oauth-token-secret',
				'hashes-oauth-consumer-key',
				'hashes-oauth-consumer-secret',
			) as $option_secret_key
		) {
			$this->options[ $option_secret_key ] =
				vipgoci_unittests_get_config_value(
					'auto-approvals-secrets',
					$option_secret_key,
					true // Fetch from secrets file
				);
		}
	
		$this->options['commit'] =
			$this->options['commit-test-hashes-api-scan-commit'];

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
		$this->options_auto_approvals = null;
		$this->options_git = null;
	}

	/**
	 * @covers ::vipgoci_ap_hashes_api_scan_commit
	 */
	public function testApHashesApiScanCommitTest1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		$commit_issues_submit = array();
		$commit_issues_stats = array();
		$auto_approved_files_arr = array();

		vipgoci_ap_hashes_api_scan_commit(
			$this->options,
			$commit_issues_submit,
			$commit_issues_stats,
			$auto_approved_files_arr
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'auto-approvable-6.php' => 'autoapprove-hashes-to-hashes',
			),
			$auto_approved_files_arr
		);
	}
}
