<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ApSvgFilesTest extends TestCase {
	var $options_git = array(
		'repo-owner'			=> null,
		'repo-name'			=> null,
		'github-repo-url'		=> null,
		'git-path'			=> null,
	);

	var $options_svg_scan = array(
		'svg-scanner-path'		=> null,
	);

	var $options_auto_approvals = array(
		'autoapprove-filetypes'		=> null,
		'commit-test-svg-files-1'	=> null,
		'commit-test-svg-files-2b'	=> null,
	);
	
	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'svg-scan',
			$this->options_svg_scan
		);

		vipgoci_unittests_get_config_values(
			'auto-approvals',
			$this->options_auto_approvals
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_svg_scan,
			$this->options_auto_approvals
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
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options = null;
		$this->options_auto_approvals = null;
		$this->options_svg_scan = null;
		$this->options_git = null;
	}

	/**
	 * @covers ::vipgoci_ap_svg_files
	 */
	public function testApSvgFiles1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$auto_approved_files_arr = array();

		$this->options['svg-checks'] = true;

		$this->options['commit'] =
			$this->options['commit-test-svg-files-1'];


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

		vipgoci_ap_svg_files(
			$this->options,
			$auto_approved_files_arr
		);

		vipgoci_unittests_output_unsuppress();


		$this->assertSame(
			array(
				'auto-approvable-1.svg' => 'ap-svg-files',
				'auto-approvable-2.svg' => 'ap-svg-files',
			),
			$auto_approved_files_arr
		);

		unset( $this->options['svg-checks'] );
	}

	/**
	 * Test auto-approvals of SVG files that
	 * have been renamed, removed, or had their
	 * permissions changed.
	 *
	 * @covers ::vipgoci_ap_svg_files
	 */
	public function testApSvgFiles2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$auto_approved_files_arr = array();

		$this->options['svg-checks'] = true;

		$this->options['commit'] =
			$this->options['commit-test-svg-files-2b'];


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

		vipgoci_ap_svg_files(
			$this->options,
			$auto_approved_files_arr
		);

		vipgoci_unittests_output_unsuppress();


		$this->assertSame(
			array(
				'auto-approvable-1.svg' => 'ap-svg-files',
				'auto-approvable-2-renamed.svg' => 'ap-svg-files',
				'auto-approvable-7.svg' => 'ap-svg-files',
				'auto-approvable3.svg' => 'ap-svg-files',
				'auto-approvable4.svg' => 'ap-svg-files',
			),
			$auto_approved_files_arr
		);

		unset( $this->options['svg-checks'] );
	}
}
