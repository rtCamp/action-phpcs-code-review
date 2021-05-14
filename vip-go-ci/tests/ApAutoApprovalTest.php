<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ApAutoApprovalTest extends TestCase {
	var $safe_to_run = null;

	protected function setUp(): void {
		$this->options_git = array(
			'git-path'				=> null,
			'github-repo-url'			=> null,
			'repo-owner'				=> null,
			'repo-name'				=> null,
		);

		$this->options_auto_approvals = array(
			'pr-test-ap-auto-approval-1'		=> null,
			'commit-test-ap-auto-approval-1'	=> null,
		);

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

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['commit'] =
			$this->options['commit-test-ap-auto-approval-1'];

		$this->options['dry-run'] = false;

		$this->options['branches-ignore'] = array();

		$this->options['autoapprove'] = true;

		$this->options['autoapprove-label'] =
			'Autoapproved Pull-Request';

		// Not used in this test, but needs to be defined
		$this->options['autoapprove-filetypes'] =
			array(
				'css',
				'txt',
				'json',
				'md'
			);

		// Same, not used, but needs to be defined
		$this->options['autoapprove-php-nonfunctional-changes'] = false;

		$this->options['skip-draft-prs'] = false;

		$this->options['local-git-repo'] = false;

		$this->options['pr-test-ap-auto-approval-1'] =
			(int) $this->options['pr-test-ap-auto-approval-1'];

		$this->cleanup_prs();
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->cleanup_prs();

		$this->options_git = null;
		$this->options_auto_approvals = null;
		$this->options = null;
	}

	private function cleanup_prs() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		foreach ( $prs_implicated as $pr_item ) {
			if (
				(int) $pr_item->number
				!==
				(int) $this->options['pr-test-ap-auto-approval-1']
			) {
				printf(
					'Warning: Got unexpected Pull-Request item; pr-number=%d, expected-pr-number=%d, pr_item=%s',
					(int) $pr_item->number,
					(int) $this->options['pr-test-ap-auto-approval-1'],
					print_r( $pr_item, true )
				);

				$this->safe_to_run = false;

				continue;
			}

			else {
				if ( null === $this->safe_to_run ) {
					$this->safe_to_run = true;
				}
			}

			vipgoci_unittests_output_suppress();

			$pr_item_reviews = vipgoci_github_pr_reviews_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $pr_item->number,
				$this->options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'APPROVED' ),
				),
				true // skip cache
			);

			foreach( $pr_item_reviews as $pr_item_review ) {
				vipgoci_github_pr_review_dismiss(
					$this->options['repo-owner'],
					$this->options['repo-name'],
					(int) $pr_item->number,
					(int) $pr_item_review->id,
					'Dismissing obsolete review; not approved any longer',
					$this->options['token']
				);
			}

			vipgoci_unittests_output_unsuppress();
		}
	}

	private function pr_get_labels() {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $this->options['repo-owner'] ) . '/' .
			rawurlencode( $this->options['repo-name'] ) . '/' .
			'issues/' .
			rawurlencode( $this->options['pr-test-ap-auto-approval-1'] ) . '/' .
			'labels';

		$data = vipgoci_github_fetch_url(
			$github_url,
			$this->options['token']
		);

		$data = json_decode( $data );

		foreach( $data as $data_item ) {
			if (
				$data_item->name ===
				$this->options['autoapprove-label']
			) {
				return $data_item;
			}
		}

		return false;
	}

	/**
	 * Test which PRs we get; make sure these
	 * are only the relevant ones. Mimics behaviour
	 * found in vipgoci_auto_approval_scan_commit().
	 *
	 * @covers ::vipgoci_auto_approval
	 */

	public function testAutoApproval1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				'Test not safe to run due to earlier warnings'
			);
		}

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $prs_implicated )
		);

		foreach ( $prs_implicated as $pr_item ) {
			$this->assertSame(
				$this->options['pr-test-ap-auto-approval-1'],
				$pr_item->number
			);
		}
	}

	/**
	 * Test auto-approvals for PR that should
	 * auto-appove.

	 * @covers ::vipgoci_auto_approval
	 */
	public function testAutoApproval2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				'Test not safe to run due to earlier warnings'
			);
		}

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

		$auto_approved_files_arr = array(
			'file-1.php' => 'autoapprove-hashes-to-hashes',
			'file-2.css' => 'autoapprove-filetypes',
			'file-3.txt' => 'autoapprove-filetypes',
			'file-4.json' => 'autoapprove-filetypes',
			'README.md' => 'autoapprove-filetypes',
		);

		$results = array();

		vipgoci_unittests_output_suppress();

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(),
			$results
		);

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $prs_implicated )
		);

		foreach ( $prs_implicated as $pr_item ) {
			$this->assertSame(
				$this->options['pr-test-ap-auto-approval-1'],
				$pr_item->number
			);

			vipgoci_unittests_output_suppress();

			$pr_item_reviews = vipgoci_github_pr_reviews_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $pr_item->number,
				$this->options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'APPROVED' ),
				),
				true // skip cache
			);

			vipgoci_unittests_output_unsuppress();

			$this->assertSame(
				1,
				count( $pr_item_reviews )
			);

			foreach( $pr_item_reviews as $pr_item_review ) {
				$this->assertSame(
					'APPROVED',
					$pr_item_review->state
				);
			}
		}

		$labels = $this->pr_get_labels();

		$this->assertSame(
			$this->options['autoapprove-label'],
			$labels->name
		);
	}

	/**
	 * Test auto-approvals for PR that should
	 * not auto-appove.

	 * @covers ::vipgoci_auto_approval
	 */
	public function testAutoApproval3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				'Test not safe to run due to earlier warnings'
			);
		}

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

		$auto_approved_files_arr = array(
			// note: file-1.php is NOT approved
			'file-2.css' => 'autoapprove-filetypes',
			'file-3.txt' => 'autoapprove-filetypes',
			'file-4.json' => 'autoapprove-filetypes',
			'README.md' => 'autoapprove-filetypes',
		);

		$results = array();

		vipgoci_unittests_output_suppress();

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(),
			$results
		);

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $prs_implicated )
		);

		foreach ( $prs_implicated as $pr_item ) {
			$this->assertSame(
				$this->options['pr-test-ap-auto-approval-1'],
				$pr_item->number
			);

			vipgoci_unittests_output_suppress();

			$pr_item_reviews = vipgoci_github_pr_reviews_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $pr_item->number,
				$this->options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'APPROVED' ),
				),
				true // skip cache
			);

			vipgoci_unittests_output_unsuppress();

			$this->assertSame(
				0,
				count( $pr_item_reviews )
			);
		}

		$label = $this->pr_get_labels();

		$this->assertSame(
			false,
			$label
		);
	}

	/**
	 * Test auto-approvals for PR that should
	 * not auto-appove, but should leave a comment
	 * about one PHP file that is approved.
	 *
	 * @covers ::vipgoci_auto_approval
	 */
	public function testAutoApproval4() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				'Test not safe to run due to earlier warnings'
			);
		}

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

		$auto_approved_files_arr = array(
			// note: file-1.php is approved, but
			// some of the other files are not
			'file-1.php' => 'autoapprove-hashes-to-hashes',
			'file-2.css' => 'autoapprove-filetypes',
			'file-3.txt' => 'autoapprove-filetypes',
			// file-4.json is not approved
			'README.md' => 'autoapprove-filetypes',
		);

		$results = array(
			'stats' => array(
				VIPGOCI_STATS_HASHES_API => array(
					$this->options['pr-test-ap-auto-approval-1'] => array(
						'info' => 0
					)
				)
			)
		);

		vipgoci_unittests_output_suppress();

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $prs_implicated )
		);

		foreach ( $prs_implicated as $pr_item ) {
			$this->assertSame(
				$this->options['pr-test-ap-auto-approval-1'],
				$pr_item->number
			);

			vipgoci_unittests_output_suppress();

			$pr_item_reviews = vipgoci_github_pr_reviews_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $pr_item->number,
				$this->options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'APPROVED' ),
				),
				true // skip cache
			);

			vipgoci_unittests_output_unsuppress();

			$this->assertSame(
				0,
				count( $pr_item_reviews )
			);
		}

		$label = $this->pr_get_labels();

		$this->assertSame(
			false,
			$label
		);

		$this->assertSame(
			1,
			$results['stats']
				[ VIPGOCI_STATS_HASHES_API ]
				[ $this->options['pr-test-ap-auto-approval-1'] ]
				[ 'info' ]
		);

		$this->assertSame(
			'file-1.php',
			$results['issues']
				[ $this->options['pr-test-ap-auto-approval-1'] ]
				[ 0 ]
				['file_name']
		);
	}

	/**
	 * In vipgoci_autoapproval_do_approve() we make
	 * sure we do not re-approve already approved
	 * Pull-Requests. Make sure this really works.
	 *
	 * @covers ::vipgoci_auto_approval
	 */
	public function testAutoApproval5() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				'Test not safe to run due to earlier warnings'
			);
		}

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

		/*
		 * Make sure no reviews indicate approval currently.
		 */
		vipgoci_unittests_output_suppress();

		$pr_item_reviews = vipgoci_github_pr_reviews_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr-test-ap-auto-approval-1'],
			$this->options['token'],
			array(
				'login' => 'myself',
				'state' => array( 'APPROVED' ),
			),
			true // skip cache
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			0,
			count( $pr_item_reviews )
		);

		/*
		 * Auto-approve the PR twice.
		 */

		$auto_approved_files_arr = array(
			// all files in the PR are approvable
			'file-1.php' => 'autoapprove-hashes-to-hashes', 
			'file-2.css' => 'autoapprove-filetypes',
			'file-3.txt' => 'autoapprove-filetypes',
			'file-4.json' => 'autoapprove-filetypes',
		);
		
		$results = array(
			'stats' => array(
				VIPGOCI_STATS_HASHES_API => array(
					$this->options['pr-test-ap-auto-approval-1'] => array(
						'info' => 0
					)
				)
			)
		);

		vipgoci_unittests_output_suppress();

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		$pr_item_reviews = vipgoci_github_pr_reviews_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr-test-ap-auto-approval-1'],
			$this->options['token'],
			array(
				'login' => 'myself',
				'state' => array( 'APPROVED' ),
			),
			true // skip cache
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $pr_item_reviews )
		);
	}
}
