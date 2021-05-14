<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscPatchChangedLinesTest extends TestCase {
	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-owner'		=> null,
		'repo-name'		=> null,
	);

	var $options_patch_changed_lines = array(
		'pr-base-sha'		=> null,
		'commit-id'		=> null,
		'pr-base-sha-2'		=> null,
		'commit-id-2'		=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'patch-changed-lines',
			$this->options_patch_changed_lines
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_patch_changed_lines
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
			$this->options[ 'github-token' ];

		$this->options['commit'] = 'master';
	}

	/**
	 * @covers ::vipgoci_patch_changed_lines
	 */
	public function testPatchChangedLines1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->markTestSkipped(
				'Must set up vipgoci_patch_changed_lines() test'
			);

			return;
		}

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

		/*
		 * Test when file is not part of the
		 * patch.
		 */

		$patch_arr = vipgoci_patch_changed_lines(
			$this->options['local-git-repo'],
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['pr-base-sha-2'],
			$this->options['commit-id-2'],
			'README.md'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			null,
			$patch_arr
		);

		/*
		 * Test simple patch.
		 */
		vipgoci_unittests_output_suppress();

		$patch_arr = vipgoci_patch_changed_lines(
			$this->options['local-git-repo'],
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['pr-base-sha'],
			$this->options['commit-id'],
			'README.md'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				null, 1, null, "1", 2, 3, 4, 5, 6, 7
			),
			$patch_arr
		);

		/*
		 * Test series of more complex patches.
		 */
		vipgoci_unittests_output_suppress();

		$patch_arr = vipgoci_patch_changed_lines(
			$this->options['local-git-repo'],
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['pr-base-sha-2'],
			$this->options['commit-id-2'],
			'file2.php'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				0 => null,
				1 => '1',
				2 => 2,
				3 => null,
				4 => 3, 
				5 => null,
				6 => null,
				7 => 4,
				8 => null,
				9 => null, 
				10 => 5, 
				11 => 6, 
				12 => 7
			),
			$patch_arr
		);

		vipgoci_unittests_output_suppress();

		$patch_arr = vipgoci_patch_changed_lines(
			$this->options['local-git-repo'],
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['pr-base-sha-2'],
			$this->options['commit-id-2'],
			'file3.php'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				0 => null,
				1 => '7',
				2 => 8,
				3 => 9,
				4 => null, 
				5 => 10,
				6 => 11,
				7 => 12,
				8 => 13,
			),
			$patch_arr
		);
	}
}
